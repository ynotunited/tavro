<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatusSyncLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'organization_id',
        'syncable_type',
        'syncable_id',
        'action',
        'success',
        'provider',
        'provider_id',
        'error_message',
        'response_payload',
    ];

    protected function casts(): array
    {
        return [
            'success'          => 'boolean',
            'response_payload' => 'array',
        ];
    }
}