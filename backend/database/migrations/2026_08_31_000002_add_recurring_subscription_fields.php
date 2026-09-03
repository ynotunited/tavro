<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add fields that support true recurring (auto-renewing) subscriptions
     * powered by Paystack. The existing paystack_subscription_code already
     * holds the Paystack subscription code; we add the customer, scheduling,
     * and renewal-state fields needed for webhook-driven auto-renewal.
     */
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('paystack_customer_code')->nullable()->after('paystack_email_token');
            $table->timestamp('next_payment_date')->nullable()->after('paystack_customer_code');
            $table->boolean('autorenew')->default(false)->after('next_payment_date');
            $table->string('paystack_status')->nullable()->after('autorenew');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['paystack_customer_code', 'next_payment_date', 'autorenew', 'paystack_status']);
        });
    }
};
