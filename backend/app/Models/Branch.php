<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\HasTenant;

class Branch extends Model
{
    use HasFactory, HasTenant;

    protected $fillable = [
        'organization_id',
        'name',
        'address',
        'phone',
        'timezone',
        'operating_hours',
    ];

    protected function casts(): array
    {
        return [
            'operating_hours' => 'array',
        ];
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
