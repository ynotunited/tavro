<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;

/**
 * Setup a staging database from production.
 *
 * This command:
 *   1. Dumps the production database (pg_dump)
 *   2. Creates a staging database if it doesn't exist
 *   3. Restores the dump into staging
 *   4. Verifies table counts match
 *   5. Runs any pending migrations on staging
 *
 * Usage:
 *   php artisan staging:setup
 *   php artisan staging:setup --skip-migrations
 *   php artisan staging:setup --dry-run
 */
class StagingSetup extends Command
{
    protected $signature = 'staging:setup
        {--skip-migrations : Skip running pending migrations after restore}
        {--dry-run : Show what would be done without executing}
        {--fresh : Drop and recreate the staging database}';

    protected $description = 'Mirror production database to staging for safe migration testing';

    private string $prodDb;
    private string $stagingDb;
    private string $stagingHost;
    private int $stagingPort;

    public function handle(): int
    {
        $this->prodDb = config('database.connections.pgsql.database', 'tavro');
        $this->stagingDb = config('database.staging.database', 'tavro_staging');
        $this->stagingHost = config('database.staging.host', '127.0.0.1');
        $this->stagingPort = config('database.staging.port', 5433);

        $this->info('╔══════════════════════════════════════════════╗');
        $this->info('║     TAVRO STAGING ENVIRONMENT SETUP         ║');
        $this->info('╚══════════════════════════════════════════════╝');
        $this->newLine();

        // ── Step 1: Verify production is accessible ──────────────────────
        $this->info('Step 1: Verifying production database...');
        try {
            $prodCount = DB::connection('pgsql')->select('SELECT COUNT(*) as count FROM pg_stat_activity WHERE datname = ?', [$this->prodDb]);
            $this->info("  ✓ Production database '{$this->prodDb}' is accessible");
        } catch (\Exception $e) {
            $this->error("  ✗ Cannot connect to production: {$e->getMessage()}");
            return self::FAILURE;
        }

        // ── Step 2: Check for active connections ─────────────────────────
        $this->info('Step 2: Checking active connections...');
        $connections = DB::connection('pgsql')->select(
            "SELECT count(*) as active FROM pg_stat_activity WHERE datname = ? AND state = 'active'",
            [$this->prodDb]
        );
        $activeCount = $connections[0]->active;
        if ($activeCount > 0) {
            $this->warn("  ⚠ {$activeCount} active connection(s) on production. Proceeding anyway.");
        } else {
            $this->info('  ✓ No active production connections');
        }

        // ── Step 3: Dump production data ─────────────────────────────────
        $this->info('Step 3: Dumping production data...');
        $dumpFile = storage_path('app/staging_dump_' . date('Y-m-d_His') . '.sql');

        if ($this->option('dry-run')) {
            $this->info("  [DRY RUN] Would dump to: {$dumpFile}");
        } else {
            $dumpResult = $this->dumpDatabase($dumpFile);
            if ($dumpResult !== 0) {
                $this->error('  ✗ pg_dump failed. Check that pg_dump is in your PATH.');
                $this->warn('  On Laragon: add C:\\laragon\\bin\\postgres\\pg-15\\bin to PATH');
                return self::FAILURE;
            }
            $size = number_format(filesize($dumpFile) / 1024 / 1024, 2);
            $this->info("  ✓ Dumped {$size} MB to {$dumpFile}");
        }

        // ── Step 4: Create/restore staging database ──────────────────────
        $this->info('Step 4: Setting up staging database...');

        if ($this->option('fresh')) {
            $this->info('  Dropping existing staging database...');
            if (!$this->option('dry-run')) {
                DB::statement("DROP DATABASE IF EXISTS {$this->stagingDb}");
            }
        }

        if (!$this->option('dry-run')) {
            // Create database if it doesn't exist
            $exists = DB::select("SELECT 1 FROM pg_database WHERE datname = ?", [$this->stagingDb]);
            if (empty($exists)) {
                DB::statement("CREATE DATABASE {$this->stagingDb}");
                $this->info("  ✓ Created staging database '{$this->stagingDb}'");
            } else {
                $this->info("  ✓ Staging database '{$this->stagingDb}' already exists");
            }

            // Restore dump into staging
            $restoreResult = $this->restoreDatabase($dumpFile);
            if ($restoreResult !== 0) {
                $this->error('  ✗ pg_restore failed.');
                return self::FAILURE;
            }
            $this->info('  ✓ Restored production data into staging');
        } else {
            $this->info("  [DRY RUN] Would create/restore to '{$this->stagingDb}'");
        }

        // ── Step 5: Verify table counts ─────────────────────────────────
        $this->info('Step 5: Verifying table counts...');
        if (!$this->option('dry-run')) {
            $prodTables = $this->getTableCounts('pgsql');
            $stagingTables = $this->getTableCounts('staging');

            $mismatches = 0;
            foreach ($prodTables as $table => $count) {
                $stagingCount = $stagingTables[$table] ?? 0;
                if ($count !== $stagingCount) {
                    $this->error("  ✗ {$table}: production={$count}, staging={$stagingCount}");
                    $mismatches++;
                }
            }

            if ($mismatches === 0) {
                $this->info("  ✓ All " . count($prodTables) . " tables match");
            } else {
                $this->error("  ✗ {$mismatches} table(s) have mismatched counts");
                return self::FAILURE;
            }
        } else {
            $this->info('  [DRY RUN] Would verify table counts');
        }

        // ── Step 6: Run pending migrations on staging ────────────────────
        if (!$this->option('skip-migrations')) {
            $this->info('Step 6: Running pending migrations on staging...');
            if (!$this->option('dry-run')) {
                // Switch to staging connection temporarily
                config(['database.default' => 'staging']);
                $this->call('migrate', ['--force' => true]);
                config(['database.default' => 'pgsql']);
                $this->info('  ✓ Migrations complete on staging');
            } else {
                $this->info('  [DRY RUN] Would run: php artisan migrate --force');
            }
        } else {
            $this->info('Step 6: Skipped (--skip-migrations)');
        }

        // ── Step 7: Cleanup dump file ────────────────────────────────────
        if (!$this->option('dry-run') && file_exists($dumpFile)) {
            unlink($dumpFile);
            $this->info("Step 7: Cleaned up dump file");
        }

        $this->newLine();
        $this->info('╔══════════════════════════════════════════════╗');
        $this->info('║     STAGING ENVIRONMENT READY               ║');
        $this->info('╚══════════════════════════════════════════════╝');
        $this->newLine();
        $this->info("  Database: {$this->stagingDb}");
        $this->info("  Host: {$this->stagingHost}:{$this->stagingPort}");
        $this->newLine();
        $this->info('  Next steps:');
        $this->info('    1. Run: php artisan migrate --database=staging --force');
        $this->info('    2. Verify: php artisan staging:verify');
        $this->info('    3. Test: php artisan test --database=staging');
        $this->newLine();

        return self::SUCCESS;
    }

