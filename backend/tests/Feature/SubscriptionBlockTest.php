<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyRequestSignature;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Critical billing test: Ensure expired subscriptions block new order creation.
 */
class SubscriptionBlockTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithSubscription(string $status): User
    {
        $org  = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);

        $plan = Plan::create([
            'name'          => 'Pro',
            'slug'          => 'pro',
            'price_monthly' => 75000,
            'price_yearly'  => 750000,
            'features'      => ['branches' => 10, 'users' => 50, 'terminals' => 20],
        ]);

        Subscription::create([
            'organization_id'     => $org->id,
            'plan_id'             => $plan->id,
            'status'              => $status,
            'current_period_start'=> now(),
            'current_period_end'  => now()->addMonth(),
        ]);

        return $user;
    }

    public function test_active_subscription_allows_order_creation(): void
    {
        $user = $this->createUserWithSubscription('active');

        // We just check the endpoint is NOT returning 402.
        // It may return 422 (validation) but not 402 (payment required).
        $response = $this->withoutMiddleware(VerifyRequestSignature::class)
            ->actingAs($user)
            ->postJson('/api/v1/orders', []);

        $this->assertNotEquals(402, $response->status());
    }

    public function test_canceled_subscription_blocks_order_creation(): void
    {
        $user = $this->createUserWithSubscription('canceled');

        $response = $this->withoutMiddleware(VerifyRequestSignature::class)
            ->actingAs($user)
            ->postJson('/api/v1/orders', []);

        $response->assertStatus(402);
        $response->assertJsonPath('error_code', 'SUBSCRIPTION_REQUIRED');
    }

    public function test_trialing_subscription_allows_order_creation(): void
    {
        $user = $this->createUserWithSubscription('trialing');

        $response = $this->withoutMiddleware(VerifyRequestSignature::class)
            ->actingAs($user)
            ->postJson('/api/v1/orders', []);

        $this->assertNotEquals(402, $response->status());
    }
}
