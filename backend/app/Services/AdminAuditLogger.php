<?php

namespace App\Services;

use App\Models\AdminAuditLog;
use Illuminate\Http\Request;

/**
 * Writes immutable audit entries for admin (dev company) actions.
 *
 * Unlike the tenant AuditLogger, admin audit rows are never tied to a tenant,
 * they track the acting AdminUser. The AuditAdminActions middleware uses this;
 * controllers can also call it directly for events outside a request (e.g.
 * login failure).
 */
class AdminAuditLogger
{
    /**
     * @param  string  $action  e.g. "admin.login", "issue.resolved"
     * @param  string|null  $entityType  e.g. "Issue", "AdminUser"
     * @param  Request|null  $request  for method/path/payload/ip/ua
     * @param  array|null  $payload  extra data when no request is available
     */
    public static function log(
        string $action,
        ?int $adminUserId = null,
        ?string $entityType = null,
        ?int $entityId = null,
        ?Request $request = null,
        ?int $status = null,
        ?array $payload = null,
    ): void {
        try {
            AdminAuditLog::create([
                'admin_user_id' => $adminUserId,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'method' => $request?->method(),
                'path' => $request?->path(),
                'request_payload' => $payload ?? self::safePayload($request),
                'status_code' => $status,
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Never break the request because auditing failed.
            report($e);
        }
    }

    /**
     * A bounded, redacted snapshot of JSON request input (avoids storing
     * secrets or huge bodies in the audit trail).
     */
    private static function safePayload(?Request $request): ?array
    {
        if (! $request) {
            return null;
        }

        $sensitive = ['password', 'password_confirmation', 'token', 'secret', 'api_key', 'signing_secret'];

        $data = $request->isMethod('GET') ? $request->query() : $request->post();

        foreach ($sensitive as $key) {
            if (array_key_exists($key, $data)) {
                $data[$key] = '[REDACTED]';
            }
        }

        return self::limit($data);
    }

    private static function limit(array $data, int $depth = 0): array
    {
        if ($depth >= 4) {
            return [];
        }

        $out = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $out[$key] = self::limit($value, $depth + 1);
            } elseif (is_string($value) && strlen($value) > 500) {
                $out[$key] = substr($value, 0, 500).'…';
            } else {
                $out[$key] = $value;
            }
        }

        return $out;
    }
}
