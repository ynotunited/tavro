<?php

namespace App\Http\Middleware;

use App\Services\AdminAuditLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records every admin action into admin_audit_logs for the dev company.
 *
 * Strategy:
 *   - Mutating methods (POST/PUT/PATCH/DELETE) are always audited.
 *   - Safe methods (GET/HEAD) are audited ONLY when the route sets
 *     `$request->attributes->set('admin_audit', true)` (e.g. sensitive reads
 *     like the audit viewer or a data export).
 *   - Highly sensitive fields are redacted by AdminAuditLogger::safePayload.
 *
 * Attach to the admin route group AFTER the AuthAdmin guard so we always have
 * an authenticated admin_user_id.
 */
class AuditAdminActions
{
    /** Payload bytes above this trigger a trim by AdminAuditLogger. */
    private const MAX_PAYLOAD = 400000;

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $mutating = in_array(strtoupper($request->method()), ['POST', 'PUT', 'PATCH', 'DELETE'], true);
        $explicit = (bool) $request->attributes->get('admin_audit', false);

        if (! $mutating && ! $explicit) {
            return $response;
        }

        // Funnel requests with oversized bodies so we don't store megabytes.
        if ($request->getContent() !== '') {
            $content = $request->getContent();
            if (strlen($content) > self::MAX_PAYLOAD) {
                return $response;
            }
        }

        $admin = $request->user('admin');

        AdminAuditLogger::log(
            action: $this->actionFor($request),
            adminUserId: $admin?->id,
            request: $request,
            status: $response->getStatusCode(),
        );

        return $response;
    }

    private function actionFor(Request $request): string
    {
        return strtolower($request->method()).'.'.trim($request->path(), '/');
    }
}
