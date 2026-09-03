<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Internal ops/monitoring token guard.
 *
 * Protects /ops/* endpoints used by the dev company's own tooling (uptime
 * monitors, the integer summary, manual inspection). This is intentionally a
 * separate secret from tenant API keys so monitoring cannot double as a tenant
 * credential.
 *
 * The token is compared in constant time and is never logged in full.
 */
class VerifyOpsToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if ($token === '') {
            $token = (string) $request->header('X-Ops-Token');
        }

        $expected = (string) config('security.ops_token');

        if ($expected === '' || ! hash_equals($expected, (string) $token)) {
            Log::channel('security')->warning('Ops token rejected', [
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);

            return response()->json([
                'message' => 'Unauthorized.',
            ], 401);
        }

        return $next($request);
    }
}
