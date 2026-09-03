<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class TrackLoginFailures
{
    /**
     * Track failed login attempts and increment the failure counter.
     *
     * This middleware is applied AFTER the login controller runs.
     * It checks if the login was unsuccessful and increments the
     * failure counter in the cache. The `login` rate limiter in
     * AppServiceProvider reads this counter to apply progressive
     * throttling.
     *
     * After 5 failures: slow to 1 attempt per 15 minutes.
     * After 20 failures: lock for 1 hour and alert.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only track POST /auth/login
        if ($request->is('api/v1/auth/login') && $request->isMethod('POST')) {
            $status = $response->getStatusCode();
            $email = strtolower($request->input('email', ''));
            $key = 'login_fails:' . ($email ?: $request->ip());

            if ($status === 401 || $status === 403) {
                $failures = Cache::increment($key);

                if ($failures === 1) {
                    Cache::put($key, 1, 3600);
                }

                Log::channel('auth')->warning('Login failure tracked', [
                    'email'    => $email,
                    'ip'       => $request->ip(),
                    'failures' => $failures,
                    'status'   => $status,
                ]);

                // Alert on 20+ failures — likely brute force
                if ($failures >= 20) {
                    Log::channel('security')->critical('Brute force detected', [
                        'email'    => $email,
                        'ip'       => $request->ip(),
                        'failures' => $failures,
                    ]);
                }
            } elseif ($status === 200 || $status === 201) {
                // Successful login — reset failure counter
                Cache::forget($key);
            }
        }

        return $response;
    }
}
