<?php

namespace App\Http\Controllers;

use App\Models\Issue;
use App\Models\Organization;
use App\Models\RequestAnalytic;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

/**
 * Internal operations / monitoring endpoints for the development company.
 *
 * These aggregate health signals ACROSS all tenants so support can see, at a
 * glance, whether Tavro is healthy and whether any customer is hitting errors.
 * They are gated by the `ops.token` middleware (internal shared secret) and
 * are read-only — never mutate tenant data.
 */
class OpsMonitorController extends Controller
{
    use ApiResponse;

    /**
     * One-shot health + incident summary for the dev team.
     * Cheap aggregate queries; safe to call every minute from a monitor.
     */
    public function summary(Request $request)
    {
        $now = now();

        // ── Signals ──────────────────────────────────────────────────────────
        $last24h = $now->copy()->subHours(24);

        $totals = RequestAnalytic::where('created_at', '>=', $last24h)
            ->selectRaw('count(*) as total, sum(case when is_error then 1 else 0 end) as errors')
            ->first();

        $errorRate = ($totals->total > 0) ? round($totals->errors / $totals->total * 100, 2) : 0.0;

        // Recent server-side errors (5xx) with context.
        $recentServers = RequestAnalytic::where('status_code', '>=', 500)
            ->where('created_at', '>=', $now->copy()->subHours(3))
            ->orderByDesc('created_at')
            ->limit(20)
            ->get(['endpoint', 'method', 'status_code', 'response_time_ms', 'created_at', 'organization_id']);

        // Open customer issues that need support attention.
        $openIssues = Issue::with(['organization:id,name'])
            ->whereIn('status', ['open', 'in_progress'])
            ->orderByDesc('created_at')
            ->limit(25)
            ->get(['id', 'organization_id', 'title', 'severity', 'category', 'status', 'created_at']);

        $openIssueCount = Issue::whereIn('status', ['open', 'in_progress'])->count();
        $urgentIssueCount = Issue::whereIn('status', ['open', 'in_progress'])
            ->where('severity', 'in', ['high', 'critical'])
            ->count();

        // Org & user tallies.
        $totalOrganizations = Organization::count();
        $recentSignups = User::where('created_at', '>=', $now->copy()->subDays(7))->count();

        // Recent accounts that signed up but have NOT verified their email yet.
        $unverifiedRecent = User::where('created_at', '>=', $now->copy()->subDays(7))
            ->whereNull('email_verified_at')
            ->count();

        // Queue backlog (if Redis is the queue driver).
        $queueInfo = $this->queueBacklog();

        return $this->success([
            'generated_at' => $now->toIso8601String(),
            'signals' => [
                'total_requests_24h' => (int) $totals->total,
                'errors_24h' => (int) $totals->errors,
                'error_rate_24h' => $errorRate,
                'has_error_spike' => $totals->total >= 50 && $errorRate > 5,
            ],
            'recent_server_errors' => $recentServers,
            'issues' => [
                'open' => $openIssueCount,
                'urgent' => $urgentIssueCount,
                'recent' => $openIssues,
            ],
            'delivery' => [
                'total_organizations' => $totalOrganizations,
                'recent_signups_7d' => $recentSignups,
                'unverified_signups_7d' => $unverifiedRecent,
            ],
            'queue' => $queueInfo,
        ]);
    }

    /**
     * Rate of error per endpoint over the last hour — for triage dashboards.
     */
    public function errorBreakdown(Request $request)
    {
        $since = now()->subHour();

        $breakdown = RequestAnalytic::where('is_error', true)
            ->where('created_at', '>=', $since)
            ->selectRaw('endpoint, method, status_code, count(*) as occurrences')
            ->groupBy('endpoint', 'method', 'status_code')
            ->orderByDesc('occurrences')
            ->limit(25)
            ->get();

        return $this->success($breakdown);
    }

    /**
     * Cross-tenant open issues, optionally filtered.
     */
    public function issues(Request $request)
    {
        $query = Issue::with(['organization:id,name'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('severity')) {
            $query->where('severity', $request->string('severity'));
        }

        return $this->success($query->limit(100)->get());
    }

    /**
     * Resolve a customer issue on the customer's behalf after support has
     * fixed the underlying problem. This is the only write this controller
     * allows, to keep the support loop tight.
     */
    public function resolveIssue(Request $request, int $issueId)
    {
        $validated = $request->validate([
            'note' => 'nullable|string|max:1000',
        ]);

        $issue = Issue::find($issueId);
        if (! $issue) {
            return $this->error('Issue not found.', 404);
        }

        $meta = $issue->metadata ?? [];
        $meta['resolved_via_ops'] = now()->toIso8601String();
        $meta['resolution_note'] = $validated['note'] ?? '';

        $issue->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'metadata' => $meta,
        ]);

        return $this->success($issue->fresh(['organization:id,name']));
    }

    /**
     * Simple Redis queue backlog numbers. Only meaningful when the queue
     * connection is Redis; otherwise returns nulls without erroring.
     */
    private function queueBacklog(): array
    {
        try {
            if (config('queue.default') !== 'database' && config('queue.default') !== 'redis') {
                return ['driver' => config('queue.default'), 'queues' => []];
            }
        } catch (\Throwable) {
            return ['driver' => 'unknown', 'queues' => []];
        }

        $queues = [];
        try {
            // Fast path via Redis directly when available.
            $redis = Redis::resolve()->connection()->client();
            foreach (['default', 'emails', 'notifications', 'realtime'] as $queue) {
                try {
                    $queues[$queue] = (int) $redis->llen('queues:'.$queue);
                } catch (\Throwable) {
                    $queues[$queue] = -1; // unreadable
                }
            }
        } catch (\Throwable) {
            // Fall back to the queue connection's size() where supported.
            foreach (['default', 'emails', 'notifications', 'realtime'] as $queue) {
                try {
                    $queues[$queue] = Queue::size($queue);
                } catch (\Throwable) {
                    $queues[$queue] = -1;
                }
            }
        }

        return ['driver' => config('queue.default'), 'queues' => $queues];
    }
}
