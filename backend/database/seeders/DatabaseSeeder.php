<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Drink seeder rebuilds the whole catalog table set, so run it first,
        // then the food catalog on top.
        $this->call([
            DrinkCatalogSeeder::class,
            NigerianFoodCatalogSeeder::class,
        ]);
    }
}
