<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Organization;
use App\Models\Payment;
use Carbon\Carbon;

/**
 * Compose the compact sales digest that brand owners receive on Telegram.
 * Shared by the scheduled command, the "send test" button and — later — any
 * other free channel (WhatsApp has a paid API, so Telegram wins for now).
 */
class SalesReportBuilder
{
    public function frequencyWindow(Organization $org): array
    {
        $tz = $org->timezone ?: 'Africa/Lagos';
        $now = Carbon::now($tz);

        return match ($org->sales_report_frequency) {
            'hourly' => [$now->subHour(), 'the last hour'],
            'daily' => [$now->startOfDay(), 'today'],
            'weekly' => [$now->startOfWeek(), 'this week'],
            default => [$now->startOfDay(), 'today'],
        };
    }

    public function isDue(Organization $org): bool
    {
        $last = $org->sales_reports_last_sent_at;
        if ($last === null) {
            return true;
        }

        $tz = $org->timezone ?: 'Africa/Lagos';

        return match ($org->sales_report_frequency) {
            'hourly' => $last->lte(Carbon::now($tz)->subMinutes(50)),
            'weekly' => $last->lte(Carbon::now($tz)->subDays(7)),
            default => $last->lt(Carbon::today($tz)),
        };
    }

    public function build(Organization $org, ?string $periodOverride = null): string
    {
        [$since, $period] = $this->frequencyWindow($org);
        if ($periodOverride !== null) {
            $period = $periodOverride;
        }

        $orders = Order::where('organization_id', $org->id)
            ->where('created_at', '>=', $since->copy()->setTimezone('UTC'))
            ->whereNull('voided_at')
            ->get();

        $gross = (float) $orders->sum('subtotal');
        $discounts = (float) $orders->sum('discount_amount');
        $net = round(max(0, $gross - $discounts), 2);

        $orderIds = $orders->pluck('id');
        $items = OrderItem::whereIn('order_id', $orderIds)->get();
        $itemQty = $items->sum('quantity');
        $collected = (float) Payment::whereIn('order_id', $orderIds)
            ->where('status', 'COMPLETED')
            ->sum('amount');

        $top = $items
            ->groupBy('product_name')
            ->map(fn ($rows) => $rows->sum('quantity'))
            ->sortDesc()
            ->take(3)
            ->map(fn ($qty, $name) => "{$name} ({$qty})")
            ->values()
            ->implode(' · ');

        $currency = $org->currency ?: 'NGN';
        $money = fn ($n) => $currency.' '.number_format((float) $n, 2);

        $lines = [
            '📊 '.$org->name,
            '📈 Sales '.$period.':',
            '💰 '.$money($net).($discounts > 0 ? ' net ('.$money($gross).' gross, '.$money($discounts).' off)' : ''),
            '🧾 '.count($orders).' orders · '.$itemQty.' items',
            '💵 Collected: '.$money($collected),
        ];

        if ($top !== '') {
            $lines[] = '🔝 '.$top;
        }

        return implode(PHP_EOL, $lines);
    }
}
