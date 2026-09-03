<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ModifierGroup extends Model
{
    use HasTenant, SoftDeletes;

    protected $fillable = ['organization_id', 'name', 'min_selections', 'max_selections', 'is_required'];

    protected function casts(): array
    {
        return ['is_required' => 'boolean'];
    }

    public function modifiers()
    {
        return $this->hasMany(Modifier::class)->orderBy('sort_order');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_modifier_group');
    }
}
