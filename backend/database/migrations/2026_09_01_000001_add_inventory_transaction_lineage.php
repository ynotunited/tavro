<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table): void {
            // Idempotency keys are application-generated strings, not UUIDs.
            $table->string('idempotency_key', 191)->nullable()->unique()->after('id');
            // inventory_transactions.id is a BIGINT, so the self-reference must use the same type.
            $table->unsignedBigInteger('reverses_transaction_id')->nullable()->after('idempotency_key');
            $table->string('movement_type', 40)->nullable()->after('type');
            $table->index(['inventory_item_id', 'created_at'], 'inventory_tx_item_created_idx');
            $table->index(['reference_type', 'reference_id'], 'inventory_tx_reference_idx');
            $table->index('reverses_transaction_id', 'inventory_tx_reversal_idx');
        });

        Schema::table('inventory_transactions', function (Blueprint $table): void {
            $table->foreign('reverses_transaction_id', 'inventory_tx_reversal_fk')
                ->references('id')
                ->on('inventory_transactions')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table): void {
            $table->dropForeign('inventory_tx_reversal_fk');
            $table->dropIndex('inventory_tx_reversal_idx');
            $table->dropIndex('inventory_tx_item_created_idx');
            $table->dropIndex('inventory_tx_reference_idx');
            $table->dropUnique(['idempotency_key']);
            $table->dropColumn([
                'idempotency_key',
                'reverses_transaction_id',
                'movement_type',
            ]);
        });
    }
};
