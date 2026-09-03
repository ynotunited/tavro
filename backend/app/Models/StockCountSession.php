<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockCountSession extends Model
{
    protected $fillable = [
        'branch_id', 'type', 'status', 'category_filter',
        'started_by', 'submitted_by', 'approved_by', 'approved_at',
    ];

    protected function casts(): array
    {
        return ['approved_at' => 'datetime'];
    }

    public function branch() { return $this->belongsTo(Branch::class); }
    public function startedBy() { return $this->belongsTo(User::class, 'started_by'); }
    public function approvedBy() { return $this->belongsTo(User::class, 'approved_by'); }
    public function entries() { return $this->hasMany(StockCountEntry::class); }
}
