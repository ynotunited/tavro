<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiKeyUsage extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'api_key_id', 'endpoint', 'method', 'status_code',
        'response_time_ms', 'ip_address', 'user_agent', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function apiKey()
    {
        return $this->belongsTo(ApiKey::class);
    }
}
