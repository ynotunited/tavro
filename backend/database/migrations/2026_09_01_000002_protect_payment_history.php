<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Payment history is financial evidence. Deleting an order or payment
     * must never silently delete successful payments, refunds, or ledger rows.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropForeign(['order_id']);
            $table->foreign('order_id')
                ->references('id')
                ->on('orders')
                ->restrictOnDelete();
        });

        Schema::table('refunds', function (Blueprint $table): void {
            $table->dropForeign(['payment_id']);
            $table->foreign('payment_id')
                ->references('id')
                ->on('payments')
                ->restrictOnDelete();
        });

        Schema::table('payment_ledger', function (Blueprint $table): void {
            $table->dropForeign(['payment_id']);
            $table->foreign('payment_id')
                ->references('id')
                ->on('payments')
                ->restrictOnDelete();

            $table->dropForeign(['order_id']);
            $table->foreign('order_id')
                ->references('id')
                ->on('orders')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payment_ledger', function (Blueprint $table): void {
            $table->dropForeign(['order_id']);
            $table->foreign('order_id')
                ->references('id')
                ->on('orders')
                ->cascadeOnDelete();

            $table->dropForeign(['payment_id']);
            $table->foreign('payment_id')
                ->references('id')
                ->on('payments')
                ->cascadeOnDelete();
        });

        Schema::table('refunds', function (Blueprint $table): void {
            $table->dropForeign(['payment_id']);
            $table->foreign('payment_id')
                ->references('id')
                ->on('payments')
                ->cascadeOnDelete();
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->dropForeign(['order_id']);
            $table->foreign('order_id')
                ->references('id')
                ->on('orders')
                ->cascadeOnDelete();
        });
    }
};
