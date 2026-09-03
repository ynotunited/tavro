<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->after('branch_id')->constrained('suppliers')->nullOnDelete();
            $table->decimal('bottle_size_ml', 10, 2)->nullable()->after('unit_of_measure')->comment('For bar: volume per bottle/unit in ml');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropColumn(['supplier_id', 'bottle_size_ml']);
        });
    }
};
