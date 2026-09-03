<?php

namespace App\Http\Middleware;

use App\Services\AdminAuditLogger;
use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Throttles admin login attempts to blunt brute-force / credential-stuffing
 * attacks. Limits are keyed by BOTH the remote IP and the submitted email, so
 * a single source cannot loop through many addresses and a single account
 * cannot be hammered from many sources.
 *
 * The limit/decay come from security.admin_login. Excess attempts get a 429
 * with a Retry-After header.
 */
class ThrottleAdminLogin
{
    public function __construct(private readonly RateLimiter $limiter) {}

    public function handle(Request $request, Closure $next): Response
    {
        $max = (int) config('security.admin_login.max_attempts', 5);
        $decay = (int) config('security.admin_login.decay_minutes', 15) * 60;

        $email = strtolower(trim((string) $request->input('email', '')));
        $keyIp = 'admin_login:'.$request->ip();
        $keyEmail = $email !== '' ? 'admin_login:'.$email : '';

        $hit = fn ($key) => $this->limiter->hit($key, $decay);

        if ($this->limiter->tooManyAttempts($keyIp, $max) || ($keyEmail !== '' && $this->limiter->tooManyAttempts($keyEmail, $max))) {
            $retryAfter = max(
                $this->limiter->availableIn($keyIp),
                $keyEmail !== '' ? $this->limiter->availableIn($keyEmail) : 0,
            );

            AdminAuditLogger::log(
                action: 'admin.login.throttled',
                request: $request,
                status: 429,
                payload: ['email' => $email !== '' ? md5($email) : null, 'reason' => 'rate_limited'],
            );

            return response()->json([
                'message' => 'Too many login attempts. Please try again later.',
                'retry_after' => $retryAfter,
            ], 429)->header('Retry-After', (string) max($retryAfter, 1));
        }

        $hit($keyIp);
        if ($keyEmail !== '') {
            $hit($keyEmail);
        }

        $response = $next($request);

        // If credentials were wrong, keep the buckets incremented and audit it.
        if ($response->getStatusCode() === 401) {
            AdminAuditLogger::log(
                action: 'admin.login.failed',
                request: $request,
                status: 401,
                payload: ['email' => $email !== '' ? md5($email) : null],
            );
        }

        return $response;
    }
}
