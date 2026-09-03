<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Status/Maintenance/Incident system — provider-agnostic.
 *
 * Local source of truth for incidents and maintenance windows.
 * Syncs to a hosted status page provider (Instatus, BetterStack, etc.)
 * which runs on its own always-available domain.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Status page provider config (per organization) ────────────────
        Schema::create('status_provider_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('provider');           // instatus | betterstack | none
            $table->string('api_key');            // provider API token
            $table->string('page_id')->nullable(); // provider status page id
            $table->json('component_map')->nullable(); // {'api': 'abc123', 'web': 'def456'}
            $table->boolean('is_configured')->default(false);
            $table->timestamps();

            $table->unique('organization_id');
        });

        // ── Incidents ────────────────────────────────────────────────────
        // An incident is a service disruption. Each status transition
        // (DETECTED → INVESTIGATING → MONITORING → RESOLVED) is an
        // IncidentUpdate, preserving a full public timeline.
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('summary')->nullable();
            $table->string('severity');           // minor | major | critical
            $table->string('status');             // investigating | identified | monitoring | resolved
            $table->json('impacted_components')->nullable(); // ['api', 'payments']
            $table->string('provider_incident_id')->nullable()->index(); // id returned by provider
            $table->timestamp('detected_at')->default(now());
            $table->timestamp('resolved_at')->nullable();
            $table->string('resolved_by')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'created_at']);
        });

        // ── Incident updates (public timeline) ───────────────────────────
        Schema::create('incident_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained()->cascadeOnDelete();
            $table->string('status');             // investigating | identified | monitoring | resolved
            $table->text('message');
            $table->string('created_by')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['incident_id', 'created_at']);
        });

        // ── Scheduled maintenance windows ────────────────────────────────
        Schema::create('maintenance_windows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('status');             // scheduled | in_progress | completed | cancelled
            $table->json('impacted_components')->nullable();
            $table->string('provider_maintenance_id')->nullable()->index();
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'starts_at']);
        });

        // ── Sync log (what was pushed where, and whether it succeeded) ───
        Schema::create('status_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('syncable_type');      // Incident | MaintenanceWindow
            $table->unsignedBigInteger('syncable_id');
            $table->string('action');             // create | update | resolve | delete
            $table->boolean('success');
            $table->string('provider');
            $table->string('provider_id')->nullable();
            $table->string('error_message')->nullable();
            $table->json('response_payload')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['syncable_type', 'syncable_id']);
            $table->index('success');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('status_sync_logs');
        Schema::dropIfExists('maintenance_windows');
        Schema::dropIfExists('incident_updates');
        Schema::dropIfExists('incidents');
        Schema::dropIfExists('status_provider_configs');
    }
};