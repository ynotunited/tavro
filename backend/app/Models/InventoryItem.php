<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'branch_id', 'name', 'sku', 'category', 'unit_of_measure',
        'cost_per_unit', 'current_stock', 'min_level', 'track_inventory',
        'low_stock_alerted',
    ];

    protected function casts(): array
    {
        return [
            'cost_per_unit' => 'decimal:4',
            'current_stock' => 'decimal:4',
            'min_level' => 'decimal:4',
            'track_inventory' => 'boolean',
            'low_stock_alerted' => 'boolean',
        ];
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function transactions()
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function openBottles()
    {
        return $this->hasMany(OpenBottle::class);
    }
}
