<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->string('type'); // purchase, sale, adjustment, wastage, transfer, opening_balance
            $table->decimal('quantity_change', 12, 4); // positive or negative
            $table->decimal('current_quantity', 12, 4); // snapshot after transaction
            $table->string('reference_type')->nullable(); // Model class if polymorphic
            $table->unsignedBigInteger('reference_id')->nullable(); // Order ID, Purchase ID, etc
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // Who did it
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
