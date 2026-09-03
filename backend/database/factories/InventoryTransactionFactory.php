<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Branch;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryTransaction>
 */
final class InventoryTransactionFactory extends Factory
{
    protected $model = InventoryTransaction::class;

    public function definition(): array
    {
        $branch = Branch::factory()->create();

        return [
            'branch_id' => $branch->id,
            'inventory_item_id' => InventoryItem::factory()->for($branch)->create()->id,
            'type' => 'adjustment',
            'movement_type' => 'manual_adjustment',
            'quantity_change' => 1,
            'current_quantity' => 11,
            'reference_type' => null,
            'reference_id' => null,
            'idempotency_key' => null,
            'reverses_transaction_id' => null,
            'user_id' => User::factory(),
            'notes' => null,
        ];
    }
}
