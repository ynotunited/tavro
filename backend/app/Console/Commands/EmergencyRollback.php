<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Emergency rollback: undo the last migration batch immediately.
 *
 * This is NOT a normal rollback. It's designed for production emergencies:
 *   1. Captures the current migration state
 *   2. Runs the down() method for each migration in the batch
 *   3. Verifies the rollback completed cleanly
 *   4. Logs everything for post-mortem
 *
 * Usage:
 *   php artisan migrate:emergency-rollback
 *   php artisan migrate:emergency-rollback --batch=15
 *   php artisan migrate:emergency-rollback --dry-run
 *   php artisan migrate:emergency-rollback --force
 */
class EmergencyRollback extends Command
{
    protected $signature = 'migrate:emergency-rollback
        {--batch= : Rollback a specific batch (default: last)}
        {--dry-run : Show what would be rolled back without executing}
        {--force : Skip confirmation prompt}';

    protected $description = 'Emergency rollback of the last migration batch with verification';

    public function handle(): int
    {
        $this->info('╔══════════════════════════════════════════════╗');
        $this->info('║     EMERGENCY MIGRATION ROLLBACK            ║');
        $this->info('╚══════════════════════════════════════════════╝');
        $this->newLine();

        // ── Step 1: Determine which batch to rollback ────────────────────
        $batch = $this->option('batch');
        if (!$batch) {
            $result = DB::select("SELECT MAX(batch) as max_batch FROM migrations");
            $batch = $result[0]->max_batch;
        }

        if (!$batch) {
            $this->error('  ✗ No migrations to rollback.');
            return self::FAILURE;
        }

        $migrations = DB::select(
            "SELECT migration, batch FROM migrations WHERE batch = ? ORDER BY id DESC",
            [$batch]
        );

        $this->warn("  Target batch: {$batch}");
        $this->info("  Migrations to rollback: " . count($migrations));
        foreach ($migrations as $m) {
            $this->info("    - {$m->migration}");
        }
        $this->newLine();

        // ── Step 2: Capture pre-rollback state ───────────────────────────
        $preState = $this->captureState();
        $this->info("  Pre-rollback state captured: " . count($preState['tables']) . " tables");

        // ── Step 3: Confirmation ─────────────────────────────────────────
        if (!$this->option('force') && !$this->option('dry-run')) {
            if (!$this->confirm('  This will rollback batch ' . $batch . '. Continue?', false)) {
                $this->info('  Rollback cancelled.');
                return self::SUCCESS;
            }
        }

        // ── Step 4: Execute rollback ─────────────────────────────────────
        $this->info('Step 4: Executing rollback...');
        $this->newLine();

        $rolledBack = 0;
        $failed = false;

        foreach ($migrations as $migration) {
            $this->info("  Rolling back: {$migration->migration}");

            if ($this->option('dry-run')) {
                $this->info("    [DRY RUN] Would execute down() for {$migration->migration}");
                $rolledBack++;
                continue;
            }

            try {
                $path = $this->getMigrationPath($migration->migration);
                if (!$path) {
                    $this->error("    ✗ Migration file not found: {$migration->migration}");
                    $failed = true;
                    break;
                }

                $migrationFile = require $path;
                $instance = is_object($migrationFile) ? $migrationFile : new $migrationFile();

                if (method_exists($instance, 'down')) {
                    $instance->down();
                    DB::table('migrations')
                        ->where('migration', $migration->migration)
                        ->delete();
                    $this->info("    ✓ Rolled back successfully");
                    $rolledBack++;
                } else {
                    $this->warn("    ⚠ No down() method — skipped");
                }
            } catch (\Exception $e) {
                $this->error("    ✗ FAILED: {$e->getMessage()}");
                $this->error("    File: {$e->getFile()}:{$e->getLine()}");
                $failed = true;

                // Log the failure
                $this->logFailure($migration->migration, $e);

                break;
            }
        }

        $this->newLine();

        // ── Step 5: Verify rollback ──────────────────────────────────────
        if (!$this->option('dry-run') && !$failed) {
            $this->info('Step 5: Verifying rollback...');
            $postState = $this->captureState();

            // Check that we're back to pre-rollback state
            $missingTables = array_diff($preState['tables'], $postState['tables']);
            if (!empty($missingTables)) {
                $this->error("  ✗ Tables that should exist but don't:");
                foreach ($missingTables as $t) {
                    $this->error("    - {$t}");
                }
            }

            $remainingMigrations = DB::select(
                "SELECT COUNT(*) as count FROM migrations WHERE batch = ?", [$batch]
            );

            if ($remainingMigrations[0]->count > 0) {
                $this->error("  ✉ Still " . $remainingMigrations[0]->count . " migration(s) in batch {$batch}");
            } else {
                $this->info("  ✓ Batch {$batch} completely rolled back");
            }
        }

        // ── Summary ──────────────────────────────────────────────────────
        $this->newLine();
        if ($failed) {
            $this->error('╔══════════════════════════════════════════════╗');
            $this->error('║     ✗ ROLLBACK FAILED                       ║');
            $this->error('╚══════════════════════════════════════════════╝');
            $this->newLine();
            $this->warn('  IMMEDIATE ACTIONS:');
            $this->warn('  1. Check the error message above');
            $this->warn('  2. Review the failure log in storage/logs/');
            $this->warn('  3. Fix the issue manually if needed');
            $this->warn('  4. Re-run: php artisan migrate:emergency-rollback');
            $this->newLine();
            return self::FAILURE;
        }

        $this->info('╔══════════════════════════════════════════════╗');
        $this->info('║     ✓ ROLLBACK COMPLETE                     ║');
        $this->info('╚══════════════════════════════════════════════╝');
        $this->newLine();
        $this->info("  Rolled back: {$rolledBack} migration(s)");
        $this->info("  Batch: {$batch}");
        $this->newLine();

        return self::SUCCESS;
    }

    private function getMigrationPath(string $migrationName): ?string
    {
        $path = database_path("migrations/{$migrationName}.php");
        if (file_exists($path)) {
            return $path;
        }

        // Search in subdirectories
        $files = glob(database_path("migrations/**/*.php"));
        foreach ($files as $file) {
            if (basename($file, '.php') === $migrationName) {
                return $file;
            }
        }

        return null;
    }

    private function captureState(): array
    {
        $tables = array_column(
            DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'public' ORDER BY tablename"),
            'tablename'
        );

        $migrationCount = DB::select("SELECT COUNT(*) as count FROM migrations")[0]->count;

        return [
            'tables' => $tables,
            'migration_count' => $migrationCount,
        ];
    }

    private function logFailure(string $migration, \Exception $e): void
    {
        $log = [
            'timestamp' => now()->toIso8601String(),
            'migration' => $migration,
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ];

        $logPath = storage_path('logs/migration_failures.log');
        file_put_contents($logPath, json_encode($log) . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
