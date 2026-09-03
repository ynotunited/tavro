<?php

namespace App\Http\Controllers;

use App\Models\RequestAnalytic;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    use ApiResponse;

    /**
     * Dashboard analytics — usage summary for the authenticated user's org.
     */
    public function dashboard(Request $request)
    {
        $orgId = $request->user()->organization_id;
        $days = min((int) $request->input('days', 7), 90);

        $since = now()->subDays($days);

        // Total requests
        $totalRequests = RequestAnalytic::where('organization_id', $orgId)
            ->where('created_at', '>=', $since)
            ->count();

        // Error rate
        $errors = RequestAnalytic::where('organization_id', $orgId)
            ->where('created_at', '>=', $since)
            ->where('is_error', true)
            ->count();

        // Average response time
        $avgResponseTime = RequestAnalytic::where('organization_id', $orgId)
            ->where('created_at', '>=', $since)
            ->whereNotNull('response_time_ms')
            ->avg('response_time_ms');

        // Top endpoints
        $topEndpoints = RequestAnalytic::where('organization_id', $orgId)
            ->where('created_at', '>=', $since)
            ->selectRaw('endpoint, method, count(*) as hits, avg(response_time_ms) as avg_ms')
            ->groupBy('endpoint', 'method')
            ->orderByDesc('hits')
            ->limit(10)
            ->get();

        // Daily request volume
        $dailyVolume = RequestAnalytic::where('organization_id', $orgId)
            ->where('created_at', '>=', $since)
            ->selectRaw('date(created_at) as day, count(*) as requests, sum(case when is_error then 1 else 0 end) as errors')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        // Top users
        $topUsers = RequestAnalytic::where('organization_id', $orgId)
            ->where('created_at', '>=', $since)
            ->whereNotNull('user_id')
            ->selectRaw('user_id, count(*) as requests')
            ->groupBy('user_id')
            ->orderByDesc('requests')
            ->limit(10)
            ->get()
            ->map(fn($row) => [
                'user_id'  => $row->user_id,
                'requests' => $row->requests,
            ]);

        return $this->success([
            'period_days'     => $days,
            'total_requests'  => $totalRequests,
            'total_errors'    => $errors,
            'error_rate'      => $totalRequests > 0 ? round($errors / $totalRequests * 100, 2) : 0,
            'avg_response_ms' => round($avgResponseTime ?? 0),
            'top_endpoints'   => $topEndpoints,
            'daily_volume'    => $dailyVolume,
            'top_users'       => $topUsers,
        ]);
    }

    /**
     * Slow requests report.
     */
    public function slowRequests(Request $request)
    {
        $orgId = $request->user()->organization_id;
        $threshold = (int) $request->input('threshold_ms', 3000);

        $slowRequests = RequestAnalytic::where('organization_id', $orgId)
            ->where('created_at', '>=', now()->subDays(7))
            ->where('response_time_ms', '>', $threshold)
            ->selectRaw('endpoint, method, avg(response_time_ms) as avg_ms, count(*) as occurrences')
            ->groupBy('endpoint', 'method')
            ->orderByDesc('avg_ms')
            ->limit(20)
            ->get();

        return $this->success($slowRequests);
    }

    /**
     * Error breakdown.
     */
    public function errors(Request $request)
    {
        $orgId = $request->user()->organization_id;

        $errors = RequestAnalytic::where('organization_id', $orgId)
            ->where('created_at', '>=', now()->subDays(7))
            ->where('is_error', true)
            ->selectRaw('status_code, endpoint, count(*) as count')
            ->groupBy('status_code', 'endpoint')
            ->orderByDesc('count')
            ->limit(30)
            ->get();

        return $this->success($errors);
    }
}
