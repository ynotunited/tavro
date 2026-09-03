<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->string('billing_interval', 20)->default('monthly')->after('plan_id');
            $table->index(['organization_id', 'status'], 'subscriptions_org_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->dropIndex('subscriptions_org_status_idx');
            $table->dropColumn('billing_interval');
        });
    }
};
