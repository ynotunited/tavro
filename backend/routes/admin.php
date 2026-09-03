<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminDashboardController;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/*
|--------------------------------------------------------------------------
| Admin (dev company) Panel
|--------------------------------------------------------------------------
| Lives at a non-guessable path: /<ADMIN_PANEL_PATH>/...
|
|   POST /{path}/login            → admin login (IP+email throttled)
|   POST /{path}/logout           → admin logout (authed)
|   GET  /{path}/me               → current admin (authed)
|   GET  /{path}/dashboard         → platform summary (authed)
|   GET  /{path}/audit-logs       → audit trail viewer (authed, sensitive read)
|
| Security model:
|   • Uses the dedicated 'admin' session guard (cookie-bearing), but is a JSON
|     API, so CSRF verification is intentionally omitted from this group (JSON
|     endpoints are not exploitable via simple HTML forms and the session cookie
|     ships SameSite=lax; every state change is additionally gated + audited).
|   • EVERY non-login route carries 'admin.auth' (the admin guard).
|   • Login is throttled by 'admin.throttle' (IP + email) to blunt brute-force.
|   • 'admin.audit' records every mutating action (and sensitive reads) with
|     timestamp + admin identity + IP/UA/payload.
|
| The obfuscated path is defense-in-depth only — the real protection is the
| dedicated guard, throttling, and audit trail.
*/

$path = (string) config('security.admin_path', 'control-room-9f2k');
$path = trim($path, '/');

// Session-enabled but WITHOUT the CSRF middleware: keep cookies/encryption and
// session state so the admin guard works, but don't require a CSRF token for
// the JSON admin API.
Route::prefix($path)->middleware([
    EncryptCookies::class,
    AddQueuedCookiesToResponse::class,
    StartSession::class,
    ShareErrorsFromSession::class,
    SubstituteBindings::class,
])->group(function () {

    // Login — intentionally outside admin.auth; protected by throttling only.
    Route::post('/login', [AdminAuthController::class, 'login'])
        ->middleware(['admin.throttle'])
        ->name('admin.login');

    // Everything below requires the admin guard.
    Route::middleware(['admin.auth', 'admin.audit'])->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');
        Route::get('/me', [AdminAuthController::class, 'me'])->name('admin.me');
        Route::get('/dashboard', [AdminDashboardController::class, 'summary'])->name('admin.dashboard');
        Route::get('/audit-logs', [AdminDashboardController::class, 'auditLogs'])->name('admin.audit-logs');
    });
});
