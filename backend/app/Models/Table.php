<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasTenant;

class Table extends Model
{
    use HasTenant;

    protected $fillable = [
        'organization_id', 'branch_id', 'floor_id', 'name', 
        'capacity', 'status', 'pos_x', 'pos_y', 'shape'
    ];

    public function floor()
    {
        return $this->belongsTo(Floor::class);
    }
}
