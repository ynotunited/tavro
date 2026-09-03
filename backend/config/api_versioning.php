<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API Versioning & Deprecation Policy
    |--------------------------------------------------------------------------
    |
    | Versioning is PATH-BASED: the major version lives in the URL (/api/v1).
    | Negotiation supplements the path:
    |
    |   * X-API-Version: <int>        — MUST match the path version. If a client
    |     targets a different supported version it gets 409 with the correct
    |     URL; the header exists to make intent explicit, not to override the URL.
    |
    |   * Accept: application/vnd.tavro.v{n}+json — content negotiation. If the
    |     media type names a version other than the path version the server
    |     answers 406 with the list of supported versions.
    |
    | Discovery: GET /api returns every published version, its media type,
    |    lifecycle status and (for deprecated versions) the sunset date.
    |
    | Deprecation lifecycle per version:
    |    current -> deprecated (Sunset + Warning headers start appearing)
    |            -> retired (404 with Sunset…Gone guidance)
    |  Clients SHOULD migrate within the grace period printed in their Sunset header.
    */

    // The version served by /api/v{N} today.
    'current' => 1,

    // Root of the custom media type used for negotiation.
    'media_type_root' => 'application/vnd.tavro',

    // Grace period granted between deprecation announcement and sunset (days).
    'deprecation_grace_days' => 365,

    // Human-readable documentation for the policy & migration guides.
    'documentation_url' => env('API_DOCS_URL', 'https://docs.tavro.ng/api/deprecations'),

    /*
    | Lifecycle states: current | deprecated | retired
    | For deprecated versions supply sunset_date (YYYY-MM-DD) — it becomes the
    | RFC 8594 Sunset response header the moment the version is deprecated.
    */
    'versions' => [

        1 => [
            'path'             => 'v1',
            'status'           => 'current',
            'media_type'       => 'application/vnd.tavro.v1+json',
            'deprecation_date' => null,
            'sunset_date'      => null,
            'migration_url'    => null,
            'notes'            => 'Initial production release.',
        ],

        // Reserved for the next major. Not routable until a v2 group is added.
        2 => [
            'path'             => 'v2',
            'status'           => 'planned',
            'media_type'       => 'application/vnd.tavro.v2+json',
            'deprecation_date' => null,
            'sunset_date'      => null,
            'migration_url'    => null,
            'notes'            => 'Planned. Breaking changes will be announced here with >= 365 days notice.',
        ],
    ],
];