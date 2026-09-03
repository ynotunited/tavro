<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_count_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_count_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->decimal('expected_qty', 12, 4)->default(0);
            $table->decimal('actual_qty', 12, 4)->default(0);
            $table->decimal('variance_qty', 12, 4)->storedAs('actual_qty - expected_qty');
            $table->decimal('variance_value', 12, 2)->default(0); // calculated at approval time
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_count_entries');
    }
};
