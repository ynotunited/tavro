<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use App\Models\ApiKeyUsage;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * API Gateway enforcement middleware.
 *
 * Validates API keys for external/third-party consumers.
 * Applies to routes that need external API access.
 *
 * Usage:
 *   Route::middleware('api.gateway')->group(function () { ... });
 */
class ValidateApiKey
{
    public function handle(Request $request, Closure $next)
    {
        $rawKey = $request->header('X-API-Key');

        if (empty($rawKey)) {
            return response()->json([
                'message' => 'API key is required. Pass it via the X-API-Key header.',
            ], 401);
        }

        // Look up by hash (constant-time comparison at the DB layer)
        $keyHash = ApiKey::hashKey($rawKey);

        $apiKey = Cache::remember("api_key:{$keyHash}", 300, function () use ($keyHash) {
            return ApiKey::where('key_hash', $keyHash)
                ->with('organization')
                ->first();
        });

        if (!$apiKey) {
            Log::channel('security')->warning('Invalid API key used', [
                'ip'        => $request->ip(),
                'key_prefix' => substr($rawKey, 0, 12),
                'path'      => $request->path(),
            ]);

            return response()->json(['message' => 'Invalid API key.'], 401);
        }

        if (!$apiKey->is_active) {
            return response()->json(['message' => 'This API key has been deactivated.'], 403);
        }

        if ($apiKey->isExpired()) {
            return response()->json(['message' => 'This API key has expired.'], 403);
        }

        if (!$apiKey->isIpAllowed($request->ip())) {
            Log::channel('security')->warning('API key used from unauthorized IP', [
                'api_key_id' => $apiKey->id,
                'ip'         => $request->ip(),
                'allowed'    => $apiKey->allowed_ips,
            ]);

            return response()->json(['message' => 'Your IP is not authorized to use this API key.'], 403);
        }

        // Update last used timestamp (throttled to avoid write amplification)
        $lastUpdateKey = 'api_key_last_update:' . $apiKey->id;
        if (!Cache::has($lastUpdateKey)) {
            $apiKey->update(['last_used_at' => now()]);
            Cache::put($lastUpdateKey, true, 60);
        }

        // Attach to request for downstream controllers
        $request->merge(['_api_key' => $apiKey]);
        $request->attributes->set('api_key', $apiKey);

        $response = $next($request);

        // Log usage asynchronously (fire and forget)
        $this->logUsage($apiKey, $request, $response);

        return $response;
    }

    private function logUsage(ApiKey $apiKey, Request $request, $response): void
    {
        try {
            ApiKeyUsage::create([
                'api_key_id'       => $apiKey->id,
                'endpoint'         => $request->path(),
                'method'           => $request->method(),
                'status_code'      => $response->getStatusCode(),
                'response_time_ms' => (int) ((microtime(true) - LARAVEL_START) * 1000),
                'ip_address'       => $request->ip(),
                'user_agent'       => $request->userAgent(),
            ]);
        } catch (\Throwable $e) {
            // Don't let analytics failure break the request
            Log::warning('Failed to log API key usage', ['error' => $e->getMessage()]);
        }
    }
}
