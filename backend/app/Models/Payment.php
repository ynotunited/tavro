<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'order_id', 'amount', 'method', 'status', 'reference',
        'idempotency_key', 'processed_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function refunds()
    {
        return $this->hasMany(Refund::class);
    }

    public function ledger()
    {
        return $this->hasMany(PaymentLedger::class)->orderBy('created_at');
    }

    /**
     * Append a new immutable ledger entry for a state transition.
     */
    public function appendLedger(
        string $status,
        ?string $idempotencyKey = null,
        ?string $reference = null,
        ?string $providerEventId = null,
        ?array $metadata = null,
        ?int $actorId = null,
    ): PaymentLedger {
        return PaymentLedger::create([
            'payment_id'        => $this->id,
            'order_id'          => $this->order_id,
            'amount'            => $this->amount,
            'method'            => $this->method,
            'status'            => $status,
            'idempotency_key'   => $idempotencyKey,
            'reference'         => $reference ?? $this->reference,
            'provider_event_id' => $providerEventId,
            'metadata'          => $metadata,
            'actor_id'          => $actorId,
            'created_at'        => now(),
        ]);
    }
}
