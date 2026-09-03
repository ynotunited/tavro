<?php

namespace App\Http\Controllers;

use App\Models\ApiKey;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class ApiKeyController extends Controller
{
    use ApiResponse;

    /**
     * List all API keys for the organization.
     */
    public function index(Request $request)
    {
        if (!$request->user()->hasAnyRole(['owner', 'general_manager'])) {
            return $this->error('Forbidden.', 403);
        }

        $keys = ApiKey::where('organization_id', $request->user()->organization_id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($key) => array_merge($key->toArray(), [
                'days_until_expiry' => $key->expires_at ? max(0, $key->expires_at->diffInDays(now())) : null,
            ]));

        return $this->success($keys);
    }

    /**
     * Create a new API key. The raw key is returned once; only the hash is stored.
     */
    public function store(Request $request)
    {
        if (!$request->user()->hasAnyRole(['owner', 'general_manager'])) {
            return $this->error('Forbidden.', 403);
        }

        $validated = $request->validate([
            'name'         => 'required|string|max:100',
            'scopes'       => 'nullable|array',
            'scopes.*'     => 'string|in:orders,products,inventory,analytics,reports',
            'allowed_ips'  => 'nullable|array',
            'allowed_ips.*' => 'ip',
            'expires_at'   => 'nullable|date|after:now',
        ]);

        $rawKey = ApiKey::generateKey();
        $keyHash = ApiKey::hashKey($rawKey);

        // HMAC signing secret returned once alongside the key. Required to
        // sign every mutating request the key is used for.
        $signingSecret = bin2hex(random_bytes(32));

        $apiKey = ApiKey::create([
            'organization_id' => $request->user()->organization_id,
            'user_id'         => $request->user()->id,
            'name'            => $validated['name'],
            'key_hash'        => $keyHash,
            'key_prefix'      => substr($rawKey, 0, 12) . '...',
            'signing_secret'  => Crypt::encryptString($signingSecret),
            'scopes'          => $validated['scopes'] ?? ['*'],
            'allowed_ips'     => $validated['allowed_ips'] ?? [],
            'is_active'       => true,
            'expires_at'      => $validated['expires_at'] ?? null,
        ]);

        // Return the raw key once — it can never be retrieved again
        return $this->success([
            'api_key'        => $apiKey,
            'key'            => $rawKey, // Show once only
            'signing_secret' => $signingSecret, // Show once only
        ], 'API key created. Store the key and signing secret securely — they will not be shown again.', 201);
    }

    /**
     * View API key details (without the raw key).
     */
    public function show(Request $request, ApiKey $apiKey)
    {
        if ($apiKey->organization_id !== $request->user()->organization_id) {
            return $this->error('Not found.', 404);
        }

        return $this->success($apiKey);
    }

    /**
     * Revoke (deactivate) an API key.
     */
    public function destroy(Request $request, ApiKey $apiKey)
    {
        if (!$request->user()->hasAnyRole(['owner', 'general_manager'])) {
            return $this->error('Forbidden.', 403);
        }

        if ($apiKey->organization_id !== $request->user()->organization_id) {
            return $this->error('Not found.', 404);
        }

        $apiKey->update(['is_active' => false]);

        // Invalidate cached key
        Cache::forget('api_key:' . $apiKey->key_hash);

        return $this->success(null, 'API key revoked.');
    }

    /**
     * View usage stats for an API key.
     */
    public function usage(Request $request, ApiKey $apiKey)
    {
        if ($apiKey->organization_id !== $request->user()->organization_id) {
            return $this->error('Not found.', 404);
        }

        $usage = $apiKey->usage()
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        $stats = [
            'total_requests'    => $apiKey->usage()->count(),
            'requests_today'    => $apiKey->usage()->where('created_at', '>=', now()->startOfDay())->count(),
            'error_count'       => $apiKey->usage()->where('status_code', '>=', 400)->count(),
            'avg_response_time' => $apiKey->usage()->avg('response_time_ms'),
            'last_used_at'      => $apiKey->last_used_at?->toIso8601String(),
        ];

        return $this->success([
            'stats' => $stats,
            'recent' => $usage,
        ]);
    }
}
