<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasTenant;

class Floor extends Model
{
    use HasTenant;

    protected $fillable = ['organization_id', 'branch_id', 'name'];

    public function tables()
    {
        return $this->hasMany(Table::class);
    }
}
