<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiKey extends Model
{
    protected $fillable = [
        'organization_id', 'user_id', 'name', 'key_hash', 'key_prefix',
        'scopes', 'allowed_ips', 'is_active', 'last_used_at', 'expires_at',
    ];

    protected $hidden = ['key_hash'];

    protected function casts(): array
    {
        return [
            'scopes'       => 'array',
            'allowed_ips'  => 'array',
            'is_active'    => 'boolean',
            'last_used_at' => 'datetime',
            'expires_at'   => 'datetime',
        ];
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function usage()
    {
        return $this->hasMany(ApiKeyUsage::class);
    }

    /**
     * Generate a new API key and return the raw value.
     * The raw key is shown once; only the hash is stored.
     */
    public static function generateKey(): string
    {
        return 'tav_sk_' . bin2hex(random_bytes(32));
    }

    /**
     * Hash a raw API key for storage/comparison.
     */
    public static function hashKey(string $key): string
    {
        return hash('sha256', $key);
    }

    /**
     * Check if the key has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if the IP is allowed.
     */
    public function isIpAllowed(string $ip): bool
    {
        if (empty($this->allowed_ips)) {
            return true; // No IP restriction
        }

        return in_array($ip, $this->allowed_ips);
    }

    /**
     * Check if the key has the required scope.
     */
    public function hasScope(string $scope): bool
    {
        if (empty($this->scopes)) {
            return true; // No scope restriction
        }

        return in_array($scope, $this->scopes) || in_array('*', $this->scopes);
    }
}
