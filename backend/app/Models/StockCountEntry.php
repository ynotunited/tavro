<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockCountEntry extends Model
{
    protected $fillable = [
        'stock_count_session_id', 'inventory_item_id',
        'expected_qty', 'actual_qty', 'variance_value',
    ];

    protected function casts(): array
    {
        return [
            'expected_qty'   => 'decimal:4',
            'actual_qty'     => 'decimal:4',
            'variance_qty'   => 'decimal:4',
            'variance_value' => 'decimal:2',
        ];
    }

    public function session() { return $this->belongsTo(StockCountSession::class, 'stock_count_session_id'); }
    public function inventoryItem() { return $this->belongsTo(InventoryItem::class); }
}
