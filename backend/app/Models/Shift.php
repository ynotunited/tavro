<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    protected $fillable = [
        'branch_id', 'user_id', 'status',
        'opening_cash', 'closing_cash_actual', 'expected_cash', 'cash_variance',
        'variance_reason', 'variance_approved_by', 'approved_at',
        'opened_at', 'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'opening_cash'         => 'decimal:2',
            'closing_cash_actual'  => 'decimal:2',
            'expected_cash'        => 'decimal:2',
            'cash_variance'        => 'decimal:2',
            'opened_at'            => 'datetime',
            'closed_at'            => 'datetime',
            'approved_at'          => 'datetime',
        ];
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'variance_approved_by');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    // Sum of all completed CASH payments on orders in this shift
    public function totalCashSales()
    {
        return \App\Models\Payment::whereHas('order', fn($q) => $q->where('shift_id', $this->id))
            ->where('method', 'CASH')
            ->where('status', 'COMPLETED')
            ->sum('amount');
    }

    // Sum of all cash refunds in this shift
    public function totalCashRefunds()
    {
        return \App\Models\Refund::whereHas('payment', function ($q) {
            $q->where('method', 'CASH')
              ->whereHas('order', fn($q2) => $q2->where('shift_id', $this->id));
        })->sum('amount');
    }

    public function calculateExpectedCash(): float
    {
        return round(
            floatval($this->opening_cash) + $this->totalCashSales() - $this->totalCashRefunds(),
            2
        );
    }
}
