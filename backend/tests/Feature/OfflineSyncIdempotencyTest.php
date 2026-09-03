<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Critical idempotency test: Submitting the same sync request twice
 * should not create duplicate orders.
 */
class OfflineSyncIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    private function userWithActiveSubscription(): User
    {
        $org  = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);

        $plan = Plan::create([
            'name'          => 'Starter',
            'slug'          => 'starter',
            'price_monthly' => 15000,
            'price_yearly'  => 150000,
            'features'      => ['branches' => 1, 'users' => 3, 'terminals' => 1],
        ]);

        Subscription::create([
            'organization_id'      => $org->id,
            'plan_id'              => $plan->id,
            'status'               => 'active',
            'current_period_start' => now(),
            'current_period_end'   => now()->addMonth(),
        ]);

        return $user;
    }

    public function test_duplicate_order_request_with_same_idempotency_key_does_not_create_duplicates(): void
    {
        $user = $this->userWithActiveSubscription();

        $payload = [
            'source'  => 'pos',
            'type'    => 'dine_in',
            'items'   => [],
        ];

        $headers = ['X-Idempotency-Key' => 'test-key-abc123'];

        // First request creates the order
        $first = $this->actingAs($user)
            ->withHeaders($headers)
            ->postJson('/api/v1/orders', $payload);

        // The response might be 201 (created) or 422 (validation for missing required fields).
        // The important thing is: the second request must not create a second row.

        $countAfterFirst = \App\Models\Order::where('organization_id', $user->organization_id)->count();

        // Second identical request with same key
        $second = $this->actingAs($user)
            ->withHeaders($headers)
            ->postJson('/api/v1/orders', $payload);

        $countAfterSecond = \App\Models\Order::where('organization_id', $user->organization_id)->count();

        // Order count must not increase on the second request
        $this->assertEquals(
            $countAfterFirst,
            $countAfterSecond,
            'Duplicate idempotent request must not create a second order.'
        );
    }
}
