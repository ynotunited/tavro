<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Branch;
use App\Models\InventoryItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryItem>
 */
final class InventoryItemFactory extends Factory
{
    protected $model = InventoryItem::class;

    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'name' => fake()->words(2, true),
            'sku' => strtoupper(fake()->unique()->bothify('INV-####??')),
            'category' => fake()->randomElement(['Food', 'Drink', 'Bar', 'Packaging']),
            'unit_of_measure' => fake()->randomElement(['unit', 'kg', 'litre', 'bottle', 'ml']),
            'cost_per_unit' => 100,
            'current_stock' => 10,
            'min_level' => 2,
            'track_inventory' => true,
            'low_stock_alerted' => false,
        ];
    }

    public function withStock(float $quantity): static
    {
        return $this->state(fn (): array => ['current_stock' => $quantity]);
    }

    public function untracked(): static
    {
        return $this->state(fn (): array => ['track_inventory' => false]);
    }
}
