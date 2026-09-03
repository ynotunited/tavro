<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Incident extends Model
{
    protected $fillable = [
        'organization_id',
        'title',
        'summary',
        'severity',
        'status',
        'impacted_components',
        'provider_incident_id',
        'detected_at',
        'resolved_at',
        'resolved_by',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'impacted_components' => 'array',
            'detected_at'         => 'datetime',
            'resolved_at'         => 'datetime',
        ];
    }

    public const SEVERITIES = ['minor', 'major', 'critical'];
    public const STATUSES = ['investigating', 'identified', 'monitoring', 'resolved'];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function updates()
    {
        return $this->hasMany(IncidentUpdate::class);
    }

    public function isActive(): bool
    {
        return $this->status !== 'resolved';
    }

    public function scopeActive($query)
    {
        return $query->where('status', '!=', 'resolved');
    }
}