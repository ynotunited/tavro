<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    // Immutable — no updates ever
    public $timestamps = false;

    protected $fillable = [
        'actor_id', 'organization_id', 'branch_id',
        'action', 'entity_type', 'entity_id',
        'previous_state', 'new_state',
        'ip_address', 'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'previous_state' => 'array',
            'new_state'      => 'array',
            'created_at'     => 'datetime',
        ];
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
