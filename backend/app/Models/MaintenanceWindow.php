<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceWindow extends Model
{
    protected $fillable = [
        'organization_id',
        'title',
        'description',
        'starts_at',
        'ends_at',
        'status',
        'impacted_components',
        'provider_maintenance_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'starts_at'           => 'datetime',
            'ends_at'             => 'datetime',
            'impacted_components' => 'array',
        ];
    }

    public const STATUSES = ['scheduled', 'in_progress', 'completed', 'cancelled'];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Recompute status based on timestamps. Called before sync.
     */
    public function refreshStatus(): void
    {
        if ($this->status === 'cancelled') {
            return;
        }

        if (now()->lt($this->starts_at)) {
            $this->status = 'scheduled';
        } elseif (now()->between($this->starts_at, $this->ends_at)) {
            $this->status = 'in_progress';
        } else {
            $this->status = 'completed';
        }
    }
}