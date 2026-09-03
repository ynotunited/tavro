<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'product_id', 'product_variant_id',
        'product_name', 'variant_name', 'unit_price', 'quantity', 'subtotal',
        'is_taxable', 'has_service_charge', 'is_complimentary',
        'status', 'notes', 'serve_notes', 'void_reason', 'voided_by', 'voided_at', 'modifiers',
    ];

    protected function casts(): array
    {
        return [
            'unit_price'         => 'decimal:2',
            'subtotal'           => 'decimal:2',
            'is_taxable'         => 'boolean',
            'has_service_charge' => 'boolean',
            'is_complimentary'   => 'boolean',
            'modifiers'          => 'array',
            'voided_at'          => 'datetime',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
