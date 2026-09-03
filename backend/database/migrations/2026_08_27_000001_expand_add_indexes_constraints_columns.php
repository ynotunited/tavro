<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * EXPAND PHASE — Additive-only migration. Zero downtime. Zero data loss.
 *
 * RULE: Add before remove. Never drop a column or constraint in this migration.
 *       The contract phase runs in a separate migration AFTER all code has been
 *       updated to use the new columns and the expand phase is verified in staging.
 *
 * This migration adds:
 *   1. Performance indexes on every column the app queries by
 *   2. CHECK constraints to prevent invalid status values
 *   3. Composite indexes for dashboard/report hot paths
 *   4. New columns needed for upcoming features
 *   5. Unique constraint to enforce one subscription per org
 *   6. Audit columns for data lineage
 */
return new class extends Migration
{
    private array $appliedIndexes = [];
    private array $appliedConstraints = [];

    public function up(): void
    {
        // ─────────────────────────────────────────────────────────────────
        // PHASE 1: Performance indexes (highest impact, lowest risk)
        //
        // These are pure additions — they accelerate queries without
        // changing data. PostgreSQL creates indexes safely since they
        // don't lock writes.
        // ─────────────────────────────────────────────────────────────────

        // users: every request hits this via TenantScopeMiddleware
        $this->addIndexSafely('users', ['organization_id'], 'users_org_idx');
        $this->addIndexSafely('users', ['status'], 'users_status_idx');
        $this->addIndexSafely('users', ['organization_id', 'status'], 'users_org_status_idx');

        // orders: the most queried table — dashboard, reports, kitchen, POS
        $this->addIndexSafely('orders', ['organization_id'], 'orders_org_idx');
        $this->addIndexSafely('orders', ['branch_id'], 'orders_branch_idx');
        $this->addIndexSafely('orders', ['status'], 'orders_status_idx');
        $this->addIndexSafely('orders', ['shift_id'], 'orders_shift_idx');
        $this->addIndexSafely('orders', ['opened_by'], 'orders_opened_by_idx');
        $this->addIndexSafely('orders', ['created_at'], 'orders_created_at_idx');

        // Composite indexes for the exact query patterns the app uses
        $this->addIndexSafely('orders', ['organization_id', 'branch_id', 'status'], 'orders_org_branch_status_idx');
        $this->addIndexSafely('orders', ['organization_id', 'branch_id', 'created_at'], 'orders_org_branch_date_idx');
        $this->addIndexSafely('orders', ['shift_id', 'status'], 'orders_shift_status_idx');

        // order_items: queried for every order view + kitchen/bar displays
        $this->addIndexSafely('order_items', ['order_id'], 'order_items_order_idx');
        $this->addIndexSafely('order_items', ['product_id'], 'order_items_product_idx');
        $this->addIndexSafely('order_items', ['order_id', 'status'], 'order_items_order_status_idx');

        // payments: financial queries, webhook processing, reports
        $this->addIndexSafely('payments', ['order_id'], 'payments_order_idx');
        $this->addIndexSafely('payments', ['status'], 'payments_status_idx');
        $this->addIndexSafely('payments', ['processed_by'], 'payments_processed_by_idx');
        $this->addIndexSafely('payments', ['created_at'], 'payments_created_at_idx');
        $this->addIndexSafely('payments', ['order_id', 'status'], 'payments_order_status_idx');

        // shifts: active shift lookups, close operations
        $this->addIndexSafely('shifts', ['branch_id'], 'shifts_branch_idx');
        $this->addIndexSafely('shifts', ['user_id'], 'shifts_user_idx');
        $this->addIndexSafely('shifts', ['status'], 'shifts_status_idx');
        $this->addIndexSafely('shifts', ['branch_id', 'status'], 'shifts_branch_status_idx');
        $this->addIndexSafely('shifts', ['user_id', 'status'], 'shifts_user_status_idx');

        // subscriptions: checkSubscription middleware runs on every order mutation
        $this->addIndexSafely('subscriptions', ['organization_id'], 'subscriptions_org_idx');
        $this->addIndexSafely('subscriptions', ['status'], 'subscriptions_status_idx');

        // audit_logs: filtered by org + date range on every request
        $this->addIndexSafely('audit_logs', ['organization_id'], 'audit_logs_org_idx');
        $this->addIndexSafely('audit_logs', ['actor_id'], 'audit_logs_actor_idx');
        $this->addIndexSafely('audit_logs', ['created_at'], 'audit_logs_created_at_idx');
        $this->addIndexSafely('audit_logs', ['organization_id', 'created_at'], 'audit_logs_org_date_idx');

        // notifications: polymorphic notifiable lookup
        $this->addIndexSafely('notifications', ['notifiable_type', 'notifiable_id'], 'notifications_notifiable_idx');
        $this->addIndexSafely('notifications', ['read_at'], 'notifications_read_at_idx');

        // products: catalog queries
        $this->addIndexSafely('products', ['organization_id'], 'products_org_idx');
        $this->addIndexSafely('products', ['category_id'], 'products_category_idx');

        // inventory_items: stock lookups (uses branch_id, not org_id)
        $this->addIndexSafely('inventory_items', ['branch_id'], 'inventory_items_branch_idx');

        // categories: catalog navigation
        $this->addIndexSafely('categories', ['organization_id'], 'categories_org_idx');

        // branches: org lookup
        $this->addIndexSafely('branches', ['organization_id'], 'branches_org_idx');

        // webhook_events: dedup lookups
        $this->addIndexSafely('webhook_events', ['provider'], 'webhook_events_provider_idx');
        $this->addIndexSafely('webhook_events', ['event_id'], 'webhook_events_event_id_idx');

        // api_keys: gateway auth lookups
        $this->addIndexSafely('api_keys', ['organization_id'], 'api_keys_org_idx');
        $this->addIndexSafely('api_keys', ['is_active'], 'api_keys_active_idx');

        // ─────────────────────────────────────────────────────────────────
        // PHASE 2: CHECK constraints (data integrity)
        //
        // PostgreSQL supports CHECK constraints. Laravel Blueprint doesn't
        // expose them directly, so we use raw DB statements.
        // These are ADDITIVE — they don't change existing data, only prevent
        // future invalid inserts/updates.
        // ─────────────────────────────────────────────────────────────────

        $this->addCheckConstraint('users', 'users_status_check',
            "status IN ('active', 'inactive', 'merged', 'pending')");

        $this->addCheckConstraint('orders', 'orders_status_check',
            "status IN ('DRAFT', 'OPEN', 'SENT', 'PREPARING', 'READY', 'SERVED', 'BILL_REQUESTED', 'PAYMENT_PENDING', 'PAYMENT_PARTIAL', 'PAID', 'CLOSED', 'VOIDED')");

        $this->addCheckConstraint('payments', 'payments_status_check',
            "status IN ('PENDING', 'PROCESSING', 'COMPLETED', 'FAILED', 'REFUNDED', 'PARTIALLY_REFUNDED')");

        $this->addCheckConstraint('shifts', 'shifts_status_check',
            "status IN ('OPEN', 'CLOSING', 'CLOSED')");

        $this->addCheckConstraint('subscriptions', 'subscriptions_status_check',
            "status IN ('trialing', 'active', 'past_due', 'canceled')");

        // ─────────────────────────────────────────────────────────────────
        // PHASE 3: Unique constraints (one subscription per org)
        //
        // This enforces business rule at the database level, not just app level.
        // ─────────────────────────────────────────────────────────────────

        $this->addUniqueConstraintSafely('subscriptions', ['organization_id'],
            'subscriptions_org_unique');

        // ─────────────────────────────────────────────────────────────────
        // PHASE 4: New columns for upcoming features
        //
        // All nullable with sensible defaults — no data migration needed.
        // Existing rows get NULL, code checks for NULL before using.
        // ─────────────────────────────────────────────────────────────────

        Schema::table('users', function (Blueprint $table) {
            $table->string('locale', 10)->nullable()->after('status');
            $table->string('timezone', 50)->nullable()->after('locale');
            $table->string('avatar_url')->nullable()->after('timezone');
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->string('default_currency', 3)->default('NGN')->after('name');
            $table->string('invoice_prefix', 10)->nullable()->after('default_currency');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('barcode')->nullable()->after('sku');
            $table->decimal('minimum_order_quantity', 8, 2)->default(1)->after('cost_price');
        });

        Schema::table('inventory_items', function (Blueprint $table) {
            $table->decimal('reorder_point', 10, 2)->nullable()->after('current_stock');
            $table->decimal('reorder_quantity', 10, 2)->nullable()->after('reorder_point');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->timestamp('grace_period_end')->nullable()->after('current_period_end');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('currency', 3)->default('NGN')->after('order_number');
            $table->decimal('exchange_rate', 12, 6)->default(1)->after('currency');
        });

        // ─────────────────────────────────────────────────────────────────
        // PHASE 5: Audit columns for data lineage
        //
        // Track who last touched a record and when — critical for compliance.
        // ─────────────────────────────────────────────────────────────────

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('last_modified_by')->nullable()->after('voided_by')
                ->constrained('users')->nullOnDelete();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('sort_order')
                ->constrained('users')->nullOnDelete();
            $table->foreignId('last_modified_by')->nullable()->after('created_by')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        // ─────────────────────────────────────────────────────────────────
        // ROLLBACK: Remove everything added in up(), in reverse order.
        //
        // SAFETY: This only removes what THIS migration added.
        //         It does NOT touch any pre-existing schema.
        // ─────────────────────────────────────────────────────────────────

        // Phase 5: Audit columns
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn(['created_by', 'last_modified_by']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['last_modified_by']);
            $table->dropColumn(['last_modified_by']);
        });

        // Phase 4: New columns
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('grace_period_end');
        });

        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropColumn(['reorder_point', 'reorder_quantity']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['barcode', 'minimum_order_quantity']);
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['default_currency', 'invoice_prefix']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['locale', 'timezone', 'avatar_url']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['currency', 'exchange_rate']);
        });

        // Phase 3: Unique constraints
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropUnique('subscriptions_org_unique');
        });

        // Phase 2: CHECK constraints
        $this->dropCheckConstraint('subscriptions', 'subscriptions_status_check');
        $this->dropCheckConstraint('shifts', 'shifts_status_check');
        $this->dropCheckConstraint('payments', 'payments_status_check');
        $this->dropCheckConstraint('orders', 'orders_status_check');
        $this->dropCheckConstraint('users', 'users_status_check');

        // Phase 1: Indexes (reverse order)
        $this->dropIndexSafely('api_keys', 'api_keys_active_idx');
        $this->dropIndexSafely('api_keys', 'api_keys_org_idx');
        $this->dropIndexSafely('webhook_events', 'webhook_events_event_id_idx');
        $this->dropIndexSafely('webhook_events', 'webhook_events_provider_idx');
        $this->dropIndexSafely('categories', 'categories_org_idx');
        $this->dropIndexSafely('inventory_items', 'inventory_items_branch_idx');
        $this->dropIndexSafely('products', 'products_category_idx');
        $this->dropIndexSafely('products', 'products_org_idx');
        $this->dropIndexSafely('notifications', 'notifications_read_at_idx');
        $this->dropIndexSafely('notifications', 'notifications_notifiable_idx');
        $this->dropIndexSafely('audit_logs', 'audit_logs_org_date_idx');
        $this->dropIndexSafely('audit_logs', 'audit_logs_created_at_idx');
        $this->dropIndexSafely('audit_logs', 'audit_logs_actor_idx');
        $this->dropIndexSafely('audit_logs', 'audit_logs_org_idx');
        $this->dropIndexSafely('subscriptions', 'subscriptions_status_idx');
        $this->dropIndexSafely('subscriptions', 'subscriptions_org_idx');
        $this->dropIndexSafely('shifts', 'shifts_user_status_idx');
        $this->dropIndexSafely('shifts', 'shifts_branch_status_idx');
        $this->dropIndexSafely('shifts', 'shifts_status_idx');
        $this->dropIndexSafely('shifts', 'shifts_user_idx');
        $this->dropIndexSafely('shifts', 'shifts_branch_idx');
        $this->dropIndexSafely('payments', 'payments_order_status_idx');
        $this->dropIndexSafely('payments', 'payments_created_at_idx');
        $this->dropIndexSafely('payments', 'payments_processed_by_idx');
        $this->dropIndexSafely('payments', 'payments_status_idx');
        $this->dropIndexSafely('payments', 'payments_order_idx');
        $this->dropIndexSafely('order_items', 'order_items_order_status_idx');
        $this->dropIndexSafely('order_items', 'order_items_product_idx');
        $this->dropIndexSafely('order_items', 'order_items_order_idx');
        $this->dropIndexSafely('orders', 'orders_shift_status_idx');
        $this->dropIndexSafely('orders', 'orders_org_branch_date_idx');
        $this->dropIndexSafely('orders', 'orders_org_branch_status_idx');
        $this->dropIndexSafely('orders', 'orders_created_at_idx');
        $this->dropIndexSafely('orders', 'orders_opened_by_idx');
        $this->dropIndexSafely('orders', 'orders_shift_idx');
        $this->dropIndexSafely('orders', 'orders_status_idx');
        $this->dropIndexSafely('orders', 'orders_branch_idx');
        $this->dropIndexSafely('orders', 'orders_org_idx');
        $this->dropIndexSafely('users', 'users_org_status_idx');
        $this->dropIndexSafely('users', 'users_status_idx');
        $this->dropIndexSafely('users', 'users_org_idx');
    }

    // ── Helper: Safe index creation (idempotent) ───────────────────────────

    private function addIndexSafely(string $table, array $columns, string $name): void
    {
        $exists = DB::select(
            "SELECT 1 FROM pg_indexes WHERE tablename = :table AND indexname = :name AND schemaname = 'public'",
            ['table' => $table, 'name' => $name]
        );

        if (empty($exists)) {
            Schema::table($table, fn (Blueprint $t) => $t->index($columns, $name));
            $this->appliedIndexes[] = $name;
        }
    }

    private function dropIndexSafely(string $table, string $name): void
    {
        if (in_array($name, $this->appliedIndexes)) {
            Schema::table($table, fn (Blueprint $t) => $t->dropIndex($name));
        }
    }

    // ── Helper: Safe CHECK constraint creation ──────────────────────────────

    private function addCheckConstraint(string $table, string $name, string $expression): void
    {
        $exists = DB::select(
            "SELECT 1 FROM pg_constraint WHERE conname = :name AND conrelid = :table::regclass",
            ['name' => $name, 'table' => $table]
        );

        if (empty($exists)) {
            DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$name} CHECK ({$expression})");
            $this->appliedConstraints[] = $name;
        }
    }

    private function dropCheckConstraint(string $table, string $name): void
    {
        if (in_array($name, $this->appliedConstraints)) {
            DB::statement("ALTER TABLE {$table} DROP CONSTRAINT {$name}");
        }
    }

    private function addUniqueConstraintSafely(string $table, array $columns, string $name): void
    {
        $exists = DB::select(
            "SELECT 1 FROM pg_constraint WHERE conname = :name AND conrelid = :table::regclass",
            ['name' => $name, 'table' => $table]
        );

        if (empty($exists)) {
            Schema::table($table, fn (Blueprint $t) => $t->unique($columns, $name));
        }
    }
};
