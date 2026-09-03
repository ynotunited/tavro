<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasTenant, SoftDeletes;

    protected $fillable = ['organization_id', 'name', 'color', 'icon', 'sort_order'];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
