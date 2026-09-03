<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasTenant, SoftDeletes;

    protected $fillable = [
        'organization_id', 'category_id', 'name', 'sku', 'description', 'type',
        'selling_price', 'cost_price', 'is_taxable', 'has_service_charge',
        'is_available', 'track_inventory', 'image_path', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'selling_price'       => 'decimal:2',
            'cost_price'          => 'decimal:2',
            'is_taxable'          => 'boolean',
            'has_service_charge'  => 'boolean',
            'is_available'        => 'boolean',
            'track_inventory'     => 'boolean',
        ];
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order');
    }

    public function modifierGroups()
    {
        return $this->belongsToMany(ModifierGroup::class, 'product_modifier_group');
    }

    public function recipe()
    {
        return $this->hasOne(Recipe::class)->where('is_active', true)->latest();
    }
}
