<?php

namespace App\Services;

use App\Models\Incident;
use App\Models\MaintenanceWindow;
use App\Models\StatusProviderConfig;
use App\Models\StatusSyncLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Synchronizes local incidents and maintenance windows to a hosted
 * status-page provider (Instatus / BetterStack / etc).
 *
 * The provider runs on its own infrastructure on an always-available
 * domain, so the status page stays up even when Tavro is down.
 *
 * Supported providers:
 *   - instatus    (free tier: 15 monitors, API, commercial OK)
 *   - betterstack (free tier: 10 monitors, API, email+Slack+webhook)
 */
class StatusPageService
{
    private const PROVIDERS = ['instatus', 'betterstack'];

    public function __construct(
        private string $provider,
        private string $apiKey,
        private ?string $pageId,
        private array $componentMap = [],
    ) {}

    /**
     * Build a service instance from an org's stored config.
     */
    public static function forOrganization(?int $orgId): ?self
    {
        if (!$orgId) {
            return null;
        }

        $config = StatusProviderConfig::where('organization_id', $orgId)->first();

        if (!$config || !$config->is_configured || !in_array($config->provider, self::PROVIDERS)) {
            return null;
        }

        return new self(
            provider: $config->provider,
            apiKey: $config->api_key,
            pageId: $config->page_id,
            componentMap: $config->component_map ?? [],
        );
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    // ─────────────────────────────────────────────────────────────────
    // INCIDENTS
    // ─────────────────────────────────────────────────────────────────

    public function createIncident(Incident $incident): void
    {
        $payload = $this->buildIncidentPayload($incident, 'create');

        try {
            if ($this->provider === 'instatus') {
                $response = Http::withToken($this->apiKey)
                    ->post("https://api.instatus.com/v1/{$this->pageId}/incidents", $payload);
            } else {
                $response = Http::withToken($this->apiKey)
                    ->post('https://uptime.betterstack.com/api/v2/incidents', $this->betterStackIncidentPayload($incident));
            }

            $this->recordSync(
                orgId: $incident->organization_id,
                type: Incident::class,
                id: $incident->id,
                action: 'create',
                success: $response->successful(),
                providerId: data_get($response->json(), 'data.id'),
                response: $response,
            );

            if ($response->successful() && ($providerId = data_get($response->json(), 'data.id'))) {
                $incident->update(['provider_incident_id' => $providerId]);
            }
        } catch (\Throwable $e) {
            $this->recordSync(
                orgId: $incident->organization_id,
                type: Incident::class,
                id: $incident->id,
                action: 'create',
                success: false,
                error: $e->getMessage(),
            );
            Log::channel('security')->error('Status page incident create failed', [
                'incident_id' => $incident->id,
                'error'       => $e->getMessage(),
            ]);
        }
    }

    public function updateIncident(Incident $incident, string $status, string $message): void
    {
        if (!$incident->provider_incident_id) {
            // No provider reference yet — try to create it first
            $this->createIncident($incident);
            return;
        }

        try {
            if ($this->provider === 'instatus') {
                $response = Http::withToken($this->apiKey)
                    ->post("https://api.instatus.com/v1/{$this->pageId}/incidents/{$incident->provider_incident_id}/updates", [
                        'status'  => strtoupper($status),
                        'message' => $message,
                    ]);
            } else {
                $response = Http::withToken($this->apiKey)
                    ->patch("https://uptime.betterstack.com/api/v2/incidents/{$incident->provider_incident_id}", [
                        'status'  => $this->betterStackIncidentStatus($status),
                        'summary' => $message,
                    ]);
            }

            $this->recordSync(
                orgId: $incident->organization_id,
                type: Incident::class,
                id: $incident->id,
                action: $status === 'resolved' ? 'resolve' : 'update',
                success: $response->successful(),
                providerId: $incident->provider_incident_id,
                response: $response,
            );
        } catch (\Throwable $e) {
            $this->recordSync(
                orgId: $incident->organization_id,
                type: Incident::class,
                id: $incident->id,
                action: 'update',
                success: false,
                error: $e->getMessage(),
            );
            Log::channel('security')->error('Status page incident update failed', [
                'incident_id' => $incident->id,
                'error'       => $e->getMessage(),
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // MAINTENANCE
    // ─────────────────────────────────────────────────────────────────

    public function createMaintenance(MaintenanceWindow $window): void
    {
        $payload = $this->buildMaintenancePayload($window);

        try {
            $response = Http::withToken($this->apiKey)
                ->post("https://api.instatus.com/v1/{$this->pageId}/maintenances", $payload);

            $this->recordSync(
                orgId: $window->organization_id,
                type: MaintenanceWindow::class,
                id: $window->id,
                action: 'create',
                success: $response->successful(),
                providerId: data_get($response->json(), 'data.id'),
                response: $response,
            );

            if ($response->successful() && ($providerId = data_get($response->json(), 'data.id'))) {
                $window->update(['provider_maintenance_id' => $providerId]);
            }
        } catch (\Throwable $e) {
            $this->recordSync(
                orgId: $window->organization_id,
                type: MaintenanceWindow::class,
                id: $window->id,
                action: 'create',
                success: false,
                error: $e->getMessage(),
            );
            Log::channel('security')->error('Status page maintenance create failed', [
                'maintenance_id' => $window->id,
                'error'          => $e->getMessage(),
            ]);
        }
    }

    public function updateMaintenance(MaintenanceWindow $window): void
    {
        if (!$window->provider_maintenance_id) {
            $this->createMaintenance($window);
            return;
        }

        $status = match ($window->status) {
            'scheduled'    => 'SCHEDULED',
            'in_progress'  => 'IN_PROGRESS',
            'completed'    => 'COMPLETED',
            'cancelled'    => 'CANCELLED',
            default        => 'SCHEDULED',
        };

        try {
            $response = Http::withToken($this->apiKey)
                ->patch("https://api.instatus.com/v1/{$this->pageId}/maintenances/{$window->provider_maintenance_id}", [
                    'name'   => $window->title,
                    'status' => $status,
                ]);

            $this->recordSync(
                orgId: $window->organization_id,
                type: MaintenanceWindow::class,
                id: $window->id,
                action: $window->status === 'completed' ? 'complete' : 'update',
                success: $response->successful(),
                providerId: $window->provider_maintenance_id,
                response: $response,
            );
        } catch (\Throwable $e) {
            $this->recordSync(
                orgId: $window->organization_id,
                type: MaintenanceWindow::class,
                id: $window->id,
                action: 'update',
                success: false,
                error: $e->getMessage(),
            );
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // VERIFY CONNECTION
    // ─────────────────────────────────────────────────────────────────

    public function verifyConnection(): array
    {
        $endpoint = $this->provider === 'instatus'
            ? "https://api.instatus.com/v1/{$this->pageId}/components"
            : 'https://uptime.betterstack.com/api/v2/uptime-checks';

        try {
            $response = Http::withToken($this->apiKey)->get($endpoint);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => 'Provider returned HTTP ' . $response->status() . '. Check your API key and page ID.',
                    'status'  => $response->status(),
                ];
            }

            // Pull available components so the user can map Tavro services
            $components = collect(data_get($response->json(), 'data', []))
                ->map(fn ($c) => [
                    'id'   => data_get($c, 'id'),
                    'name' => data_get($c, 'name'),
                ])
                ->values()
                ->all();

            return [
                'success'    => true,
                'message'    => 'Connected successfully.',
                'status'     => $response->status(),
                'components' => $components,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // PAYLOAD BUILDERS
    // ─────────────────────────────────────────────────────────────────

    private function buildIncidentPayload(Incident $incident, string $type): array
    {
        $components = collect($incident->impacted_components ?? [])
            ->map(fn ($name) => $this->componentMap[$name] ?? null)
            ->filter()
            ->values()
            ->all();

        return [
            'name'       => $incident->title,
            'status'     => 'INVESTIGATING',
            'message'    => $incident->summary ?? $incident->title,
            'components' => implode(',', $components),
        ];
    }

    private function buildMaintenancePayload(MaintenanceWindow $window): array
    {
        $components = collect($window->impacted_components ?? [])
            ->map(fn ($name) => $this->componentMap[$name] ?? null)
            ->filter()
            ->values()
            ->all();

        return [
            'name'       => $window->title,
            'message'    => $window->description ?? $window->title,
            'status'     => 'SCHEDULED',
            'start'      => $window->starts_at->toIso8601String(),
            'end'        => $window->ends_at->toIso8601String(),
            'components' => implode(',', $components),
        ];
    }

    private function betterStackIncidentPayload(Incident $incident): array
    {
        return [
            'name'       => $incident->title,
            'status'     => 'investigating',
            'summary'    => $incident->summary ?? $incident->title,
            'started_at' => $incident->detected_at->toIso8601String(),
            'cause'      => 'Investigation started automatically.',
            'impact'     => 'Service affected.',
            'components' => array_values(array_filter($incident->impacted_components ?? [])),
        ];
    }

    private function betterStackIncidentStatus(string $status): string
    {
        return match ($status) {
            'identified'   => 'identified',
            'monitoring'   => 'monitoring',
            'resolved'     => 'resolved',
            default        => 'investigating',
        };
    }

    // ─────────────────────────────────────────────────────────────────
    // SYNC LOGGING
    // ─────────────────────────────────────────────────────────────────

    private function recordSync(
        int $orgId,
        string $type,
        int $id,
        string $action,
        bool $success,
        ?string $providerId = null,
        ?\Illuminate\Http\Client\Response $response = null,
        ?string $error = null,
    ): void {
        try {
            StatusSyncLog::create([
                'organization_id'   => $orgId,
                'syncable_type'     => $type,
                'syncable_id'       => $id,
                'action'            => $action,
                'success'           => $success,
                'provider'          => $this->provider,
                'provider_id'       => $providerId,
                'error_message'     => $error,
                'response_payload'  => $response ? $response->json() : null,
            ]);
        } catch (\Throwable $e) {
            // Never let logging break the flow
        }
    }
}