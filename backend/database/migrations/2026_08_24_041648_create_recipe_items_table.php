<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipe_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            // Will reference inventory_items once Phase 5 inventory is built
            $table->string('ingredient_name'); // Denormalized for now
            $table->decimal('quantity', 12, 4);
            $table->string('unit')->default('unit'); // unit, ml, g, kg, cl, oz
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_items');
    }
};
