<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Periodic sales-report channels (Telegram today; WhatsApp may ride the
     * same switch later). The brand owner links a Telegram chat via a pairing
     * code, then gets free scheduled sales digests — hourly / daily / weekly.
     */
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('telegram_chat_id')->nullable()->after('timezone');
            $table->string('telegram_pair_code', 8)->nullable()->index()->after('telegram_chat_id');
            $table->timestamp('telegram_pair_code_expires_at')->nullable()->after('telegram_pair_code');
            $table->boolean('sales_reports_enabled')->default(false)->after('telegram_pair_code_expires_at');
            $table->string('sales_report_frequency', 10)->default('daily')->after('sales_reports_enabled');
            $table->timestamp('sales_reports_last_sent_at')->nullable()->after('sales_report_frequency');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn([
                'telegram_chat_id',
                'telegram_pair_code',
                'telegram_pair_code_expires_at',
                'sales_reports_enabled',
                'sales_report_frequency',
                'sales_reports_last_sent_at',
            ]);
        });
    }
};
