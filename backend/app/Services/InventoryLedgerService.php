<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class InventoryLedgerService
{
    public function record(
        InventoryItem $item,
        string $type,
        float $quantityChange,
        $reference = null,
        ?int $userId = null,
        ?string $notes = null,
        ?string $movementType = null,
        ?string $idempotencyKey = null,
        ?string $reversesTransactionId = null,
    ): ?InventoryTransaction {
        return DB::transaction(function () use ($item, $type, $quantityChange, $reference, $userId, $notes, $movementType, $idempotencyKey, $reversesTransactionId): ?InventoryTransaction {
            $lockedItem = InventoryItem::query()->whereKey($item->id)->lockForUpdate()->firstOrFail();

            if (! $lockedItem->track_inventory) {
                return null;
            }

            if ($idempotencyKey) {
                $existing = InventoryTransaction::query()->where('idempotency_key', $idempotencyKey)->first();
                if ($existing) {
                    return $existing;
                }
            }

            if ($reversesTransactionId) {
                $original = InventoryTransaction::query()
                    ->whereKey($reversesTransactionId)
                    ->lockForUpdate()
                    ->first();

                if (! $original || (string) $original->inventory_item_id !== (string) $lockedItem->id) {
                    throw ValidationException::withMessages(['reverses_transaction_id' => 'The transaction being reversed is invalid.']);
                }

                if ($original->reverses_transaction_id !== null) {
                    throw ValidationException::withMessages(['reverses_transaction_id' => 'A reversal cannot itself be reversed.']);
                }

                if ($original->reversal()->exists()) {
                    throw ValidationException::withMessages(['reverses_transaction_id' => 'This inventory transaction has already been reversed.']);
                }

                if (abs((float) $quantityChange + (float) $original->quantity_change) > 0.0001) {
                    throw ValidationException::withMessages(['quantity_change' => 'A reversal must exactly offset the original inventory movement.']);
                }
            }

            $newQuantity = (float) $lockedItem->current_stock + $quantityChange;
            if ($newQuantity < 0) {
                throw new InsufficientStockException($lockedItem->name, abs($quantityChange), (float) $lockedItem->current_stock);
            }

            $transaction = InventoryTransaction::create([
                'branch_id' => $lockedItem->branch_id,
                'inventory_item_id' => $lockedItem->id,
                'type' => $type,
                'movement_type' => $movementType,
                'quantity_change' => $quantityChange,
                'current_quantity' => $newQuantity,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id' => $reference?->id,
                'idempotency_key' => $idempotencyKey,
                'reverses_transaction_id' => $reversesTransactionId,
                'user_id' => $userId,
                'notes' => $notes,
            ]);

            $lockedItem->update(['current_stock' => $newQuantity]);

            if ($lockedItem->low_stock_alerted && $newQuantity > (float) $lockedItem->min_level) {
                $lockedItem->forceFill(['low_stock_alerted' => false])->save();
            }

            return $transaction;
        });
    }

    public function reverse(InventoryTransaction $transaction, ?int $userId = null, ?string $idempotencyKey = null, ?string $notes = null): ?InventoryTransaction
    {
        return $this->record(
            $transaction->inventoryItem,
            'void_reversal',
            -(float) $transaction->quantity_change,
            $transaction->reference,
            $userId,
            $notes ?? 'Reversal of inventory transaction '.$transaction->id,
            'reversal',
            $idempotencyKey,
            (string) $transaction->id,
        );
    }
}
