<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'provider', 'event_id', 'event_type', 'reference',
        'payload', 'payload_hash', 'received_ip', 'attempts',
        'status', 'error', 'created_at', 'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload'      => 'array',
            'created_at'   => 'datetime',
            'processed_at' => 'datetime',
            'attempts'     => 'integer',
        ];
    }
}
