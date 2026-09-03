<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentLedger extends Model
{
    protected $table = 'payment_ledger';

    public $timestamps = false;

    protected $fillable = [
        'payment_id', 'order_id', 'amount', 'method', 'status',
        'idempotency_key', 'reference', 'provider_event_id',
        'metadata', 'actor_id', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'amount'    => 'decimal:2',
            'metadata'  => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
