<?php

use App\Exceptions\InsufficientStockException;
use App\Http\Middleware\AuditAdminActions;
use App\Http\Middleware\AuthAdmin;
use App\Http\Middleware\BehaviorDetection;
use App\Http\Middleware\DetectSuspiciousTraffic;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\ForceHttps;
use App\Http\Middleware\NegotiateApiVersion;
use App\Http\Middleware\PromptInjectionGuard;
use App\Http\Middleware\RecordAnalytics;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\TenantScopeMiddleware;
use App\Http\Middleware\ThrottleAdminLogin;
use App\Http\Middleware\ValidateApiKey;
use App\Http\Middleware\VerifyOpsToken;
use App\Http\Middleware\VerifyRequestSignature;
use App\Http\Middleware\VerifyWebhook;
use App\Models\Issue;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Sentry\Laravel\Integration;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function (): void {
            // Admin (dev company) panel — loaded OUTSIDE the web group so its
            // routes use only the explicit session middleware declared in
            // admin.php (no CSRF; a JSON API). Gated by the 'admin' guard.
            require __DIR__.'/../routes/admin.php';
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust proxies for HTTPS detection behind load balancers
        $middleware->trustProxies(at: [
            '*',
        ]);

        // Global middleware — runs on every request (web + API + CLI)
        $middleware->append([
            SecurityHeaders::class,
            ForceHttps::class,
        ]);

        // API middleware stack — runs on every API request
        $middleware->api(append: [
            TenantScopeMiddleware::class,
            DetectSuspiciousTraffic::class,
            RecordAnalytics::class,
        ]);

        // Custom middleware aliases
        $middleware->alias([
            'prompt.guard' => PromptInjectionGuard::class,
            'api.gateway' => ValidateApiKey::class,
            'behavior.detect' => BehaviorDetection::class,
            'ensure.user.active' => EnsureUserIsActive::class,
            'signature.verify' => VerifyRequestSignature::class,
            'version.negotiate' => NegotiateApiVersion::class,
            'webhook.verify' => VerifyWebhook::class,
            'ops.token' => VerifyOpsToken::class,
            'admin.auth' => AuthAdmin::class,
            'admin.audit' => AuditAdminActions::class,
            'admin.throttle' => ThrottleAdminLogin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        Integration::handles($exceptions);

        // Insufficient stock → 422
        $exceptions->renderable(function (InsufficientStockException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        });

        // Validation errors → consistent 422 format
        $exceptions->renderable(function (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        });

        // Model not found → 404
        $exceptions->renderable(function (ModelNotFoundException $e) {
            $model = class_basename($e->getModel());

            return response()->json([
                'message' => "{$model} not found.",
            ], 404);
        });

        // Throttle exceptions → 429 with retry info
        $exceptions->renderable(function (TooManyRequestsHttpException $e) {
            return response()->json([
                'message' => 'Too many requests. Please slow down.',
                'code' => 'RATE_LIMITED',
            ], 429);
        });

        // Payment provider errors → 502
        $exceptions->renderable(function (ConnectionException $e) {
            if (str_contains($e->getMessage(), 'paystack') || str_contains($e->getMessage(), 'flutterwave')) {
                return response()->json([
                    'message' => 'Payment provider is temporarily unavailable. Please try again.',
                    'code' => 'PROVIDER_UNAVAILABLE',
                ], 502);
            }

            return null;
        });

        // Log all unhandled exceptions to security channel with full context
        $exceptions->renderable(function (Throwable $e, Request $request) {
            // Let Laravel's built-in handlers drive everything they already
            // understand: 401 (auth), 403 (authorization), HTTP exceptions such
            // as 404/405, and short-circuit exceptions like throttle (429).
            // Converting any of these to a 500 breaks clients and pollutes logs.
            if ($e instanceof AuthenticationException
                || $e instanceof AuthorizationException
                || $e instanceof HttpResponseException
                || $e instanceof HttpExceptionInterface) {
                return null;
            }

            $context = [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'method' => $request->method(),
                'path' => $request->path(),
                'ip' => $request->ip(),
                'user_id' => $request->user()?->id,
                'org_id' => $request->user()?->organization_id,
            ];

            Log::channel('security')->error('Unhandled exception', $context);

            // Auto-create issue for critical errors
            if ($e instanceof PDOException || $e instanceof ErrorException) {
                try {
                    Issue::reportFromException(
                        $e,
                        $request->user()?->organization_id,
                        $request->user()?->id,
                        $context,
                    );
                } catch (Throwable $issueException) {
                    // Don't let issue creation failure propagate
                }
            }

            return response()->json([
                'message' => 'An unexpected error occurred. Please try again.',
                'code' => 'SERVER_ERROR',
            ], 500);
        });
    })->create();
