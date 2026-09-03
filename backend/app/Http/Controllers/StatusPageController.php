<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\IncidentUpdate;
use App\Models\MaintenanceWindow;
use App\Models\StatusProviderConfig;
use App\Services\StatusPageService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class StatusPageController extends Controller
{
    use ApiResponse;

    // ─────────────────────────────────────────────────────────────────
    // PROVIDER CONFIGURATION
    // ─────────────────────────────────────────────────────────────────

    /**
     * Get the current provider configuration (API key masked).
     */
    public function getConfig(Request $request)
    {
        $config = StatusProviderConfig::where('organization_id', $request->user()->organization_id)->first();

        if (!$config) {
            return $this->success([
                'provider'      => 'none',
                'is_configured' => false,
            ]);
        }

        return $this->success([
            'provider'      => $config->provider,
            'page_id'       => $config->page_id,
            'component_map' => $config->component_map,
            'is_configured' => $config->is_configured,
            'api_key_masked' => $config->api_key
                ? substr($config->api_key, 0, 4) . '••••••••' . substr($config->api_key, -4)
                : null,
        ]);
    }

    /**
     * Save provider credentials and test the connection.
     */
    public function saveConfig(Request $request)
    {
        if (!$request->user()->hasAnyRole(['owner', 'general_manager'])) {
            return $this->error('Forbidden.', 403);
        }

        $validated = $request->validate([
            'provider'       => 'required|in:instatus,betterstack',
            'api_key'        => 'required|string|max:255',
            'page_id'        => 'required_if:provider,instatus|nullable|string|max:255',
            'component_map'  => 'nullable|array',
        ]);

        $config = StatusProviderConfig::updateOrCreate(
            ['organization_id' => $request->user()->organization_id],
            [
                'provider'      => $validated['provider'],
                'api_key'       => $validated['api_key'],
                'page_id'       => $validated['page_id'] ?? null,
                'component_map' => $validated['component_map'] ?? [],
            ]
        );

        // Test the connection
        $service = new StatusPageService(
            provider: $config->provider,
            apiKey: $config->api_key,
            pageId: $config->page_id,
            componentMap: $config->component_map ?? [],
        );

        $result = $service->verifyConnection();

        if ($result['success']) {
            $config->update(['is_configured' => true]);

            return $this->success([
                'config'     => [
                    'provider'      => $config->provider,
                    'page_id'       => $config->page_id,
                    'component_map' => $config->component_map,
                    'is_configured' => true,
                ],
                'connection' => $result,
            ], 'Status page connected successfully.');
        }

        $config->update(['is_configured' => false]);

        return $this->error(
            'Connection failed: ' . ($result['message'] ?? 'Unknown error'),
            422
        );
    }

    /**
     * Disconnect the provider (blocks further syncs).
     */
    public function disconnectConfig(Request $request)
    {
        if (!$request->user()->hasAnyRole(['owner', 'general_manager'])) {
            return $this->error('Forbidden.', 403);
        }

        StatusProviderConfig::where('organization_id', $request->user()->organization_id)->delete();

        return $this->success(null, 'Status page disconnected.');
    }

    // ─────────────────────────────────────────────────────────────────
    // INCIDENTS
    // ─────────────────────────────────────────────────────────────────

    public function indexIncidents(Request $request)
    {
        $query = Incident::with('updates')
            ->where('organization_id', $request->user()->organization_id);

        if ($request->filled('status') && in_array($request->status, Incident::STATUSES)) {
            if ($request->status === 'active') {
                $query->active();
            } else {
                $query->where('status', $request->status);
            }
        }

        $incidents = $query->orderByDesc('detected_at')->paginate(25);

        return $this->success($incidents);
    }

    public function storeIncident(Request $request)
    {
        $validated = $request->validate([
            'title'               => 'required|string|max:255',
            'summary'             => 'nullable|string|max:2000',
            'severity'            => 'required|in:minor,major,critical',
            'impacted_components' => 'nullable|array',
            'impacted_components.*' => 'string',
        ]);

        $incident = Incident::create([
            'organization_id'     => $request->user()->organization_id,
            'title'               => $validated['title'],
            'summary'             => $validated['summary'] ?? null,
            'severity'            => $validated['severity'],
            'status'              => 'investigating',
            'impacted_components' => $validated['impacted_components'] ?? [],
            'detected_at'         => now(),
            'created_by'          => $request->user()->name,
        ]);

        // Initial timeline entry
        IncidentUpdate::create([
            'incident_id' => $incident->id,
            'status'      => 'investigating',
            'message'     => $validated['summary'] ?? "We're investigating this issue. Updates will follow.",
            'created_by'  => $request->user()->name,
        ]);

        // Push to provider (best effort)
        $service = StatusPageService::forOrganization($request->user()->organization_id);
        if ($service) {
            $service->createIncident($incident);
        }

        return $this->success(
            $incident->load('updates'),
            'Incident reported. Subscribers have been notified.',
            201
        );
    }

    public function updateIncident(Request $request, Incident $incident)
    {
        if ($incident->organization_id !== $request->user()->organization_id) {
            return $this->error('Not found.', 404);
        }

        $validated = $request->validate([
            'status'  => 'sometimes|in:investigating,identified,monitoring,resolved',
            'message' => 'required_with:status|string|max:2000',
        ]);

        if (isset($validated['status'])) {
            $incident->update([
                'status'      => $validated['status'],
                'resolved_at' => $validated['status'] === 'resolved' ? now() : $incident->resolved_at,
                'resolved_by' => $validated['status'] === 'resolved' ? $request->user()->name : null,
            ]);

            // Timeline entry
            IncidentUpdate::create([
                'incident_id' => $incident->id,
                'status'      => $validated['status'],
                'message'     => $validated['message'],
                'created_by'  => $request->user()->name,
            ]);

            // Push to provider
            $service = StatusPageService::forOrganization($request->user()->organization_id);
            if ($service) {
                $service->updateIncident($incident, $validated['status'], $validated['message']);
            }
        }

        return $this->success($incident->fresh()->load('updates'));
    }

    public function resolveIncident(Request $request, Incident $incident)
    {
        if ($incident->organization_id !== $request->user()->organization_id) {
            return $this->error('Not found.', 404);
        }

        $validated = $request->validate([
            'message' => 'nullable|string|max:2000',
        ]);

        $incident->update([
            'status'      => 'resolved',
            'resolved_at' => now(),
            'resolved_by' => $request->user()->name,
        ]);

        IncidentUpdate::create([
            'incident_id' => $incident->id,
            'status'      => 'resolved',
            'message'     => $validated['message'] ?? 'This incident has been resolved. All systems are operational.',
            'created_by'  => $request->user()->name,
        ]);

        $service = StatusPageService::forOrganization($request->user()->organization_id);
        if ($service) {
            $service->updateIncident(
                $incident,
                'resolved',
                $validated['message'] ?? 'This incident has been resolved. All systems are operational.'
            );
        }

        return $this->success($incident->fresh()->load('updates'), 'Incident resolved.');
    }

    // ─────────────────────────────────────────────────────────────────
    // MAINTENANCE WINDOWS
    // ─────────────────────────────────────────────────────────────────

    public function indexMaintenance(Request $request)
    {
        $query = MaintenanceWindow::where('organization_id', $request->user()->organization_id);

        if ($request->filled('status') && in_array($request->status, MaintenanceWindow::STATUSES)) {
            $query->where('status', $request->status);
        }

        $windows = $query->orderByDesc('starts_at')->paginate(25);

        return $this->success($windows);
    }

    public function storeMaintenance(Request $request)
    {
        $validated = $request->validate([
            'title'               => 'required|string|max:255',
            'description'         => 'nullable|string|max:2000',
            'starts_at'           => 'required|date|after:now',
            'ends_at'             => 'required|date|after:starts_at',
            'impacted_components' => 'nullable|array',
            'impacted_components.*' => 'string',
        ]);

        $window = MaintenanceWindow::create([
            'organization_id'     => $request->user()->organization_id,
            'title'               => $validated['title'],
            'description'         => $validated['description'] ?? null,
            'starts_at'           => $validated['starts_at'],
            'ends_at'             => $validated['ends_at'],
            'status'              => 'scheduled',
            'impacted_components' => $validated['impacted_components'] ?? [],
            'created_by'          => $request->user()->name,
        ]);

        $service = StatusPageService::forOrganization($request->user()->organization_id);
        if ($service) {
            $service->createMaintenance($window);
        }

        return $this->success($window, 'Maintenance window scheduled. Subscribers will be notified.', 201);
    }

    public function cancelMaintenance(Request $request, MaintenanceWindow $window)
    {
        if ($window->organization_id !== $request->user()->organization_id) {
            return $this->error('Not found.', 404);
        }

        $window->update(['status' => 'cancelled']);

        $service = StatusPageService::forOrganization($request->user()->organization_id);
        if ($service) {
            $service->updateMaintenance($window);
        }

        return $this->success($window->fresh(), 'Maintenance window cancelled.');
    }

    // ─────────────────────────────────────────────────────────────────
    // SYNC LOGS / AUDIT
    // ─────────────────────────────────────────────────────────────────

    public function syncLogs(Request $request)
    {
        if (!$request->user()->hasAnyRole(['owner', 'general_manager'])) {
            return $this->error('Forbidden.', 403);
        }

        $logs = \App\Models\StatusSyncLog::where('organization_id', $request->user()->organization_id)
            ->orderByDesc('created_at')
            ->paginate(30);

        return $this->success($logs);
    }
}