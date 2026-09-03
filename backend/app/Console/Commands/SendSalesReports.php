<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Services\SalesReportBuilder;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Push periodic sales digests to brand owners on Telegram.
 *
 * Frequency is per-organization (hourly / daily / weekly); each org is only
 * messaged when its digest is actually due, so running this frequently is safe.
 *
 * Run via scheduler: $schedule->command('sales:notify:send')->everyFiveMinutes();
 */
class SendSalesReports extends Command
{
    protected $signature = 'sales:notify:send {--force : Send even when the digest is not due yet}';

    protected $description = 'Send scheduled sales reports to brand owners on Telegram';

    public function handle(TelegramService $telegram, SalesReportBuilder $builder): int
    {
        if (! $telegram->configured()) {
            $this->warn('TELEGRAM_BOT_TOKEN is not set — reports disabled.');

            return self::SUCCESS;
        }

        $orgs = Organization::where('sales_reports_enabled', true)
            ->whereNotNull('telegram_chat_id')
            ->get();

        $sent = 0;
        $skipped = 0;

        foreach ($orgs as $org) {
            if (! $this->option('force') && ! $builder->isDue($org)) {
                $skipped++;

                continue;
            }

            $message = $builder->build($org);

            if ($telegram->sendMessage($org->telegram_chat_id, $message)) {
                $org->forceFill(['sales_reports_last_sent_at' => now()])->save();
                $sent++;
                $this->info("Sent {$org->sales_report_frequency} report → {$org->name}");
            } else {
                Log::warning("[telegram] digest failed for organization {$org->id}");
            }
        }

        $this->info("Done: {$sent} sent, {$skipped} not due.");

        return self::SUCCESS;
    }
}
