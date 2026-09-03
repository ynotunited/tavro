<?php

namespace App\Console\Commands;

use App\Models\Incident;
use App\Models\MaintenanceWindow;
use App\Models\StatusProviderConfig;
use App\Services\StatusPageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Sync local incident/maintenance state to the hosted status page.
 *
 * - Refreshes maintenance window status based on timestamps
 * - Pushes status transitions to the provider
 * - Retries items missing a provider reference
 *
 * Run via scheduler every 5 minutes:
 *   $schedule->command('status:sync')->everyFiveMinutes();
 */
class StatusSync extends Command
{
    protected $signature = 'status:sync';

    protected $description = 'Synchronize incidents and maintenance windows to the status page provider';

    public function handle(): int
    {
        $orgIds = StatusProviderConfig::where('is_configured', true)->pluck('organization_id');

        if ($orgIds->isEmpty()) {
            $this->info('No status page provider configured.');
            return self::SUCCESS;
        }

        foreach ($orgIds as $orgId) {
            $service = StatusPageService::forOrganization($orgId);
            if (!$service) {
                continue;
            }

            $this->info("Syncing organization #{$orgId} → {$service->getProvider()}");

            $this->syncIncidents($service, $orgId);
            $this->syncMaintenance($service, $orgId);
        }

        $this->info('Status sync complete.');
        return self::SUCCESS;
    }

    private function syncIncidents(StatusPageService $service, int $orgId): void
    {
        // Active incidents without a provider reference: create them
        $unpushed = Incident::where('organization_id', $orgId)
            ->active()
            ->whereNull('provider_incident_id')
            ->get();

        foreach ($unpushed as $incident) {
            $this->comment("  Creating incident #{$incident->id} on provider");
            $service->createIncident($incident);
        }
    }

    private function syncMaintenance(StatusPageService $service, int $orgId): void
    {
        // Refresh window status from timestamps, then push transitions
        $windows = MaintenanceWindow::where('organization_id', $orgId)
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->get();

        foreach ($windows as $window) {
            $before = $window->status;
            $window->refreshStatus();
            $changed = $before !== $window->status;

            if ($changed) {
                $this->comment("  Updating maintenance #{$window->id}: {$before} → {$window->status}");
                $window->timestamps = false;
                $window->save();
                $service->updateMaintenance($window->fresh());
            } elseif (!$window->provider_maintenance_id) {
                // Not pushed yet
                $this->comment("  Creating maintenance #{$window->id} on provider");
                $service->createMaintenance($window);
            }
        }
    }
}