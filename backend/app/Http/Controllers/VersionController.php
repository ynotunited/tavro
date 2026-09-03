<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

/**
 * Serves GET /api — the version-discovery / negotiation endpoint.
 * A client can boot knowing nothing except the base URL and learn every
 * published version, its exact media type, lifecycle status and sunset date.
 */
class VersionController extends Controller
{
    public function index(): JsonResponse
    {
        $current  = (int) config('api_versioning.current');
        $versions = (array) config('api_versioning.versions', []);

        $published = [];

        foreach ($versions as $number => $meta) {
            $published[(int) $number] = [
                'version'           => (int) $number,
                'path'              => $meta['path'],
                'media_type'        => $meta['media_type'],
                'status'            => $meta['status'],
                'url'               => url('/api/' . $meta['path']),
                'deprecation_date'  => $meta['deprecation_date'],
                'sunset_date'       => $meta['sunset_date'],
                'migration_url'     => $meta['migration_url'] ?? null,
                'notes'             => $meta['notes'] ?? null,
            ];
        }

        return response()->json([
            'data' => [
                'current_version'    => $current,
                'default_media_type' => config('api_versioning.versions.' . $current . '.media_type'),
                'negotiation'        => [
                    'path_based'   => 'The major version is part of the URL: /api/v{n}. This is authoritative.',
                    'header'       => 'X-API-Version: <int> — must match the path version; used to make intent explicit.',
                    'media_type'   => 'Accept: application/vnd.tavro.v{n}+json — must match the path version.',
                ],
                'deprecation'        => [
                    'policy'          => 'Versions are suspended with an RFC 8594 Sunset header and Warning header on every call, at least ' . config('api_versioning.deprecation_grace_days') . ' days before removal.',
                    'documentation'   => config('api_versioning.documentation_url'),
                ],
                'versions' => array_values($published),
            ],
        ]);
    }
}