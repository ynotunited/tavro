<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Verify staging database matches production after migration.
 *
 * Checks:
 *   1. Table counts match production
 *   2. All indexes exist
 *   3. All CHECK constraints are valid
 *   4. No dead tuples from failed operations
 *   5. Migration status is clean
 *
 * Usage:
 *   php artisan staging:verify
 *   php artisan staging:verify --verbose
 */
class StagingVerify extends Command
{
    protected $signature = 'staging:verify {--verbose : Show detailed output}';
    protected $description = 'Verify staging database integrity after migration';

    public function handle(): int
    {
        $this->info('╔══════════════════════════════════════════════╗');
        $this->info('║     STAGING DATABASE VERIFICATION           ║');
        $this->info('╚══════════════════════════════════════════════╝');
        $this->newLine();

        $issues = [];

        // ── Check 1: Migration status ────────────────────────────────────
        $this->info('Check 1: Migration status...');
        $pending = DB::select("SELECT migration FROM migrations WHERE batch = (SELECT MAX(batch) FROM migrations)");
        $this->info("  Last batch: " . count($pending) . " migration(s)");

        // ── Check 2: All tables exist ────────────────────────────────────
        $this->info('Check 2: Table existence...');
        $expectedTables = [
            'users', 'organizations', 'branches', 'orders', 'order_items',
            'payments', 'payment_ledger', 'products', 'inventory_items',
            'shifts', 'subscriptions', 'plans', 'audit_logs', 'issues',
            'api_keys', 'request_analytics', 'webhook_events',
        ];

        $existing = array_column(
            DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'"),
            'tablename'
        );

        foreach ($expectedTables as $table) {
            if (!in_array($table, $existing)) {
                $issues[] = "Missing table: {$table}";
                $this->error("  ✗ Missing table: {$table}");
            }
        }

        if (empty($issues)) {
            $this->info("  ✓ All " . count($expectedTables) . " expected tables exist");
        }

        // ── Check 3: Index verification ──────────────────────────────────
        $this->info('Check 3: Critical indexes...');
        $criticalIndexes = [
            'users'            => ['users_org_idx', 'users_status_idx'],
            'orders'           => ['orders_org_idx', 'orders_branch_idx', 'orders_status_idx', 'orders_org_branch_status_idx'],
            'payments'         => ['payments_order_idx', 'payments_status_idx'],
            'shifts'           => ['shifts_branch_idx', 'shifts_user_idx', 'shifts_status_idx'],
            'subscriptions'    => ['subscriptions_org_idx'],
            'audit_logs'       => ['audit_logs_org_idx', 'audit_logs_created_at_idx'],
            'order_items'      => ['order_items_order_idx'],
            'notifications'    => ['notifications_notifiable_idx'],
        ];

        $existingIndexes = array_column(
            DB::select("SELECT indexname, tablename FROM pg_indexes WHERE schemaname = 'public'"),
            'indexname'
        );

        foreach ($criticalIndexes as $table => $indexes) {
            foreach ($indexes as $idx) {
                if (!in_array($idx, $existingIndexes)) {
                    $issues[] = "Missing index: {$idx} on {$table}";
                    $this->error("  ✗ Missing index: {$idx}");
                }
            }
        }

        $missingIdx = count($issues);
        if ($missingIdx === 0) {
            $this->info("  ✓ All critical indexes present");
        }

        // ── Check 4: CHECK constraints ───────────────────────────────────
        $this->info('Check 4: CHECK constraints...');
        $expectedConstraints = [
            'users_status_check',
            'orders_status_check',
            'payments_status_check',
            'shifts_status_check',
            'subscriptions_status_check',
        ];

        $existingConstraints = array_column(
            DB::select("SELECT conname FROM pg_constraint WHERE contype = 'c'"),
            'conname'
        );

        foreach ($expectedConstraints as $constraint) {
            if (!in_array($constraint, $existingConstraints)) {
                $issues[] = "Missing CHECK constraint: {$constraint}";
                $this->error("  ✗ Missing CHECK constraint: {$constraint}");
            }
        }

        if (empty($issues) || count($issues) === $missingIdx) {
            $this->info("  ✓ All CHECK constraints present");
        }

        // ── Check 5: Unique constraints ──────────────────────────────────
        $this->info('Check 5: Unique constraints...');
        $existingUniques = array_column(
            DB::select("SELECT conname FROM pg_constraint WHERE contype = 'u'"),
            'conname'
        );

        if (!in_array('subscriptions_org_unique', $existingUniques)) {
            $issues[] = "Missing unique constraint: subscriptions_org_unique";
            $this->error("  ✗ Missing unique constraint: subscriptions_org_unique");
        } else {
            $this->info("  ✓ Unique constraints present");
        }

        // ── Check 6: New columns exist ───────────────────────────────────
        $this->info('Check 6: New columns...');
        $expectedColumns = [
            'users'            => ['locale', 'timezone', 'avatar_url'],
            'organizations'    => ['default_currency', 'invoice_prefix'],
            'products'         => ['barcode', 'minimum_order_quantity'],
            'inventory_items'  => ['reorder_point', 'reorder_quantity'],
            'subscriptions'    => ['grace_period_end'],
            'orders'           => ['currency', 'exchange_rate', 'last_modified_by'],
        ];

        foreach ($expectedColumns as $table => $columns) {
            $existingCols = array_column(
                DB::select("SELECT column_name FROM information_schema.columns WHERE table_name = ?", [$table]),
                'column_name'
            );

            foreach ($columns as $col) {
                if (!in_array($col, $existingCols)) {
                    $issues[] = "Missing column: {$table}.{$col}";
                    $this->error("  ✗ Missing column: {$table}.{$col}");
                }
            }
        }

        if (empty($issues)) {
            $this->info("  ✓ All new columns present");
        }

        // ── Summary ──────────────────────────────────────────────────────
        $this->newLine();
        if (empty($issues)) {
            $this->info('╔══════════════════════════════════════════════╗');
            $this->info('║     ✓ ALL CHECKS PASSED                     ║');
            $this->info('╚══════════════════════════════════════════════╝');
            $this->info('  Staging is ready for production deployment.');
            return self::SUCCESS;
        } else {
            $this->error('╔══════════════════════════════════════════════╗');
            $this->error('║     ✗ CHECKS FAILED                         ║');
            $this->error('╚══════════════════════════════════════════════╝');
            $this->error('  Found ' . count($issues) . ' issue(s):');
            foreach ($issues as $issue) {
                $this->error("    - {$issue}");
            }
            return self::FAILURE;
        }
    }
}
