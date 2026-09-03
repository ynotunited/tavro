<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // API keys get an encrypted signing secret for HMAC request signing.
        // Returned once at creation; never retrievable afterwards.
        Schema::table('api_keys', function (Blueprint $table) {
            $table->text('signing_secret')->nullable()->after('key_hash');
        });

        // Sanctum tokens get an encrypted signing secret so first-party POS
        // clients can sign every mutating request with their session credential.
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->text('signing_secret')->nullable()->after('token');
        });
    }

    public function down(): void
    {
        Schema::table('api_keys', function (Blueprint $table) {
            $table->dropColumn('signing_secret');
        });

        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->dropColumn('signing_secret');
        });
    }
};