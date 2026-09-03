<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Log;

/**
 * Bottle-level stock consumption for the bar.
 *
 * Drinks are served per bottle (no tap/pour telemetry), so each bar line that
 * reaches SERVED consumes exactly that many units from matching bar inventory.
 * The inventory item is resolved by variant SKU first, then by name against the
 * served drink; when no match exists the sale proceeds normally and nothing is
 * consumed (a bar that hasn't set up inventory keeps working).
 */
class BarStockService
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly TelegramService $telegram,
    ) {}

    /**
     * Consume one or more inventory units for a single served drink line.
     */
    public function consumeServedItem(OrderItem $item, ?int $userId = null): void
    {
        $inventoryItem = $this->resolveFor($item);

        if (! $inventoryItem) {
            Log::info('[bar-stock] no inventory match for served drink', [
                'order_item_id' => $item->id,
                'product' => $item->product_name,
                'variant' => $item->variant_name,
            ]);

            return;
        }

        try {
            $this->inventory->recordTransaction(
                $inventoryItem,
                'sale',
                -1 * (float) $item->quantity,
                $item,
                $userId,
                'Bottle served on order '.optional($item->order)->order_number,
            );

            $this->maybeAlertLowStock($inventoryItem);
        } catch (InsufficientStockException $e) {
            // The drink was already handed over — record the shortfall, don't fail the serve.
            Log::warning('[bar-stock] served more than on hand', [
                'inventory_item_id' => $inventoryItem->id,
                'item' => $inventoryItem->name,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * After a whole order is marked SERVED, process every freshly-served drink.
     */
    public function consumeServedItems(Order $order, int $userId): int
    {
        $served = $order->items()
            ->whereHas('product', fn ($q) => $q->whereIn('type', ['drink', 'cocktail', 'bottle', 'shot']))
            ->where('status', 'SERVED')
            ->get();

        foreach ($served as $item) {
            $this->consumeServedItem($item, $userId);
        }

        return $served->count();
    }

    /**
     * Low-stock alert to the owner's free Telegram channel. Fires once per item
     * until a restock brings the quantity back above min_level.
     */
    public function maybeAlertLowStock(InventoryItem $inventoryItem): void
    {
        if (! $inventoryItem->track_inventory || $inventoryItem->low_stock_alerted) {
            return;
        }

        if ((float) $inventoryItem->current_stock > (float) $inventoryItem->min_level) {
            return;
        }

        $branch = $inventoryItem->branch;
        $org = $branch?->organization;

        if (! $org || ! $org->telegram_chat_id) {
            return;
        }

        $sent = $this->telegram->sendMessage(
            $org->telegram_chat_id,
            '⚠️ Low stock — '.$org->name.PHP_EOL
            .optional($branch)->name.': '.$inventoryItem->name
            .' ('.rtrim(rtrim(number_format((float) $inventoryItem->current_stock, 4), '0'), '.')
            .' left, min '.rtrim(rtrim(number_format((float) $inventoryItem->min_level, 4), '0'), '.').')',
        );

        if ($sent) {
            $inventoryItem->forceFill(['low_stock_alerted' => true])->save();
        }
    }

    /**
     * Resolve a servable drink line to a physical inventory item.
     */
    private function resolveFor(OrderItem $item): ?InventoryItem
    {
        $branchId = optional($item->order)->branch_id;

        if (! $branchId) {
            return null;
        }

        $variant = $item->variant;

        if ($variant?->sku) {
            $bySku = InventoryItem::where('branch_id', $branchId)
                ->where('sku', $variant->sku)
                ->first();

            if ($bySku) {
                return $bySku;
            }
        }

        $candidates = collect([
            $item->variant_name,
            trim(($item->product_name ?? '').' '.($item->variant_name ?? '')),
            $item->product_name,
        ])
            ->reject(fn ($n) => ! $n)
            ->map(fn ($n) => $this->normalize((string) $n))
            ->unique();

        $pool = InventoryItem::where('branch_id', $branchId)
            ->get()
            ->keyBy(fn ($i) => $this->normalize($i->name));

        foreach ($candidates as $name) {
            if ($pool->has($name)) {
                return $pool[$name];
            }
        }

        return null;
    }

    private function normalize(string $name): string
    {
        return mb_strtolower((string) preg_replace('/\s+/', ' ', trim($name)));
    }
}
