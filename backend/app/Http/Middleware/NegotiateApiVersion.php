<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Path-based API version enforcement + content negotiation.
 *
 * Usage: Route::prefix('v1')->middleware('version.negotiate:1')->group(...)
 *
 * Behaviour:
 *   - X-API-Version header must match the path version → else 409 + correct URL.
 *   - Accept media type must match the path version → else 406 + supported list.
 *   - Responses advertise X-API-Version and X-API-Supported-Versions.
 *   - Deprecated versions also receive RFC 8594 Sunset + Warning + Link headers,
 *     so consumers are warned on every single call until the sunset date.
 */
class NegotiateApiVersion
{
    public function handle(Request $request, Closure $next, int $pathVersion): Response
    {
        $versions = (array) config('api_versioning.versions', []);
        $current  = (int) config('api_versioning.current', 1);

        // ── 1. Explicit version header must agree with the URL path ───────────
        if ($request->hasHeader('X-API-Version')) {
            $headerVersion = (int) $request->header('X-API-Version', '');

            if ($headerVersion !== $pathVersion) {
                return $this->versionMismatch($request, $headerVersion, $current);
            }
        }

        // ── 2. Accept header media type must agree with the URL path ──────────
        $accept = $request->header('Accept', '');

        if (preg_match('/application\/vnd\.tavro\.v(\d+)\+json/', $accept, $m)) {
            $mediaVersion = (int) $m[1];

            if ($mediaVersion !== $pathVersion) {
                return $this->mediaTypeMismatch($accept, $current);
            }
        }

        // Record the effective version for downstream middleware & controllers.
        $request->attributes->set('api_version', $pathVersion);
        config(['app.current_api_version' => $pathVersion]);

        $response = $next($request);

        // ── 3. Advertise versioning on every response ─────────────────────────
        $supported = implode(',', array_keys(array_filter($versions, fn ($v) => $v['status'] !== 'retired')));

        $response->headers->set('X-API-Version', (string) $pathVersion);
        $response->headers->set('X-API-Supported-Versions', $supported);

        // ── 4. Deprecation policy (RFC 8594) ──────────────────────────────────
        $meta = $versions[$pathVersion] ?? null;

        if ($meta && ($meta['status'] ?? '') === 'deprecated') {
            $sunsetDate = $meta['sunset_date'] ?? null;
            $migration  = $meta['migration_url'] ?? config('api_versioning.documentation_url');

            if ($sunsetDate) {
                $sunset = \DateTime::createFromFormat('Y-m-d', $sunsetDate);
                $response->headers->set('Sunset', $sunset->format(DATE_RFC7231));
            }

            $response->headers->set(
                'Warning',
                '299 tavro "Version ' . $pathVersion . ' of the Tavro API is deprecated. Migrate before the sunset date. ' . $migration . '"'
            );
            $response->headers->set('Link', '<' . $migration . '>; rel="deprecation"; type="text/html"');

            Log::channel('security')->info('Request to deprecated API version', [
                'version' => $pathVersion,
                'path'    => $request->path(),
                'user'    => $request->user()?->id,
            ]);
        }

        return $response;
    }

    private function versionMismatch(Request $request, int $headerVersion, int $current): Response
    {
        $versions = (array) config('api_versioning.versions', []);

        if (!isset($versions[$headerVersion])) {
            return response()->json([
                'message'             => 'The requested API version does not exist.',
                'code'                => 'UNKNOWN_VERSION',
                'current_version'     => $current,
                'supported_versions'  => array_values(array_filter(
                    array_map(fn ($v) => $v['path'] ?? null, $versions)
                )),
            ], 409);
        }

        $correctPath = '/' . config('api_versioning.versions.' . $headerVersion . '.path');

        $response = response()->json([
            'message'         => 'X-API-Version conflicts with the version in the URL. Version is bound to the path; update your URL instead.',
            'code'            => 'VERSION_MISMATCH',
            'requested'       => $headerVersion,
            'correct_path'    => $correctPath,
            'current_version' => $current,
        ], 409);

        $response->headers->set('X-API-Version', (string) $current);
        $response->headers->set('Location', $correctPath);

        return $response;
    }

    private function mediaTypeMismatch(string $accept, int $current): Response
    {
        $published = array_filter(
            (array) config('api_versioning.versions', []),
            fn ($v) => $v['status'] !== 'retired'
        );

        $response = response()->json([
            'message'            => 'Accept media type does not match the requested URL version.',
            'code'               => 'MEDIA_TYPE_MISMATCH',
            'negotiated_accurate_version' => $current,
        ], 406);

        $response->headers->set('X-API-Version', (string) $current);

        foreach ($published as $v) {
            $response->headers->set('Link', '<' . $v['media_type'] . '>; rel="alternate"', false);
        }

        return $response;
    }
}