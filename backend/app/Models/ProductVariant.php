<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use SoftDeletes;

    protected $fillable = ['product_id', 'name', 'sku', 'selling_price', 'cost_price', 'is_available', 'sort_order'];

    protected function casts(): array
    {
        return [
            'selling_price' => 'decimal:2',
            'cost_price'    => 'decimal:2',
            'is_available'  => 'boolean',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function recipe()
    {
        return $this->hasOne(Recipe::class, 'product_variant_id')->where('is_active', true)->latest();
    }
}
