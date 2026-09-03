<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecipeItem extends Model
{
    protected $fillable = ['recipe_id', 'ingredient_name', 'quantity', 'unit'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:4'];
    }

    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }
}
