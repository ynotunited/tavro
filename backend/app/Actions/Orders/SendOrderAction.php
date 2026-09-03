<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Events\BarTicketUpdated;
use App\Events\KitchenTicketUpdated;
use App\Models\Order;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class SendOrderAction
{
    public function __construct(private readonly InventoryService $inventory) {}

    public function execute(User $actor, Order $order): Order
    {
        $result = DB::transaction(function () use ($actor, $order): Order {
            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->where('organization_id', $actor->organization_id)
                ->where('branch_id', $actor->branch_id)
                ->lockForUpdate()
                ->first();

            if (! $lockedOrder) {
                abort(403, 'You do not have access to this order.');
            }

            if (! in_array($lockedOrder->status, ['OPEN', 'SENT'], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Only open orders can be sent to preparation.',
                ]);
            }

            $pendingItems = $lockedOrder->allItems()
                ->where('status', 'PENDING')
                ->lockForUpdate()
                ->get();

            foreach ($pendingItems as $item) {
                $product = $item->product()->with('recipe.items')->first();
                if ($product) {
                    $this->inventory->deductFromRecipe(
                        $product,
                        $item->quantity,
                        $lockedOrder,
                        $actor->id
                    );
                }

                $item->update(['status' => 'SENT']);
            }

            $lockedOrder->update([
                'status' => 'SENT',
                'sent_at' => $lockedOrder->sent_at ?? now(),
            ]);

            return $lockedOrder->fresh(['table', 'waiter', 'items']);
        });

        event(new KitchenTicketUpdated($result));
        event(new BarTicketUpdated($result));

        return $result;
    }
}
