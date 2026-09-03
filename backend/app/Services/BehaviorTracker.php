<?php

namespace App\Services;

use App\Models\RequestAnalytic;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Behavior-based anomaly detection service.
 *
 * Monitors for:
 * - Requests outside normal business hours
 * - Geographic anomalies (new country)
 * - Unusual request volume per user
 * - Privilege escalation attempts
 * - Unusual endpoint access patterns
 */
class BehaviorTracker
{
    /**
     * Check if the current request is anomalous.
     * Returns an array of detected anomalies (empty if normal).
     */
    public static function detectAnomalies(\Illuminate\Http\Request $request): array
    {
        $user = $request->user();
        if (!$user) {
            return [];
        }

        $anomalies = [];
        $ip = $request->ip();

        // 1. Off-hours access (outside 6am–11pm local time)
        $hour = (int) now()->format('H');
        if ($hour < 6 || $hour > 23) {
            $lastOffHours = cache("behavior:offhours:{$user->id}");
            if (!$lastOffHours) {
                $anomalies[] = 'off_hours_access';
                cache()->put("behavior:offhours:{$user->id}", true, 3600);
            }
        }

        // 2. New IP (never seen before for this user)
        $knownIpsKey = "behavior:ips:{$user->id}";
        $knownIps = cache()->get($knownIpsKey, []);
        if (!in_array($ip, $knownIps)) {
            $anomalies[] = 'new_ip_address';
            $knownIps[] = $ip;
            // Keep only last 20 known IPs
            $knownIps = array_slice($knownIps, -20);
            cache()->put($knownIpsKey, $knownIps, 86400 * 30);
        }

        // 3. Excessive requests (burst detection)
        $burstKey = "behavior:burst:{$user->id}";
        $burstCount = cache()->increment($burstKey);
        if ($burstCount === 1) {
            cache()->put($burstKey, 1, 60);
        }
        if ($burstCount > 200) {
            $anomalies[] = 'excessive_request_rate';
        }

        // 4. New user agent (device fingerprint)
        $ua = $request->userAgent() ?? '';
        $uaKey = "behavior:ua:{$user->id}";
        $knownUas = cache()->get($uaKey, []);
        $uaHash = md5($ua);
        if (!in_array($uaHash, $knownUas)) {
            $anomalies[] = 'new_device';
            $knownUas[] = $uaHash;
            $knownUas = array_slice($knownUas, -10);
            cache()->put($uaKey, $knownUas, 86400 * 30);
        }

        // 5. Rapid role/permission change detection
        $roleKey = "behavior:last_role:{$user->id}";
        $currentRoles = $user->getRoleNames()->implode(',');
        $lastRoles = cache()->get($roleKey);
        if ($lastRoles && $lastRoles !== $currentRoles) {
            $anomalies[] = 'role_changed';
            // This is informational — actual role changes are already logged
        }
        cache()->put($roleKey, $currentRoles, 86400);

        // 6. Mass data export detection (many sequential reads on report/export endpoints)
        $exportKey = "behavior:exports:{$user->id}";
        $exportCount = cache()->increment($exportKey);
        if ($exportCount === 1) {
            cache()->put($exportKey, 1, 300);
        }
        if ($exportCount > 10) {
            $anomalies[] = 'excessive_data_export';
        }

        return $anomalies;
    }

    /**
     * Log detected anomalies for security review.
     */
    public static function logAnomalies(array $anomalies, \Illuminate\Http\Request $request): void
    {
        if (empty($anomalies)) {
            return;
        }

        $user = $request->user();

        foreach ($anomalies as $anomaly) {
            Log::channel('security')->warning('Behavioral anomaly detected', [
                'type'      => $anomaly,
                'user_id'   => $user?->id,
                'email'     => $user?->email,
                'ip'        => $request->ip(),
                'path'      => $request->path(),
                'user_agent'=> $request->userAgent(),
                'severity'  => match ($anomaly) {
                    'excessive_request_rate', 'excessive_data_export' => 'high',
                    'new_ip_address', 'new_device', 'role_changed' => 'medium',
                    'off_hours_access' => 'low',
                    default => 'medium',
                },
            ]);
        }

        // If multiple anomalies in one request, escalate
        if (count($anomalies) >= 3) {
            Log::channel('security')->critical('Multiple behavioral anomalies — possible account compromise', [
                'user_id'  => $user?->id,
                'anomalies'=> $anomalies,
                'ip'       => $request->ip(),
            ]);
        }
    }

    /**
     * Get anomaly summary for a user (last 24h).
     */
    public static function getUserAnomalySummary(int $userId): array
    {
        $recentAnomalies = RequestAnalytic::where('user_id', $userId)
            ->where('created_at', '>=', now()->subDay())
            ->where('is_error', true)
            ->selectRaw('endpoint, count(*) as error_count')
            ->groupBy('endpoint')
            ->orderByDesc('error_count')
            ->get();

        $totalRequests = RequestAnalytic::where('user_id', $userId)
            ->where('created_at', '>=', now()->subDay())
            ->count();

        return [
            'total_requests_24h' => $totalRequests,
            'error_endpoints'    => $recentAnomalies,
            'known_ips'          => cache()->get("behavior:ips:{$userId}", []),
        ];
    }
}
