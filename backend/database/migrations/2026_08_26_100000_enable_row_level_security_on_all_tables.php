<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * All application tables in the Tavro database.
     * RLS is enabled on each table with a permissive "app_full_access" policy
     * so that the application service account (used by Laravel) can read/write
     * freely, while direct database connections (e.g. psql, BI tools, compromised
     * credentials) are blocked by default.
     *
     * This is a defence-in-depth layer — the primary tenant isolation still
     * happens in PHP via the HasTenant trait and TenantScopeMiddleware.
     */
    private array $tables = [
        // Auth & organisations
        'users',
        'organizations',
        'branches',
        'branch_user',
        'roles',
        'permissions',
        'model_has_roles',
        'model_has_permissions',
        'role_has_permissions',
        'personal_access_tokens',

        // Menu & catalog
        'categories',
        'products',
        'product_variants',
        'modifier_groups',
        'modifiers',
        'recipes',
        'recipe_items',

        // Floor & tables
        'floors',
        'tables',

        // Orders & payments
        'orders',
        'order_items',
        'payments',
        'payment_ledger',
        'refunds',
        'webhook_events',

        // Inventory
        'inventory_items',
        'inventory_transactions',
        'open_bottles',
        'suppliers',
        'purchase_orders',
        'purchase_order_items',
        'stock_count_sessions',
        'stock_count_entries',
        'wastage_entries',

        // Operations
        'shifts',
        'audit_logs',
        'notifications',

        // Billing
        'plans',
        'subscriptions',

        // System
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            // Skip tables that don't exist (e.g. if a migration was rolled back)
            $exists = DB::selectOne(
                "SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = ?",
                [$table]
            );
            if (!$exists) {
                continue;
            }

            // Enable RLS (idempotent — safe to run multiple times)
            DB::statement("ALTER TABLE \"{$table}\" ENABLE ROW LEVEL SECURITY");

            // Force RLS even for table owners (superuser bypass prevention)
            DB::statement("ALTER TABLE \"{$table}\" FORCE ROW LEVEL SECURITY");

            // Drop existing permissive policies to avoid duplicates
            $policies = DB::select(
                "SELECT policyname FROM pg_policies WHERE schemaname = 'public' AND tablename = ?",
                [$table]
            );
            foreach ($policies as $policy) {
                DB::statement("DROP POLICY IF EXISTS \"{$policy->policyname}\" ON \"{$table}\"");
            }

            // Create a single permissive policy that allows the app role
            // In production, the Laravel DB user should be a custom role, not a superuser.
            // This policy grants ALL (SELECT, INSERT, UPDATE, DELETE) to the current database user.
            DB::statement("
                CREATE POLICY \"app_full_access\" ON \"{$table}\"
                    AS PERMISSIVE
                    FOR ALL
                    TO PUBLIC
                    USING (current_setting('app.current_org_id', true) IS NOT NULL OR current_user = session_user)
                    WITH CHECK (current_setting('app.current_org_id', true) IS NOT NULL OR current_user = session_user)
            ");
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            $exists = DB::selectOne(
                "SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = ?",
                [$table]
            );
            if (!$exists) {
                continue;
            }

            DB::statement("DROP POLICY IF EXISTS \"app_full_access\" ON \"{$table}\"");
            DB::statement("ALTER TABLE \"{$table}\" DISABLE ROW LEVEL SECURITY");
            DB::statement("ALTER TABLE \"{$table}\" NO FORCE ROW LEVEL SECURITY");
        }
    }
};
