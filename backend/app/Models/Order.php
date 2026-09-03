<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasTenant;

    protected $fillable = [
        'organization_id', 'branch_id', 'table_id', 'opened_by', 'waiter_id',
        'order_number', 'status', 'cover_count',
        'subtotal', 'tax_amount', 'service_charge_amount', 'discount_amount', 'total_amount',
        'discount_type', 'discount_value', 'discount_approved_by',
        'void_reason', 'voided_by', 'voided_at',
        'opened_at', 'sent_at', 'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal'               => 'decimal:2',
            'tax_amount'             => 'decimal:2',
            'service_charge_amount'  => 'decimal:2',
            'discount_amount'        => 'decimal:2',
            'total_amount'           => 'decimal:2',
            'discount_value'         => 'decimal:2',
            'opened_at'              => 'datetime',
            'sent_at'                => 'datetime',
            'closed_at'              => 'datetime',
            'voided_at'              => 'datetime',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────────────────

    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    public function openedBy()
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function waiter()
    {
        return $this->belongsTo(User::class, 'waiter_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class)->whereNull('voided_at');
    }

    public function allItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    // ── Computed Totals ────────────────────────────────────────────────────────

    public function recalculate(float $taxRate = 0.0, float $scRate = 0.0): void
    {
        $items = $this->items()->get();

        $subtotal = $items->sum('subtotal');

        $taxable = $items->where('is_taxable', true)->sum('subtotal');
        $taxAmount = round($taxable * $taxRate / 100, 2);

        $chargeable = $items->where('has_service_charge', true)->sum('subtotal');
        $scAmount = round($chargeable * $scRate / 100, 2);

        $discountAmount = 0;
        if ($this->discount_type === 'percent' && $this->discount_value) {
            $discountAmount = round($subtotal * $this->discount_value / 100, 2);
        } elseif ($this->discount_type === 'flat' && $this->discount_value) {
            $discountAmount = (float) $this->discount_value;
        }

        $this->update([
            'subtotal'              => $subtotal,
            'tax_amount'            => $taxAmount,
            'service_charge_amount' => $scAmount,
            'discount_amount'       => $discountAmount,
            'total_amount'          => max(0, $subtotal + $taxAmount + $scAmount - $discountAmount),
        ]);
    }

    // ── Order Number Generator ─────────────────────────────────────────────────

    public static function generateOrderNumber(int $branchId): string
    {
        $date = now()->format('Ymd');
        $count = static::whereDate('created_at', today())
            ->where('branch_id', $branchId)
            ->count() + 1;
        return "ORD-{$date}-" . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function getAmountPaidAttribute()
    {
        return $this->payments()->where('status', 'COMPLETED')->sum('amount');
    }

    public function isFullyPaid()
    {
        return $this->amount_paid >= $this->total_amount;
    }
}
