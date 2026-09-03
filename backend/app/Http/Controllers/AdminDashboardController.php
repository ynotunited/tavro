<?php

namespace App\Http\Controllers;

use App\Models\AdminAuditLog;
use App\Models\AdminUser;
use App\Models\Issue;
use App\Models\Organization;
use App\Models\RequestAnalytic;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

/**
 * Protected summary endpoints for the dev-company admin dashboard.
 * Every route here is gated by the 'admin.auth' middleware (admin guard).
 */
class AdminDashboardController extends Controller
{
    use ApiResponse;

    public function summary(Request $request)
    {
        $now = now();
        $last24h = $now->copy()->subHours(24);

        $totals = RequestAnalytic::where('created_at', '>=', $last24h)
            ->selectRaw('count(*) as total, sum(case when is_error then 1 else 0 end) as errors')
            ->first();

        $errorRate = ($totals->total > 0) ? round($totals->errors / $totals->total * 100, 2) : 0.0;

        return $this->success([
            'generated_at' => $now->toIso8601String(),
            'platform' => [
                'organizations' => Organization::count(),
                'users' => User::count(),
                'unverified_users' => User::whereNull('email_verified_at')->count(),
            ],
            'traffic_24h' => [
                'requests' => (int) $totals->total,
                'errors' => (int) $totals->errors,
                'error_rate' => $errorRate,
            ],
            'support' => [
                'open_issues' => Issue::whereIn('status', ['open', 'in_progress'])->count(),
                'urgent_issues' => Issue::whereIn('status', ['open', 'in_progress'])->where('severity', 'high')->count(),
            ],
            'admins' => AdminUser::count(),
        ]);
    }

    /**
     * Protect the audit-log viewer as a sensitive read (explicitly invited
     * to be recorded by AuditAdminActions via the admin_audit attribute).
     */
    public function auditLogs(Request $request)
    {
        $request->attributes->set('admin_audit', true);

        $validated = $request->validate([
            'admin_user_id' => 'nullable|integer|exists:admin_users,id',
            'action' => 'nullable|string|max:100',
            'date_from' => 'nullable|date_format:Y-m-d',
            'date_to' => 'nullable|date_format:Y-m-d',
            'page' => 'nullable|integer|min:1',
        ]);

        $query = AdminAuditLog::with('admin:id,name,email')
            ->orderByDesc('created_at');

        if (! empty($validated['admin_user_id'])) {
            $query->where('admin_user_id', $validated['admin_user_id']);
        }
        if (! empty($validated['action'])) {
            $escaped = addcslashes($validated['action'], '%_');
            $query->where('action', 'like', '%'.$escaped.'%');
        }
        if (! empty($validated['date_from'])) {
            $query->whereDate('created_at', '>=', $validated['date_from']);
        }
        if (! empty($validated['date_to'])) {
            $query->whereDate('created_at', '<=', $validated['date_to']);
        }

        return $this->success($query->paginate(50));
    }
}
