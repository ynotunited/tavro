<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestAnalytic extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'organization_id', 'endpoint', 'method',
        'status_code', 'response_time_ms', 'ip_address',
        'country_code', 'user_agent', 'is_error', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'is_error'   => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
