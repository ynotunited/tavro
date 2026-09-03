<?php

namespace App\Http\Middleware;

use App\Models\RequestAnalytic;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Analytics middleware — records every request for usage dashboards.
 * Applies to all authenticated API routes.
 */
class RecordAnalytics
{
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);

        $response = $next($request);

        try {
            $responseTimeMs = (int) ((microtime(true) - $startTime) * 1000);
            $user = $request->user();

            RequestAnalytic::create([
                'user_id'         => $user?->id,
                'organization_id' => $user?->organization_id,
                'endpoint'        => $this->normaliseEndpoint($request->path()),
                'method'          => $request->method(),
                'status_code'     => $response->getStatusCode(),
                'response_time_ms'=> $responseTimeMs,
                'ip_address'      => $request->ip(),
                'user_agent'      => $request->userAgent(),
                'is_error'        => $response->getStatusCode() >= 400,
            ]);

            // Track slow requests (> 3 seconds)
            if ($responseTimeMs > 3000) {
                Log::channel('security')->warning('Slow API request', [
                    'endpoint'         => $request->path(),
                    'response_time_ms' => $responseTimeMs,
                    'user_id'          => $user?->id,
                ]);
            }

            // Track error rate per endpoint (rolling 5-minute window)
            if ($response->getStatusCode() >= 500) {
                $errorKey = 'analytics:errors:' . $this->normaliseEndpoint($request->path());
                $errorCount = Cache::increment($errorKey);
                if ($errorCount === 1) {
                    Cache::put($errorKey, 1, 300);
                }

                if ($errorCount >= 10) {
                    Log::channel('security')->critical('High error rate on endpoint', [
                        'endpoint'    => $request->path(),
                        'error_count' => $errorCount,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            // Never break the response due to analytics
        }

        return $response;
    }

    /**
     * Normalise endpoints by replacing IDs with placeholders.
     * /api/v1/orders/123 → /api/v1/orders/{id}
     */
    private function normaliseEndpoint(string $path): string
    {
        return preg_replace_callback('/\d+/', function ($matches) {
            return '{id}';
        }, $path);
    }
}
