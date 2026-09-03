<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class VoidOrderItemAction
{
    public function __construct(private readonly InventoryService $inventory) {}

    public function execute(User $actor, Order $order, OrderItem $item, string $reason): OrderItem
    {
        return DB::transaction(function () use ($actor, $order, $item, $reason): OrderItem {
            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->where('organization_id', $actor->organization_id)
                ->where('branch_id', $actor->branch_id)
                ->lockForUpdate()
                ->first();

            if (! $lockedOrder) {
                abort(403, 'You do not have access to this order.');
            }

            $lockedItem = OrderItem::query()
                ->whereKey($item->id)
                ->where('order_id', $lockedOrder->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedItem) {
                abort(403, 'You do not have access to this order item.');
            }

            if ($lockedItem->status === 'VOIDED') {
                throw ValidationException::withMessages(['status' => 'This item has already been voided.']);
            }

            if (in_array($lockedOrder->status, ['PAID', 'CLOSED', 'VOIDED'], true)) {
                throw ValidationException::withMessages(['status' => 'Items cannot be voided after the order is finalized.']);
            }

            // Recipe consumption is now referenced directly by OrderItem.
            $movements = InventoryTransaction::query()
                ->where('reference_type', $lockedItem->getMorphClass())
                ->where('reference_id', $lockedItem->id)
                ->where('movement_type', 'recipe_consumption')
                ->whereNull('reverses_transaction_id')
                ->lockForUpdate()
                ->get();

            foreach ($movements as $movement) {
                if (! $movement->reversal()->exists()) {
                    $this->inventory->reverseTransaction(
                        $movement,
                        $actor->id,
                        'void-item:'.$lockedItem->id.':'.$movement->id,
                        'Void order item '.$lockedItem->id.': '.$reason,
                    );
                }
            }

            $lockedItem->update([
                'status' => 'VOIDED',
                'void_reason' => $reason,
                'voided_by' => $actor->id,
                'voided_at' => now(),
            ]);

            $lockedOrder->recalculate();

            return $lockedItem->fresh(['product', 'variant']);
        });
    }
}
