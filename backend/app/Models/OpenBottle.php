<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpenBottle extends Model
{
    protected $fillable = [
        'branch_id', 'inventory_item_id', 'current_volume', 'opened_at', 'empty_at', 'opened_by'
    ];

    protected function casts(): array
    {
        return [
            'current_volume' => 'decimal:4',
            'opened_at' => 'datetime',
            'empty_at' => 'datetime',
        ];
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function openedBy()
    {
        return $this->belongsTo(User::class, 'opened_by');
    }
}
