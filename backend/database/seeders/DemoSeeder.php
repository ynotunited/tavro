<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Floor;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\Table;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    /**
     * Seeds a complete working Tavro demo environment.
     *
     * Run: php artisan db:seed --class=DemoSeeder
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding demo data...');

        // Clean up previous demo data
        User::whereIn('email', ['owner@demo.tavro.ng', 'waiter@demo.tavro.ng'])->delete();
        Organization::where('name', 'The Golden Fork Restaurant')->delete();

        // ── Organization ──────────────────────────────────────────────────────
        $org = Organization::create([
            'name' => 'The Golden Fork Restaurant',
            'type' => 'restaurant',
            'currency' => 'NGN',
            'timezone' => 'Africa/Lagos',
            'tax_percentage' => 7.5,
            'service_charge_percentage' => 5,
        ]);

        // ── Subscription (Pro, trialing 14 days) ─────────────────────────────
        $plan = Plan::where('slug', 'pro')->first();
        if ($plan) {
            Subscription::create([
                'organization_id' => $org->id,
                'plan_id' => $plan->id,
                'status' => 'trialing',
                'trial_ends_at' => now()->addDays(14),
                'current_period_start' => now(),
                'current_period_end' => now()->addDays(14),
            ]);
        }

        // ── Branch ────────────────────────────────────────────────────────────
        $branch = Branch::create([
            'organization_id' => $org->id,
            'name' => 'Main Branch',
            'address' => '14 Adeola Odeku Street, Victoria Island',
            'phone' => '+2348012345678',
            'timezone' => 'Africa/Lagos',
        ]);

        // ── Users ─────────────────────────────────────────────────────────────
        $owner = User::create([
            'organization_id' => $org->id,
            'first_name' => 'Emeka',
            'last_name' => 'Okafor',
            'name' => 'Emeka Okafor',
            'email' => 'owner@demo.tavro.ng',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);
        $owner->assignRole('owner');
        $owner->branches()->attach($branch->id);

        $waiter = User::create([
            'organization_id' => $org->id,
            'first_name' => 'Amaka',
            'last_name' => 'Obi',
            'name' => 'Amaka Obi',
            'email' => 'waiter@demo.tavro.ng',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);
        $waiter->assignRole('waiter');
        $waiter->branches()->attach($branch->id);

        // ── Categories ────────────────────────────────────────────────────────
        $starters = Category::create([
            'organization_id' => $org->id,
            'name' => 'Starters',
            'color' => '#F59E0B',
            'sort_order' => 1,
        ]);
        $mains = Category::create([
            'organization_id' => $org->id,
            'name' => 'Mains',
            'color' => '#10B981',
            'sort_order' => 2,
        ]);
        $drinks = Category::create([
            'organization_id' => $org->id,
            'name' => 'Drinks',
            'color' => '#3B82F6',
            'sort_order' => 3,
        ]);

        // ── Products ──────────────────────────────────────────────────────────
        $products = [
            ['name' => 'Peppered Chicken',      'category_id' => $starters->id, 'selling_price' => 2500, 'cost_price' => 1200],
            ['name' => 'Calamari Rings',         'category_id' => $starters->id, 'selling_price' => 3200, 'cost_price' => 1500],
            ['name' => 'Jollof Rice',            'category_id' => $mains->id,    'selling_price' => 3500, 'cost_price' => 1600],
            ['name' => 'Fried Rice & Chicken',   'category_id' => $mains->id,    'selling_price' => 4500, 'cost_price' => 2000],
            ['name' => 'Grilled Tilapia',        'category_id' => $mains->id,    'selling_price' => 5500, 'cost_price' => 2800],
            ['name' => 'Egusi Soup & Pounded Yam', 'category_id' => $mains->id,   'selling_price' => 4000, 'cost_price' => 1800],
            ['name' => 'Chilled Chapman',        'category_id' => $drinks->id,   'selling_price' => 1200, 'cost_price' => 500],
            ['name' => 'Nigerian Chapman',       'category_id' => $drinks->id,   'selling_price' => 1500, 'cost_price' => 600],
            ['name' => 'Star Lager',             'category_id' => $drinks->id,   'selling_price' => 1000, 'cost_price' => 450],
            ['name' => 'Soft Drink (35cl)',      'category_id' => $drinks->id,   'selling_price' => 700, 'cost_price' => 300],
        ];

        foreach ($products as $i => $p) {
            Product::create([
                'organization_id' => $org->id,
                'category_id' => $p['category_id'],
                'name' => $p['name'],
                'selling_price' => $p['selling_price'],
                'cost_price' => $p['cost_price'],
                'type' => $p['category_id'] === $drinks->id ? 'drink' : 'food',
                'is_available' => true,
                'is_taxable' => true,
                'sort_order' => $i + 1,
            ]);
        }

        // ── Floors & Tables ───────────────────────────────────────────────────
        $floor = Floor::create([
            'organization_id' => $org->id,
            'branch_id' => $branch->id,
            'name' => 'Ground Floor',
        ]);

        $tables = ['T1', 'T2', 'T3', 'T4', 'T5', 'T6'];
        foreach ($tables as $t) {
            Table::create([
                'organization_id' => $org->id,
                'branch_id' => $branch->id,
                'floor_id' => $floor->id,
                'name' => $t,
                'capacity' => 4,
                'status' => 'available',
            ]);
        }

        $this->command->info('✅ Demo environment ready!');
        $this->command->table(['Role', 'Email', 'Password'], [
            ['Owner / Manager', 'owner@demo.tavro.ng', 'password'],
            ['Waiter',          'waiter@demo.tavro.ng', 'password'],
        ]);
    }
}
