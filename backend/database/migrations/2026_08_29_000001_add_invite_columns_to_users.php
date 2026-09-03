<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Invite columns for the staff-invite flow.
     *
     * Invited users are created unverified with no password. The invite token
     * is delivered by email and exchanged for a password + verified email on
     * acceptance (`POST /api/v1/auth/invite/accept`).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('invite_token')->nullable()->after('status');
            $table->timestamp('invite_expires_at')->nullable()->after('invite_token');
            $table->index('invite_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['invite_token']);
            $table->dropColumn(['invite_token', 'invite_expires_at']);
        });
    }
};