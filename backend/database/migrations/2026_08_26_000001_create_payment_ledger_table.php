<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('method');
            $table->string('status'); // INTENT, AUTHORIZED, CAPTURED, COMPLETED, FAILED, REFUNDED, REVERSED
            $table->string('idempotency_key')->nullable();
            $table->string('reference')->nullable();
            $table->string('provider_event_id')->nullable();
            $table->json('metadata')->nullable(); // provider response, error details, etc.
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            // Immutable — no updated_at column

            $table->unique(['payment_id', 'status', 'idempotency_key'], 'ledger_idempotency');
            $table->index('order_id');
            $table->index('idempotency_key');
            $table->index('provider_event_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_ledger');
    }
};
