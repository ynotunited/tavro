<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // Staff who opened it
            $table->string('status')->default('OPEN'); // OPEN, CLOSING, CLOSED
            $table->decimal('opening_cash', 12, 2)->default(0);
            $table->decimal('closing_cash_actual', 12, 2)->nullable();  // Entered at close
            $table->decimal('expected_cash', 12, 2)->nullable();         // Calculated at close
            $table->decimal('cash_variance', 12, 2)->nullable();         // actual - expected
            $table->text('variance_reason')->nullable();
            $table->foreignId('variance_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