    private function dumpDatabase(string $path): int
    {
        $host = config('database.connections.pgsql.host', '127.0.0.1');
        $port = config('database.connections.pgsql.port', 5433);
        $user = config('database.connections.pgsql.username', 'tavro');

        $cmd = "pg_dump -h {$host} -p {$port} -U {$user} -d {$this->prodDb} -F c -f \"{$path}\" 2>&1";

        $result = Process::run($cmd);

        if ($result->exitCode() !== 0) {
            $this->error("  pg_dump stderr: {$result->errorOutput()}");
        }

        return $result->exitCode();
    }

    private function restoreDatabase(string $path): int
    {
        $user = config('database.connections.pgsql.username', 'tavro');

        $cmd = "pg_restore -h {$this->stagingHost} -p {$this->stagingPort} -U {$user} -d {$this->stagingDb} --clean --if-exists \"{$path}\" 2>&1";

        $result = Process::run($cmd);

        if ($result->exitCode() !== 0) {
            // pg_restore returns non-zero on warnings too, check for actual errors
            $output = $result->output() . $result->errorOutput();
            if (str_contains($output, 'FATAL') || str_contains($output, 'PANIC')) {
                $this->error("  pg_restore failed: {$output}");
                return $result->exitCode();
            }
        }

        return 0;
    }

    private function getTableCounts(string $connection): array
    {
        $tables = DB::connection($connection)->select(
            "SELECT tablename FROM pg_tables WHERE schemaname = 'public' ORDER BY tablename"
        );

        $counts = [];
        foreach ($tables as $table) {
            $result = DB::connection($connection)->select("SELECT COUNT(*) as count FROM {$table->tablename}");
            $counts[$table->tablename] = (int) $result[0]->count;
        }

        return $counts;
    }
}
