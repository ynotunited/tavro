<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatusProviderConfig extends Model
{
    protected $fillable = [
        'organization_id',
        'provider',
        'api_key',
        'page_id',
        'component_map',
        'is_configured',
    ];

    protected $hidden = ['api_key'];

    protected function casts(): array
    {
        return [
            'component_map' => 'array',
            'is_configured' => 'boolean',
        ];
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}