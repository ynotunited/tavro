<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InventoryTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id', 'inventory_item_id', 'type', 'movement_type',
        'quantity_change', 'current_quantity', 'reference_type', 'reference_id',
        'idempotency_key', 'reverses_transaction_id', 'user_id', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity_change' => 'decimal:4',
            'current_quantity' => 'decimal:4',
            'reverses_transaction_id' => 'integer',
        ];
    }

    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function inventoryItem(): BelongsTo { return $this->belongsTo(InventoryItem::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function reverses(): BelongsTo { return $this->belongsTo(self::class, 'reverses_transaction_id'); }
    public function reversal(): HasOne { return $this->hasOne(self::class, 'reverses_transaction_id'); }
    public function reference(): MorphTo { return $this->morphTo(); }
}
