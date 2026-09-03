<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'plan_id',
        'billing_interval',
        'status',
        'trial_ends_at',
        'current_period_start',
        'current_period_end',
        'paystack_subscription_code',
        'paystack_email_token',
        'paystack_customer_code',
        'next_payment_date',
        'autorenew',
        'paystack_status',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at'        => 'datetime',
            'current_period_start' => 'datetime',
            'current_period_end'   => 'datetime',
            'next_payment_date'    => 'datetime',
            'autorenew'            => 'boolean',
        ];
    }

    public function intervalMonths(): int
    {
        return $this->billing_interval === 'yearly' ? 12 : 1;
    }

    public function advanceToNextPeriod(): void
    {
        $start = $this->current_period_end ? $this->current_period_end->copy() : now();
        $end = $start->copy()->addMonths($this->intervalMonths());

        $this->update([
            'status' => 'active',
            'current_period_start' => $start,
            'current_period_end' => $end,
            'next_payment_date' => $end,
            'autorenew' => true,
        ]);
    }

    public function markPastDue(?string $paystackStatus = 'past_due'): void
    {
        $this->update([
            'status' => 'past_due',
            'paystack_status' => $paystackStatus,
            'autorenew' => false,
        ]);
    }

    public function markCanceled(?string $paystackStatus = 'cancelled'): void
    {
        $this->update([
            'status' => 'canceled',
            'paystack_status' => $paystackStatus,
            'autorenew' => false,
        ]);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function isActive(): bool
    {
        if (in_array($this->status, ['active', 'trialing'], true)) {
            return true;
        }

        if ($this->status === 'past_due' && $this->current_period_end) {
            return now()->lte($this->current_period_end->copy()->addDays(7));
        }

        return false;
    }

    public function daysRemaining(): int
    {
        if (!$this->current_period_end) {
            return 0;
        }

        return max(0, (int) now()->diffInDays($this->current_period_end, false));
    }

    public function graceDaysRemaining(): int
    {
        if ($this->status !== 'past_due' || !$this->current_period_end) {
            return 0;
        }

        return max(0, (int) now()->diffInDays(
            $this->current_period_end->copy()->addDays(7),
            false
        ));
    }

    public function checkAndTransitionStatus(): void
    {
        if ($this->status === 'active' && $this->current_period_end && now()->gt($this->current_period_end)) {
            $this->update(['status' => 'past_due']);

            Log::channel('security')->info('Subscription transitioned to past_due', [
                'org_id' => $this->organization_id,
                'plan_id' => $this->plan_id,
                'period_end' => $this->current_period_end->toIso8601String(),
                'transitioned_at' => now()->toIso8601String(),
            ]);
        }

        if ($this->status === 'past_due' && $this->current_period_end) {
            $graceEnd = $this->current_period_end->copy()->addDays(7);

            if (now()->gt($graceEnd)) {
                $this->update(['status' => 'canceled']);

                Log::channel('security')->warning('Subscription canceled after grace period', [
                    'org_id' => $this->organization_id,
                    'plan_id' => $this->plan_id,
                    'grace_end' => $graceEnd->toIso8601String(),
                ]);
            }
        }
    }
}
