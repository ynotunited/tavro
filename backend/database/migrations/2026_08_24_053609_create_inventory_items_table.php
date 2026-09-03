<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('sku')->nullable();
            $table->string('category')->nullable(); // e.g. Spirits, Mixers, Produce
            $table->string('unit_of_measure'); // e.g. ml, g, piece
            $table->decimal('cost_per_unit', 12, 4)->default(0);
            $table->decimal('current_stock', 12, 4)->default(0);
            $table->decimal('min_level', 12, 4)->default(0);
            $table->boolean('track_inventory')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
