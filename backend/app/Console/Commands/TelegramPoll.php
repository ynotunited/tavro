<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Pair Telegram chats to organizations.
 *
 * Long-polls the bot for messages; when an owner sends their pairing code
 * ("tavro ABC123") the update is matched against organizations.telegram_pair_code
 * and the chat id is bound to that org (after which digests can flow).
 *
 * Run every minute via scheduler: $schedule->command('telegram:poll')->everyMinute();
 */
class TelegramPoll extends Command
{
    protected $signature = 'telegram:poll
        {--once : Single poll, then exit}
        {--timeout=25 : Maximum seconds this run may spend polling}';

    protected $description = 'Bind Telegram chats to organizations via pairing codes';

    public function handle(TelegramService $telegram): int
    {
        if (! $telegram->configured()) {
            $this->warn('TELEGRAM_BOT_TOKEN is not set — pairing disabled.');

            return self::SUCCESS;
        }

        $deadline = microtime(true) + (int) $this->option('timeout');
        $offset = Cache::get('telegram.poll_offset', 0);

        do {
            $updates = $telegram->getUpdates($offset);

            foreach ($updates as $update) {
                $updateId = (int) ($update['update_id'] ?? 0);
                $offset = max($offset, $updateId + 1);

                $chatId = $telegram->chatIdFromUpdate($update);
                $code = $telegram->pairingCodeFromUpdate($update);

                if ($chatId === null || $code === null) {
                    continue;
                }

                $this->attemptPair($telegram, $chatId, $code);
            }

            if ($this->option('once') || $this->option('timeout') == 0) {
                break;
            }
        } while (microtime(true) < $deadline);

        Cache::put('telegram.poll_offset', $offset, 3600);

        return self::SUCCESS;
    }

    private function attemptPair(TelegramService $telegram, int $chatId, string $code): void
    {
        $org = Organization::where('telegram_pair_code', $code)
            ->where('telegram_pair_code_expires_at', '>', now())
            ->first();

        if (! $org) {
            $telegram->sendMessage($chatId, '❌ That Tavro pairing code is not recognised or has expired. Open the Tavro app to generate a fresh one.');

            return;
        }

        $org->forceFill([
            'telegram_chat_id' => (string) $chatId,
            'telegram_pair_code' => null,
            'telegram_pair_code_expires_at' => null,
        ])->save();

        $username = $telegram->botUsername();
        $handle = $username ? '@'.$username : 'this bot';

        $telegram->sendMessage(
            $chatId,
            "✅ Tavro connected to {$org->name}!".PHP_EOL
            .PHP_EOL
            ."You'll now receive sales digests here. To pick the frequency or switch off, open Tavro → Settings → Notifications."
        );

        Log::info("[telegram] paired chat {$chatId} to organization {$org->id} ({$org->name}) via {$handle}");
        $this->info("Paired chat {$chatId} → {$org->name}");
    }
}
