<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_packs', function (Blueprint $table) {
            $table->string('type')->default('drink')->after('slug'); // drink | food
        });
    }

    public function down(): void
    {
        Schema::table('catalog_packs', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
