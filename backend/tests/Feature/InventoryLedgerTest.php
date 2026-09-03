<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exceptions\InsufficientStockException;
use App\Models\Branch;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\User;
use App\Services\InventoryLedgerService;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class InventoryLedgerTest extends TestCase
{
    use RefreshDatabase;

    private function userForItem(InventoryItem $item): User
    {
        $branch = Branch::withoutGlobalScopes()->findOrFail($item->branch_id);
        app(TenantContext::class)->set($branch->organization_id);

        $user = User::factory()->create([
            'organization_id' => $branch->organization_id,
        ]);

        $user->branches()->attach($item->branch_id);

        return $user;
    }

    public function test_it_records_a_stock_increase_and_updates_the_balance(): void
    {
        $item = InventoryItem::factory()->withStock(10)->create();
        $user = $this->userForItem($item);

        $transaction = app(InventoryLedgerService::class)->record(
            item: $item,
            type: 'purchase',
            quantityChange: 5,
            userId: $user->id,
            idempotencyKey: 'test-purchase-1',
        );

        $this->assertNotNull($transaction);
        $this->assertSame('15.0000', (string) $transaction->current_quantity);
        $this->assertSame('15.0000', (string) $item->fresh()->current_stock);
        $this->assertDatabaseHas('inventory_transactions', [
            'id' => $transaction->id,
            'inventory_item_id' => $item->id,
            'idempotency_key' => 'test-purchase-1',
        ]);
    }

    public function test_it_rejects_a_movement_that_would_make_stock_negative(): void
    {
        $item = InventoryItem::factory()->withStock(2)->create();
        $user = $this->userForItem($item);

        $this->expectException(InsufficientStockException::class);

        try {
            app(InventoryLedgerService::class)->record(
                item: $item,
                type: 'recipe_consumption',
                quantityChange: -3,
                userId: $user->id,
                idempotencyKey: 'test-consumption-1',
            );
        } finally {
            $this->assertSame('2.0000', (string) $item->fresh()->current_stock);
            $this->assertDatabaseCount('inventory_transactions', 0);
        }
    }

    public function test_it_returns_the_existing_transaction_for_a_repeated_idempotency_key(): void
    {
        $item = InventoryItem::factory()->withStock(10)->create();
        $user = $this->userForItem($item);
        $service = app(InventoryLedgerService::class);

        $first = $service->record($item, 'purchase', 2, null, $user->id, null, null, 'same-key');
        $second = $service->record($item->fresh(), 'purchase', 2, null, $user->id, null, null, 'same-key');

        $this->assertNotNull($first);
        $this->assertNotNull($second);
        $this->assertSame($first->id, $second->id);
        $this->assertSame('12.0000', (string) $item->fresh()->current_stock);
        $this->assertSame(1, InventoryTransaction::query()->where('idempotency_key', 'same-key')->count());
    }

    public function test_it_creates_an_exact_reversal_and_does_not_mutate_the_original_movement(): void
    {
        $item = InventoryItem::factory()->withStock(10)->create();
        $user = $this->userForItem($item);
        $service = app(InventoryLedgerService::class);

        $original = $service->record($item, 'recipe_consumption', -3, null, $user->id, null, null, 'sale-item-1');
        $this->assertNotNull($original);

        $reversal = $service->reverse($original, $user->id, 'reverse-sale-item-1', 'Void order item');

        $this->assertNotNull($reversal);
        $this->assertSame($original->id, $reversal->reverses_transaction_id);
        $this->assertSame('3.0000', (string) $reversal->quantity_change);
        $this->assertSame('10.0000', (string) $item->fresh()->current_stock);
        $this->assertDatabaseHas('inventory_transactions', ['id' => $original->id, 'quantity_change' => -3]);
    }

    public function test_it_rejects_a_second_reversal_of_the_same_movement(): void
    {
        $item = InventoryItem::factory()->withStock(10)->create();
        $user = $this->userForItem($item);
        $service = app(InventoryLedgerService::class);

        $original = $service->record($item, 'recipe_consumption', -2, null, $user->id, null, null, 'sale-item-2');
        $this->assertNotNull($original);
        $service->reverse($original, $user->id, 'reverse-sale-item-2', 'Void order item');

        $this->expectException(ValidationException::class);
        try {
            $service->reverse($original->fresh(), $user->id, 'reverse-sale-item-2-again', 'Duplicate void');
        } finally {
            $this->assertSame(2, InventoryTransaction::query()->where('inventory_item_id', $item->id)->count());
            $this->assertSame('10.0000', (string) $item->fresh()->current_stock);
        }
    }

    public function test_it_rejects_a_reversal_of_a_reversal(): void
    {
        $item = InventoryItem::factory()->withStock(10)->create();
        $user = $this->userForItem($item);
        $service = app(InventoryLedgerService::class);

        $original = $service->record($item, 'recipe_consumption', -2, null, $user->id, null, null, 'sale-item-3');
        $this->assertNotNull($original);
        $reversal = $service->reverse($original, $user->id, 'reverse-sale-item-3', 'Void order item');
        $this->assertNotNull($reversal);

        $this->expectException(ValidationException::class);
        try {
            $service->reverse($reversal, $user->id, 'reverse-reversal-3', 'Invalid reversal');
        } finally {
            $this->assertSame('10.0000', (string) $item->fresh()->current_stock);
        }
    }
}

