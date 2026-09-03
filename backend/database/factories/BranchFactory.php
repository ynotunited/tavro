<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Branch>
 */
class BranchFactory extends Factory
{
    protected $model = Branch::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->company() . ' Branch',
            'address' => fake()->address(),
            'phone' => fake()->phoneNumber(),
            'timezone' => 'Africa/Lagos',
            'operating_hours' => [],
        ];
    }
}
