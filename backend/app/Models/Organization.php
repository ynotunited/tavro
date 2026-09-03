<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'currency',
        'tax_percentage',
        'service_charge_percentage',
        'timezone',
        'telegram_chat_id',
        'telegram_pair_code',
        'telegram_pair_code_expires_at',
        'sales_reports_enabled',
        'sales_report_frequency',
        'sales_reports_last_sent_at',
    ];

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
