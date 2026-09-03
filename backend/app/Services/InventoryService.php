<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\OrderItem;

final class InventoryService
{
    public function __construct(private readonly InventoryLedgerService $ledger) {}

    public function recordTransaction(
        InventoryItem $item,
        string $type,
        float $quantityChange,
        $reference = null,
        $userId = null,
        $notes = null,
        ?string $movementType = null,
        ?string $idempotencyKey = null,
    ) {
        return $this->ledger->record(
            item: $item,
            type: $type,
            quantityChange: $quantityChange,
            reference: $reference,
            userId: $userId,
            notes: $notes,
            movementType: $movementType,
            idempotencyKey: $idempotencyKey,
        );
    }

    /**
     * Deduct inventory for a sold order item.
     * Movements reference the exact OrderItem so voids can reverse only this item's consumption.
     */
    public function deductFromRecipe(OrderItem $orderItem, $userId = null): void
    {
        $orderItem->loadMissing('product.recipe.items', 'order');
        $product = $orderItem->product;

        if (! $product?->recipe) {
            return;
        }

        foreach ($product->recipe->items as $recipeItem) {
            if (! $recipeItem->inventory_item_id) {
                continue;
            }

            $inventoryItem = InventoryItem::query()
                ->whereKey($recipeItem->inventory_item_id)
                ->where('branch_id', $orderItem->order->branch_id)
                ->first();

            if (! $inventoryItem || ! $inventoryItem->track_inventory) {
                continue;
            }

            $deduction = -1 * ((float) $recipeItem->quantity * (float) $orderItem->quantity);

            $this->recordTransaction(
                $inventoryItem,
                'sale',
                $deduction,
                $orderItem,
                $userId,
                'Recipe consumption for order item '.$orderItem->id,
                'recipe_consumption',
                'recipe-sale:order-item:'.$orderItem->id.':'.$inventoryItem->id,
            );
        }
    }

    public function reverseTransaction($transaction, $userId = null, ?string $idempotencyKey = null, ?string $notes = null)
    {
        return $this->ledger->reverse($transaction, $userId, $idempotencyKey, $notes);
    }
}
