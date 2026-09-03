<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();

            $table->string('product_name');           // snapshot at time of order
            $table->string('variant_name')->nullable();
            $table->decimal('unit_price', 12, 2);     // snapshot — not affected by future price changes
            $table->integer('quantity')->default(1);
            $table->decimal('subtotal', 12, 2);

            $table->boolean('is_taxable')->default(true);
            $table->boolean('has_service_charge')->default(true);
            $table->boolean('is_complimentary')->default(false);

            $table->string('status')->default('PENDING');
            // PENDING → SENT → PREPARING → READY → SERVED | VOIDED

            $table->text('notes')->nullable();
            $table->string('void_reason')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();

            // Selected modifiers stored as JSON snapshot
            $table->json('modifiers')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
