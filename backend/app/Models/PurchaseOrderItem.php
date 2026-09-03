<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'purchase_order_id', 'inventory_item_id', 'qty_ordered', 'qty_received', 'unit_cost',
    ];

    protected function casts(): array
    {
        return [
            'qty_ordered'  => 'decimal:4',
            'qty_received' => 'decimal:4',
            'unit_cost'    => 'decimal:4',
        ];
    }

    public function purchaseOrder() { return $this->belongsTo(PurchaseOrder::class); }
    public function inventoryItem() { return $this->belongsTo(InventoryItem::class); }
}
