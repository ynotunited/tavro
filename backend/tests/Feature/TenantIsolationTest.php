<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Order;
use App\Models\Organization;
use App\Models\Product;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

/**
 * Critical security tests for Tavro's tenant isolation boundary.
 *
 * These tests intentionally exercise the model layer as well as HTTP access.
 * Tenant isolation must remain safe even when a controller forgets a manual
 * organization_id filter.
 */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_list_another_tenants_orders(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        $userA = User::factory()->create(['organization_id' => $orgA->id]);
        $userB = User::factory()->create(['organization_id' => $orgB->id]);

        $branchB = Branch::create([
            'organization_id' => $orgB->id,
            'name' => 'Branch B',
        ]);

        Order::create([
            'organization_id' => $orgB->id,
            'branch_id' => $branchB->id,
            'opened_by' => $userB->id,
            'order_number' => 'ORG-B-001',
            'status' => 'OPEN',
            'source' => 'pos',
        ]);

        $response = $this->actingAs($userA)->getJson('/api/v1/orders');

        $response->assertOk();
        $this->assertCount(0, $response->json('data'));
    }

    public function test_user_cannot_see_another_tenants_products(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        $userA = User::factory()->create(['organization_id' => $orgA->id]);

        Product::create([
            'organization_id' => $orgB->id,
            'name' => 'Org B Secret Burger',
            'selling_price' => 1000,
        ]);

        $response = $this->actingAs($userA)->getJson('/api/v1/products');

        $response->assertOk();
        $this->assertCount(0, $response->json('data'));
    }

    public function test_tenant_scope_returns_only_current_tenant_records(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        $productA = Product::create([
            'organization_id' => $orgA->id,
            'name' => 'Tenant A Product',
            'selling_price' => 1000,
        ]);

        $productB = Product::create([
            'organization_id' => $orgB->id,
            'name' => 'Tenant B Product',
            'selling_price' => 2000,
        ]);

        app(TenantContext::class)->set($orgA->id);

        $this->assertSame([$productA->id], Product::query()->pluck('id')->all());
        $this->assertNull(Product::query()->find($productB->id));

        app(TenantContext::class)->clear();
    }

    public function test_missing_tenant_context_fails_closed_for_tenant_models(): void
    {
        $org = Organization::factory()->create();

        Product::create([
            'organization_id' => $org->id,
            'name' => 'Protected Product',
            'selling_price' => 1000,
        ]);

        app(TenantContext::class)->clear();

        $this->assertCount(0, Product::query()->get());
    }

    public function test_forged_organization_id_cannot_be_created_inside_another_tenant(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        app(TenantContext::class)->set($orgA->id);

        $this->expectException(LogicException::class);

        Product::create([
            'organization_id' => $orgB->id,
            'name' => 'Forged Product',
            'selling_price' => 1000,
        ]);
    }

    public function test_existing_tenant_record_cannot_be_saved_under_another_tenant(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        $product = Product::create([
            'organization_id' => $orgB->id,
            'name' => 'Tenant B Product',
            'selling_price' => 1000,
        ]);

        app(TenantContext::class)->set($orgA->id);

        $product->name = 'Tampered Product';

        $this->expectException(LogicException::class);
        $product->save();
    }

    public function test_tenant_context_run_restores_previous_context(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        $context = app(TenantContext::class);

        $context->set($orgA->id);

        $context->run($orgB->id, function () use ($context, $orgB): void {
            $this->assertSame((string) $orgB->id, $context->requiredId());
        });

        $this->assertSame((string) $orgA->id, $context->requiredId());
        $context->clear();
        $this->assertNull($context->id());
    }
}
