<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Request Signing (HMAC)
    |--------------------------------------------------------------------------
    | Every mutating API call (POST/PUT/PATCH/DELETE) must carry an HMAC-SHA256
    | signature over a canonical representation of the request. This binds the
    | request to its body, URL, timestamp and a unique nonce, proving:
    |
    |   1. Integrity  - the payload was not altered in transit
    |   2. Authenticity - the caller possesses the signing secret
    |   3. Replay protection - a captured request cannot be replayed
    |
    | Header layout:
    |   X-Timestamp   Unix seconds the request was signed (freshness window)
    |   X-Nonce       Unique UUID per request (replay detection)
    |   X-Signature   Hex HMAC-SHA256 of the canonical request
    |   X-Api-Key     Present ONLY for API-key clients (gateway consumers)
    |
    | Canonical request (sign and verify identically):
    |   METHOD\n{path}\n{sha256(rawBody)}\n{timestamp}\n{nonce}
    */

    // Maximum age (seconds) between signing and server receipt.
    'window' => 300,

    // Extra seconds a nonce is remembered beyond the signing window.
    'nonce_buffer' => 60,

    // HMAC algorithm used.
    'algorithm' => 'sha256',

    // Redis prefix for replay-protection nonces.
    'nonce_prefix' => 'request_nonce:',

    // Mutating HTTP verbs that must carry a signature.
    'methods' => ['POST', 'PUT', 'PATCH', 'DELETE'],

    /*
    |--------------------------------------------------------------------------
    | Internal Ops / Monitoring token
    |--------------------------------------------------------------------------
    | Shared secret used by the dev company's internal monitoring tools
    | (health checks, uptime alerts, the ops summary endpoint). Passed as a
    | bearer token to the `/ops/*` endpoints. Keep it long and random.
    | Guarded by the `ops.token` middleware.
    */

    'ops_token' => env('OPS_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Admin (dev company) panel
    |--------------------------------------------------------------------------
    | The admin panel lives at a non-guessable path, enforced by the 'admin'
    | session guard, with credential-stuffing protection on the login route.
    | The path is a secret better than nothing, but the REAL protection is the
    | dedicated guard + rate limiting + audit trail — not obfuscation alone.
    */

    'admin_path' => env('ADMIN_PANEL_PATH', 'control-room-9f2k'),

    // Login rate limit: attempts + lockout minutes per IP and per email.
    'admin_login' => [
        'max_attempts' => 5,
        'decay_minutes' => 15,
    ],
];
