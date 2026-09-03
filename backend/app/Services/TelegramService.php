<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Minimal Telegram Bot API client used for brand-owner sales notifications.
 *
 * Telegram is the *totally free* channel (unlike the paid WhatsApp Business
 * API), so the pairing + report commands talk through here exclusively. If the
 * TELEGRAM_BOT_TOKEN env is not set the service reports "not configured" and
 * every outward call no-ops instead of throwing — the app keeps working fine
 * without it.
 */
class TelegramService
{
    private const BASE = 'https://api.telegram.org/bot';

    public function token(): ?string
    {
        $token = (string) config('services.telegram.token');

        return $token === '' ? null : $token;
    }

    public function configured(): bool
    {
        return $this->token() !== null;
    }

    /**
     * Cached bot handle shown to the owner in the pairing instructions.
     */
    public function botUsername(): ?string
    {
        $env = (string) config('services.telegram.username');
        if ($env !== '') {
            return ltrim($env, '@');
        }

        if (! $this->configured()) {
            return null;
        }

        return Cache::remember('telegram.bot_username', 3600, function () {
            $res = Http::timeout(10)->get(self::BASE.$this->token().'/getMe');

            return $res->successful() && ($res->json('result.username') ?? '')
                ? $res->json('result.username')
                : null;
        });
    }

    public function sendMessage(int|string $chatId, string $text): bool
    {
        if (! $this->configured()) {
            Log::info('[telegram] not configured; skipping send to '.$chatId);

            return false;
        }

        $res = Http::timeout(15)->post(self::BASE.$this->token().'/sendMessage', [
            'chat_id' => $chatId,
            'text' => $text,
        ]);

        if (! $res->successful()) {
            Log::warning('[telegram] send failed: '.$res->status().' '.$res->body());

            return false;
        }

        return true;
    }

    /**
     * Fetch a single batch of updates (short timeout so the poll command is
     * scheduler-friendly). Returns the raw `result` array.
     */
    public function getUpdates(?int $offset = null): array
    {
        if (! $this->configured()) {
            return [];
        }

        $res = Http::timeout(10)->post(self::BASE.$this->token().'/getUpdates', [
            'offset' => $offset ?? 0,
            'timeout' => 1,
        ]);

        if (! $res->successful() || ! $res->json('ok')) {
            Log::warning('[telegram] getUpdates failed: '.($res->body() ?: 'no response'));

            return [];
        }

        return $res->json('result', []);
    }

    /**
     * Pull the chat id out of an update (private chats presumably, since the
     * owner DMs the bot the pairing code).
     */
    public function chatIdFromUpdate(array $update): ?int
    {
        $chatId = data_get($update, 'message.chat.id')
            ?? data_get($update, 'edited_message.chat.id')
            ?? data_get($update, 'channel_post.chat.id');

        return $chatId !== null ? (int) $chatId : null;
    }

    /**
     * Extract a Tavro pairing code from a user's message text.
     * Accepted forms: "ABC123", "tavro ABC123", "/tavro ABC123", "start ABC123".
     */
    public function pairingCodeFromUpdate(array $update): ?string
    {
        $text = data_get($update, 'message.text')
            ?? data_get($update, 'edited_message.text');

        if (! is_string($text)) {
            return null;
        }

        if (preg_match('/(?:tavro|start|code)?\s*([A-Z0-9]{6,8})\b/i', trim($text), $m)) {
            return strtoupper($m[1]);
        }

        return null;
    }
}
