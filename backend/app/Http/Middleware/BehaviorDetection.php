<?php

namespace App\Http\Middleware;

use App\Services\BehaviorTracker;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Behavior-based detection middleware.
 *
 * Monitors requests for anomalous patterns and logs them for security review.
 * Does NOT block requests by default (observability-first approach).
 * Blocks only when 3+ anomalies detected simultaneously (possible compromise).
 */
class BehaviorDetection
{
    public function handle(Request $request, Closure $next)
    {
        $anomalies = BehaviorTracker::detectAnomalies($request);

        // Log all anomalies
        BehaviorTracker::logAnomalies($anomalies, $request);

        // Block only if multiple simultaneous anomalies suggest active compromise
        if (count($anomalies) >= 3) {
            Log::channel('security')->critical('Request blocked — multiple behavioral anomalies', [
                'user_id'   => $request->user()?->id,
                'anomalies' => $anomalies,
                'ip'        => $request->ip(),
                'path'      => $request->path(),
            ]);

            return response()->json([
                'message' => 'Unusual activity detected. Please verify your identity.',
                'code'    => 'ANOMALY_DETECTED',
            ], 403);
        }

        return $next($request);
    }
}
