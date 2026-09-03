<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VersionController;

/*
|--------------------------------------------------------------------------
| API Versioning Root
|--------------------------------------------------------------------------
| The base path (/api) is a discovery endpoint. Every published major version
| lives under /api/v{n} and is dispatched from its own route file:
|
|   /api/v1  →  routes/api/v1.php        (version.negotiate:1)
|   /api/v2  →  routes/api/v2.php        (future, version.negotiate:2)
|
| Version negotiation + RFC 8594 deprecation (Sunset) headers are handled by
| the NegotiateApiVersion middleware inside each versioned group.
*/

// Version discovery & negotiation entrypoint.
Route::get('/', [VersionController::class, 'index']);

// ─── v1 — current production version ─────────────────────────────────────────
Route::prefix('v1')
    ->middleware('version.negotiate:1')
    ->group(function () {
        require __DIR__ . '/api/v1.php';
    });

// ─── v2 — reserved (planned) ─────────────────────────────────────────────────
// Route::prefix('v2')
//     ->middleware('version.negotiate:2')
//     ->group(function () {
//         require __DIR__ . '/api/v2.php';
//     });