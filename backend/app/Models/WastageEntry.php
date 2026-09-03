<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WastageEntry extends Model
{
    protected $fillable = [
        'branch_id', 'inventory_item_id', 'quantity', 'unit_cost', 'total_cost',
        'type', 'notes', 'requires_approval', 'recorded_by', 'approved_by', 'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity'         => 'decimal:4',
            'unit_cost'        => 'decimal:4',
            'total_cost'       => 'decimal:2',
            'requires_approval'=> 'boolean',
            'approved_at'      => 'datetime',
        ];
    }

    public function branch() { return $this->belongsTo(Branch::class); }
    public function inventoryItem() { return $this->belongsTo(InventoryItem::class); }
    public function recordedBy() { return $this->belongsTo(User::class, 'recorded_by'); }
    public function approvedBy() { return $this->belongsTo(User::class, 'approved_by'); }
}
