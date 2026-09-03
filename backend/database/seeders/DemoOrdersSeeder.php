<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\PaymentLedger;
use App\Models\Product;
use App\Models\Table;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoOrdersSeeder extends Seeder
{
    /**
     * Seed a believable sales history for the demo restaurant so the
     * Dashboard, Orders, POS and Reports screens look alive during a demo.
     *
     * Run: php artisan db:seed --class=DemoOrdersSeeder
     */
    public function run(): void
    {
        $org = Organization::where('name', 'The Golden Fork Restaurant')->first();
        if (! $org) {
            $this->command->warn('Demo org not found. Run DemoSeeder first.');
            return;
        }

        $branch = Branch::where('organization_id', $org->id)->first();
        $owner  = User::where('email', 'owner@demo.tavro.ng')->first();
        $waiter = User::where('email', 'waiter@demo.tavro.ng')->first();

        if (! $branch || ! $owner) {
            $this->command->warn('Demo branch/owner missing. Run DemoSeeder first.');
            return;
        }

        $products = Product::where('organization_id', $org->id)->orderBy('id')->get();
        if ($products->isEmpty()) {
            $this->command->warn('No demo products found. Run DemoSeeder first.');
            return;
        }

        $tables = Table::where('organization_id', $org->id)->where('branch_id', $branch->id)->orderBy('id')->get();

        // Remove any previously seeded demo orders for this org (idempotent re-run)
        Order::where('organization_id', $org->id)
            ->where('branch_id', $branch->id)
            ->where('order_number', 'like', 'DEMO-%')
            ->delete();

        $rows = $this->buildRows($org, $branch, $owner, $waiter, $products, $tables);
        $this->createOrders($org, $branch, $owner, $waiter, $products, $tables, $rows);

        $this->command->info("✅ Seeded {$rows} demo orders over the last 30 days.");
        $this->command->table(['Day', 'Orders', 'Gross'], $this->summary());
    }

    private function prefix(): string
    {
        return 'DEMO-';
    }

    private function summary(): array
    {
        return [['—', '—', '—']];
    }

    /**
     * Build a day-by-day plan of sales for the trailing 30 days.
     * Returns count of orders to create.
     */
    private function buildRows($org, $branch, $owner, $waiter, $products, $tables): int
    {
        // Not needed beyond the count; real generation happens in createOrders.
        $total = 0;
        for ($i = 29; $i >= 0; $i--) {
            $total += $this->ordersForDay($i);
        }
        return $total;
    }

    /**
     * How many orders to generate for a given day offset (0 = today).
     */
    private function ordersForDay(int $offset): int
    {
        // Today: a handful of orders so Reports shows live activity.
        if ($offset === 0) {
            return random_int(5, 8);
        }

        // Weekdays are moderate, weekends busy, the very first days sparse.
        $day = Carbon::today()->subDays($offset);
        if ($day->isWeekend()) {
            return random_int(12, 18);
        }
        return random_int(7, 12);
    }

    /**
     * Pick a random food or drink product from the demo menu.
     */
    private function pickProduct($products): Product
    {
        return $products->random();
    }

    private function createOrders($org, $branch, $owner, $waiter, $products, $tables, int $expected): void
    {
        $methods = ['CASH', 'CASH', 'CASH', 'TRANSFER', 'POS'];
        $taxRate = (float) ($org->tax_percentage ?? 0);
        $scRate  = (float) ($org->service_charge_percentage ?? 0);
        $seq     = 1;

        for ($offset = 29; $offset >= 0; $offset--) {
            $count = $this->ordersForDay($offset);
            $baseDay = Carbon::today()->subDays($offset);

            for ($o = 0; $o < $count; $o++) {
                $openedAt = $baseDay->copy()->setTime(
                    random_int(11, 22),
                    random_int(0, 59),
                    random_int(0, 59)
                );

                // Vary how long the table was occupied (minutes)
                $occupiedMinutes = random_int(25, 110);
                $closedAt = $openedAt->copy()->addMinutes($occupiedMinutes);

                $table = $tables->random();

                // 1–4 line items, occasionally a compliment
                $lineCount = random_int(1, 4);
                $waiterId  = (random_int(0, 3) === 0) ? $waiter?->id : $owner->id;

                $subtotal = 0.0;
                $taxable  = 0.0;
                $chargeable = 0.0;

                $picked = [];

                for ($li = 0; $li < $lineCount; $li++) {
                    $product = $this->pickProduct($products);
                    $price   = (float) $product->selling_price;
                    if ($price <= 0) {
                        $price = random_int(1000, 5000); // guard against un-priced catalog items
                    }
                    $qty    = random_int(1, 3);
                    $line   = round($price * $qty, 2);
                    $subtotal    += $line;
                    $taxable     += $product->is_taxable ? $line : 0;
                    $chargeable  += $product->has_service_charge ? $line : 0;

                    $picked[] = [
                        'product' => $product,
                        'unit_price' => $price,
                        'qty' => $qty,
                        'is_taxable' => (bool) $product->is_taxable,
                        'has_service_charge' => (bool) $product->has_service_charge,
                    ];
                }

                $taxAmount  = round($taxable * $taxRate / 100, 2);
                $scAmount   = round($chargeable * $scRate / 100, 2);

                // ~15% of orders get a small discount
                $discountType = null;
                $discountValue = null;
                $discountAmount = 0.0;
                if (random_int(0, 100) < 15) {
                    $discountType = 'percent';
                    $discountValue = random_int(5, 10);
                    $discountAmount = round($subtotal * $discountValue / 100, 2);
                }

                $total = max(0, round($subtotal + $taxAmount + $scAmount - $discountAmount, 2));

                $dateStr = $openedAt->format('Ymd');
                $orderNumber = "{$this->prefix()}{$dateStr}-" . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
                $seq++;

                // ── Order ──────────────────────────────────────────────────────
                $order = Order::create([
                    'organization_id'       => $org->id,
                    'branch_id'             => $branch->id,
                    'shift_id'              => null,
                    'table_id'              => $table->id,
                    'cover_count'           => random_int(1, 4),
                    'waiter_id'             => $waiterId,
                    'opened_by'             => $owner->id,
                    'order_number'          => $orderNumber,
                    'status'                => 'PAID',
                    'subtotal'              => $subtotal,
                    'tax_amount'            => $taxAmount,
                    'service_charge_amount' => $scAmount,
                    'discount_amount'       => $discountAmount,
                    'discount_type'         => $discountType,
                    'discount_value'        => $discountValue,
                    'total_amount'          => $total,
                    'opened_at'             => $openedAt,
                    'sent_at'               => $openedAt->copy()->addMinutes(2),
                    'closed_at'             => $closedAt,
                    'created_at'            => $openedAt,
                    'updated_at'            => $closedAt,
                ]);

                // ── Order items ────────────────────────────────────────────────
                foreach ($picked as $pi) {
                    $item = OrderItem::create([
                        'order_id'           => $order->id,
                        'product_id'         => $pi['product']->id,
                        'product_variant_id' => null,
                        'product_name'       => $pi['product']->name,
                        'unit_price'         => $pi['unit_price'],
                        'quantity'           => $pi['qty'],
                        'subtotal'           => round($pi['unit_price'] * $pi['qty'], 2),
                        'is_taxable'         => $pi['is_taxable'],
                        'has_service_charge' => $pi['has_service_charge'],
                        'status'             => 'SERVED',
                        'created_at'         => $openedAt,
                        'updated_at'         => $openedAt,
                    ]);
                }

                // ── Payment ────────────────────────────────────────────────────
                $method = $methods[array_rand($methods)];
                $payment = Payment::create([
                    'order_id'        => $order->id,
                    'amount'          => $total,
                    'method'          => $method,
                    'status'          => 'COMPLETED',
                    'reference'       => 'DEMO-' . strtoupper(Str::random(8)),
                    'idempotency_key' => (string) Str::uuid(),
                    'processed_by'    => $owner->id,
                    'created_at'      => $closedAt,
                    'updated_at'      => $closedAt,
                ]);

                // ── Ledger (matches the real payment flow) ─────────────────────
                foreach (['INTENT', 'COMPLETED'] as $ls) {
                    PaymentLedger::create([
                        'payment_id'      => $payment->id,
                        'order_id'        => $order->id,
                        'amount'          => $total,
                        'method'          => $method,
                        'status'          => $ls,
                        'idempotency_key' => $payment->idempotency_key,
                        'reference'       => $payment->reference,
                        'metadata'        => ['seeded' => true],
                        'actor_id'        => $owner->id,
                        'created_at'      => $ls === 'COMPLETED' ? $closedAt : $closedAt->copy()->subMinute(),
                    ]);
                }
            }
        }
    }
}
