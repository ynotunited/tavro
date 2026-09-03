<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('table_id')->nullable()->constrained('tables')->nullOnDelete();
            $table->foreignId('opened_by')->constrained('users');
            $table->foreignId('waiter_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('order_number')->unique(); // e.g. ORD-20240824-0001
            $table->string('status')->default('DRAFT');
            // DRAFT → OPEN → SENT → PREPARING → READY → SERVED → BILL_REQUESTED → PAYMENT_PENDING → PAID → CLOSED | VOIDED

            $table->integer('cover_count')->default(1);

            // Financials (computed at close time, snapshotted)
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('service_charge_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);

            // Discount
            $table->string('discount_type')->nullable(); // percent, flat
            $table->decimal('discount_value', 8, 2)->nullable();
            $table->foreignId('discount_approved_by')->nullable()->constrained('users')->nullOnDelete();

            // Void
            $table->string('void_reason')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();

            // Timestamps
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
