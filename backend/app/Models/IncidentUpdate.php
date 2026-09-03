<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncidentUpdate extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'incident_id',
        'status',
        'message',
        'created_by',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function incident()
    {
        return $this->belongsTo(Incident::class);
    }
}