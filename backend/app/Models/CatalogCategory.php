<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CatalogCategory extends Model
{
    protected $guarded = [];

    public function products(): HasMany
    {
        return $this->hasMany(CatalogProduct::class, 'catalog_category_id');
    }
}