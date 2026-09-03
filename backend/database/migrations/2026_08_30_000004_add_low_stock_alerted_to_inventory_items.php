<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Marks inventory items that already fired a low-stock alert so the owner
     * isn't spammed every time a bottle is served below the minimum. Reset
     * automatically by a restock that brings the quantity back above min_level.
     */
    public function up(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->boolean('low_stock_alerted')->default(false)->after('track_inventory');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropColumn('low_stock_alerted');
        });
    }
};
