<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        Plan::updateOrCreate(['slug' => 'starter'], [
            'name' => 'Starter',
            'price_monthly' => 15000,
            'price_yearly' => 150000,
            'features' => [
                'branches' => 1,
                'users' => 3,
                'terminals' => 1,
            ],
            'paystack_plan_code' => 'PLN_starter_monthly'
        ]);

        Plan::updateOrCreate(['slug' => 'growth'], [
            'name' => 'Growth',
            'price_monthly' => 35000,
            'price_yearly' => 350000,
            'features' => [
                'branches' => 3,
                'users' => 10,
                'terminals' => 5,
            ],
            'paystack_plan_code' => 'PLN_growth_monthly'
        ]);

        Plan::updateOrCreate(['slug' => 'pro'], [
            'name' => 'Pro',
            'price_monthly' => 75000,
            'price_yearly' => 750000,
            'features' => [
                'branches' => 10,
                'users' => 50,
                'terminals' => 20,
            ],
            'paystack_plan_code' => 'PLN_pro_monthly'
        ]);

        Plan::updateOrCreate(['slug' => 'enterprise'], [
            'name' => 'Enterprise',
            'price_monthly' => 150000,
            'price_yearly' => 1500000,
            'features' => [
                'branches' => -1, // unlimited
                'users' => -1,
                'terminals' => -1,
            ],
            'paystack_plan_code' => 'PLN_enterprise_monthly'
        ]);
    }
}
