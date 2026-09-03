<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Global (read-only) catalog — shared reference data across all tenants.
        Schema::create('catalog_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type')->default('drink'); // drink | food | soft
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('catalog_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('brand')->nullable();
            $table->string('type')->default('drink');   // drink (alcohol) | drink (soft) | food
            $table->boolean('is_alcoholic')->default(true);
            $table->string('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index('name');
        });

        Schema::create('catalog_product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_product_id')->constrained()->cascadeOnDelete();
            $table->string('name');                 // e.g. "Big (60cl)", "Small (33cl)"
            $table->string('size_label')->nullable();
            $table->decimal('suggested_selling_price', 12, 2);
            $table->decimal('suggested_cost_price', 12, 2)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('catalog_packs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('catalog_pack_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_pack_id')->constrained()->cascadeOnDelete();
            $table->foreignId('catalog_product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_pack_items');
        Schema::dropIfExists('catalog_packs');
        Schema::dropIfExists('catalog_product_variants');
        Schema::dropIfExists('catalog_products');
        Schema::dropIfExists('catalog_categories');
    }
};