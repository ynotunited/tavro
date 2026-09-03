<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CatalogProduct extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_alcoholic' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(CatalogCategory::class, 'catalog_category_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(CatalogProductVariant::class, 'catalog_product_id')->orderBy('sort_order');
    }

    public function packs(): BelongsToMany
    {
        return $this->belongsToMany(CatalogPack::class, 'catalog_pack_items', 'catalog_product_id', 'catalog_pack_id');
    }
}