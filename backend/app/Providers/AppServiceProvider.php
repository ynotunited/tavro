<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    private function configureRateLimiting(): void
    {
        // ── Login: dual-key (identity + IP) to prevent distributed brute force ──
        // Keyed by BOTH email AND IP so a single attacker can't rotate IPs,
        // and a single IP can't rotate emails.
        RateLimiter::for('login', function (Request $request) {
            $email = strtolower($request->input('email', ''));
            $ip = $request->ip();

            // Composite key: login:{email}:{ip}
            $identityKey = 'login:'.$email.':'.$ip;

            $failures = (int) cache()->get('login_fails:'.$email, 0);

            if ($failures >= 5) {
                return Limit::perMinutes(15, 1)->by($identityKey)->response(function () {
                    return response()->json([
                        'message' => 'Too many login attempts. Please try again in 15 minutes.',
                        'code' => 'LOGIN_RATE_LIMITED',
                    ], 429);
                });
            }

            return Limit::perMinutes(5, 5)->by($identityKey)->response(function () {
                return response()->json([
                    'message' => 'Too many login attempts. Please try again later.',
                    'code' => 'LOGIN_RATE_LIMITED',
                ], 429);
            });
        });

        // ── Password reset: identity-based (email) + IP fallback ─────────────
        RateLimiter::for('password.reset', function (Request $request) {
            $email = strtolower($request->input('email', ''));
            $key = $email ? 'pwd_reset:'.$email : 'pwd_reset:ip:'.$request->ip();

            return Limit::perHour(3, 1)->by($key)->response(function () {
                return response()->json([
                    'message' => 'Too many password reset requests. Please try again later.',
                    'code' => 'PASSWORD_RESET_RATE_LIMITED',
                ], 429);
            });
        });

        // ── General API: identity-first (user ID), IP as fallback ────────────
        RateLimiter::for('api', function (Request $request) {
            $key = $request->user()?->id
                ? 'api:user:'.$request->user()->id
                : 'api:ip:'.$request->ip();

            return Limit::perMinute(120)->by($key)->response(function () {
                return response()->json([
                    'message' => 'Rate limit exceeded. Please slow down.',
                    'code' => 'API_RATE_LIMITED',
                ], 429);
            });
        });

        // ── Organization creation: IP + fingerprint ───────────────────────────
        RateLimiter::for('organizations', function (Request $request) {
            return Limit::perHour(3, 1)->by('org:'.$request->ip())->response(function () {
                return response()->json([
                    'message' => 'Too many organization signups from this IP.',
                    'code' => 'ORG_RATE_LIMITED',
                ], 429);
            });
        });

        // ── User creation: org-scoped ────────────────────────────────────────
        RateLimiter::for('users.create', function (Request $request) {
            return Limit::perHour(10, 1)->by('users:'.$request->user()?->organization_id)->response(function () {
                return response()->json([
                    'message' => 'Too many user invitations. Please try again later.',
                    'code' => 'USER_CREATE_RATE_LIMITED',
                ], 429);
            });
        });

        // ── Subscription actions: org-scoped ─────────────────────────────────
        RateLimiter::for('subscriptions', function (Request $request) {
            return Limit::perHour(10, 1)->by('subs:'.$request->user()?->organization_id)->response(function () {
                return response()->json([
                    'message' => 'Too many subscription requests.',
                    'code' => 'SUBSCRIPTION_RATE_LIMITED',
                ], 429);
            });
        });

        // ── Webhooks: IP-scoped (external) ───────────────────────────────────
        RateLimiter::for('webhooks', function (Request $request) {
            return Limit::perMinute(30, 1)->by('webhook:'.$request->ip())->response(function () {
                return response()->json([
                    'message' => 'Too many webhook requests.',
                    'code' => 'WEBHOOK_RATE_LIMITED',
                ], 429);
            });
        });

        // ── Heavy operations: user-scoped ────────────────────────────────────
        RateLimiter::for('heavy', function (Request $request) {
            return Limit::perMinute(20, 1)->by('heavy:'.$request->user()?->id)->response(function () {
                return response()->json([
                    'message' => 'Too many requests. Please wait before generating another report.',
                    'code' => 'HEAVY_RATE_LIMITED',
                ], 429);
            });
        });

        // ── Email verification: user-scoped ──────────────────────────────────
        RateLimiter::for('verification', function (Request $request) {
            return Limit::perHour(3, 1)->by('verify:'.$request->user()?->id)->response(function () {
                return response()->json([
                    'message' => 'Too many verification emails. Please check your inbox.',
                    'code' => 'VERIFICATION_RATE_LIMITED',
                ], 429);
            });
        });

        // ── Strict: IP-scoped ────────────────────────────────────────────────
        RateLimiter::for('strict', function (Request $request) {
            return Limit::perMinute(30, 1)->by('strict:'.$request->ip())->response(function () {
                return response()->json([
                    'message' => 'Rate limit exceeded.',
                    'code' => 'STRICT_RATE_LIMITED',
                ], 429);
            });
        });

        // ── Ops / monitoring: IP-scoped (dev company tooling) ────────────────
        RateLimiter::for('ops', function (Request $request) {
            return Limit::perMinute(60, 1)->by('ops:'.$request->ip())->response(function () {
                return response()->json([
                    'message' => 'Ops endpoint rate limit exceeded.',
                    'code' => 'OPS_RATE_LIMITED',
                ], 429);
            });
        });

        // ── Paid external APIs: user-scoped + configurable per provider ──────
        $paidProviders = ['openai', 'anthropic', 'google_ai', 'deepseek'];
        foreach ($paidProviders as $provider) {
            RateLimiter::for("paid_api.{$provider}", function (Request $request) use ($provider) {
                $limit = (int) config("services.{$provider}.rate_limit.requests_per_minute", 20);
                $window = (int) config("services.{$provider}.rate_limit.window_minutes", 1);

                return Limit::perMinutes($window, $limit)
                    ->by("paid_api:{$provider}:".$request->user()?->id)
                    ->response(function () use ($provider) {
                        return response()->json([
                            'message' => "Rate limit exceeded for {$provider}. Please try again later.",
                            'code' => 'PAID_API_RATE_LIMITED',
                        ], 429);
                    });
            });
        }

        // ── LLM endpoints: strict per-user ──────────────────────────────────
        RateLimiter::for('llm', function (Request $request) {
            return Limit::perMinute(10, 1)->by('llm:'.$request->user()?->id)->response(function () {
                return response()->json([
                    'message' => 'AI request limit exceeded. Please wait before sending another request.',
                    'code' => 'LLM_RATE_LIMITED',
                ], 429);
            });
        });

        // ── OTP: identity-based (email) + IP dual-key ────────────────────────
        RateLimiter::for('otp', function (Request $request) {
            $email = strtolower($request->input('email', ''));
            $ip = $request->ip();

            return Limit::perMinutes(15, 5)->by("otp:{$email}:{$ip}")->response(function () {
                return response()->json([
                    'message' => 'Too many OTP requests. Please try again later.',
                    'code' => 'OTP_RATE_LIMITED',
                ], 429);
            });
        });

        // ── OTP verify: strict identity-based (email only) ───────────────────
        RateLimiter::for('otp.verify', function (Request $request) {
            $email = strtolower($request->input('email', ''));

            return Limit::perMinutes(15, 5)->by("otp_verify:{$email}")->response(function () {
                return response()->json([
                    'message' => 'Too many OTP verification attempts. Your account has been temporarily locked.',
                    'code' => 'OTP_VERIFY_LOCKED',
                ], 429);
            });
        });
    }
}
