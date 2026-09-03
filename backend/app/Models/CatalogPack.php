<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CatalogPack extends Model
{
    protected $guarded = [];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(CatalogProduct::class, 'catalog_pack_items', 'catalog_pack_id', 'catalog_product_id')
            ->withPivot('sort_order')
            ->orderBy('catalog_pack_items.sort_order');
    }
}