<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogger
{
    /**
     * Log an immutable audit event.
     *
     * @param string $action      e.g. "order.created", "payment.voided"
     * @param string|null $entityType  e.g. "Order", "Payment"
     * @param int|null $entityId
     * @param array|null $previous    State before the change
     * @param array|null $new         State after the change
     * @param Request|null $request   For capturing IP / user agent
     */
    public static function log(
        string $action,
        ?string $entityType = null,
        ?int $entityId = null,
        ?array $previous = null,
        ?array $new = null,
        ?Request $request = null
    ): void {
        $user = $request?->user();

        AuditLog::create([
            'actor_id'        => $user?->id,
            'organization_id' => $user?->organization_id,
            'branch_id'       => $user?->branch_id,
            'action'          => $action,
            'entity_type'     => $entityType,
            'entity_id'       => $entityId,
            'previous_state'  => $previous,
            'new_state'       => $new,
            'ip_address'      => $request?->ip(),
            'user_agent'      => $request?->userAgent(),
            'created_at'      => now(),
        ]);
    }
}
