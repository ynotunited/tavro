<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * WEBHOOK HARDENING — provider-scoped idempotency + delivery tracking.
 *
 * Changes:
 *   1. Replace the single-column `event_id` unique constraint with a composite
 *      `(provider, event_id)` unique. Event IDs are namespaced per provider, so
 *      "92437" from Paystack and "92437" from Flutterwave must not collide.
 *
 *   2. Add idempotency tracking columns used by the atomic claim flow:
 *        - payload_hash  — SHA-256 of the raw body (tamper/trace integrity)
 *        - received_ip   — caller IP at delivery time
 *        - attempts      — delivery attempts observed (replay / retry counter)
 *        - processed_at  — when the event reached a terminal state
 *
 *   3. Add a CHECK constraint enforcing the idempotency state machine:
 *        RECEIVED   → first atomic insert (claimed)
 *        PROCESSED  → handled successfully (terminal — dedup returns 'duplicate')
 *        DUPLICATE  → acknowledged replay of a processed event
 *        FAILED     → processing errored (provider retries re-claim it)
 *
 * Because the old unique constraint covered ALL providers, migrating to the
 * composite key is safe: the composite is strictly wider on the existing unique
 * space, so no row can become ambiguous.
 */
return new class extends Migration
{
    private array $appliedConstraints = [];

    public function up(): void
    {
        // 1. Composite unique (provider, event_id) — the idempotency guard.
        $this->dropConstraintSafely('webhook_events', 'webhook_events_event_id_unique');
        $this->addUniqueConstraintSafely('webhook_events', ['provider', 'event_id'],
            'webhook_events_provider_event_id_unique');

        // 2. Delivery tracking columns (additive, nullable/defaulted).
        Schema::table('webhook_events', function (Blueprint $table) {
            $table->string('payload_hash', 64)->nullable()->after('payload');
            $table->string('received_ip', 45)->nullable()->after('payload_hash');
            $table->unsignedInteger('attempts')->default(1)->after('received_ip');
            $table->timestamp('processed_at')->nullable()->after('attempts');
        });

        // 3. Idempotency state machine guard.
        $this->addCheckConstraint('webhook_events', 'webhook_events_status_check',
            "status IN ('RECEIVED', 'PROCESSED', 'DUPLICATE', 'FAILED')");
    }

    public function down(): void
    {
        $this->dropCheckConstraint('webhook_events', 'webhook_events_status_check');

        Schema::table('webhook_events', function (Blueprint $table) {
            $table->dropColumn(['payload_hash', 'received_ip', 'attempts', 'processed_at']);
        });

        $this->dropConstraintSafely('webhook_events', 'webhook_events_provider_event_id_unique');
        $this->addUniqueConstraintSafely('webhook_events', ['event_id'], 'webhook_events_event_id_unique');
    }

    private function addUniqueConstraintSafely(string $table, array $columns, string $name): void
    {
        $exists = DB::select(
            "SELECT 1 FROM pg_constraint WHERE conname = :name AND conrelid = :table::regclass",
            ['name' => $name, 'table' => $table]
        );

        if (empty($exists)) {
            Schema::table($table, fn (Blueprint $t) => $t->unique($columns, $name));
            $this->appliedConstraints[] = $name;
        }
    }

    private function dropConstraintSafely(string $table, string $name): void
    {
        $exists = DB::select(
            "SELECT 1 FROM pg_constraint WHERE conname = :name AND conrelid = :table::regclass",
            ['name' => $name, 'table' => $table]
        );

        if (!empty($exists)) {
            Schema::table($table, fn (Blueprint $t) => $t->dropUnique($name));
        }
    }

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
};