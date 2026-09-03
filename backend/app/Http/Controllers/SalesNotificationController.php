<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Services\SalesReportBuilder;
use App\Services\TelegramService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Brand-owner sales-notification channel.
 *
 * The owner (and only the owner) can link a free Telegram chat via a pairing
 * code, then receive hourly / daily / weekly sales digests. All mutating calls
 * are owner-gated; reads are visible to any member of the org.
 */
class SalesNotificationController extends Controller
{
    use ApiResponse;

    /** Role(s) allowed to configure the owner's report channel. */
    private const OWNER_ROLES = ['owner', 'general_manager'];

    public function __construct(
        private readonly TelegramService $telegram,
        private readonly SalesReportBuilder $builder,
    ) {}

    public function settings(Request $request)
    {
        $org = $this->org($request);

        return $this->success([
            'telegram_configured' => $this->telegram->configured(),
            'bot_username' => $this->telegram->botUsername(),
            'connected' => $org->telegram_chat_id !== null,
            'sales_reports_enabled' => (bool) $org->sales_reports_enabled,
            'sales_report_frequency' => $org->sales_report_frequency,
            'last_sent_at' => $org->sales_reports_last_sent_at,
        ]);
    }

    public function updateSettings(Request $request)
    {
        $org = $this->org($request);
        $this->authorizeOwner($request, $org);

        $validated = $request->validate([
            'sales_reports_enabled' => 'sometimes|boolean',
            'sales_report_frequency' => 'sometimes|string|in:hourly,daily,weekly',
        ]);

        $enabled = (bool) ($validated['sales_reports_enabled'] ?? $org->sales_reports_enabled);

        if ($enabled && $org->telegram_chat_id === null) {
            return $this->error('Link a Telegram chat before turning reports on.', 422);
        }

        $org->update($validated);

        if ($enabled) {
            $org->forceFill(['sales_reports_last_sent_at' => now()])->save();
        }

        return $this->success($this->masked($org, $request), 'Notification settings saved.');
    }

    /**
     * Fresh pairing code. The owner sends it to the bot on Telegram
     * (e.g. "tavro ABC123"); the telegram:poll command links the chat to this org.
     */
    public function generatePairCode(Request $request)
    {
        $org = $this->org($request);
        $this->authorizeOwner($request, $org);

        $code = strtoupper(Str::random(6));
        $expires = now()->addMinutes(15);

        $org->forceFill([
            'telegram_pair_code' => $code,
            'telegram_pair_code_expires_at' => $expires,
        ])->save();

        $username = $this->telegram->botUsername();

        $paste = $username
            ? "Open Telegram → find @{$username} → send this code:\n\ntavro {$code}"
            : "Pairing code:\n\ntavro {$code}";

        return $this->success([
            'code' => $code,
            'expires_at' => $expires,
            'bot_username' => $username,
            'telegram_configured' => $this->telegram->configured(),
            'instructions' => $paste,
            'connected' => $org->telegram_chat_id !== null,
        ], 'Pairing code generated (15 min expiry).', 201);
    }

    public function disconnect(Request $request)
    {
        $org = $this->org($request);
        $this->authorizeOwner($request, $org);

        $org->forceFill([
            'telegram_chat_id' => null,
            'telegram_pair_code' => null,
            'telegram_pair_code_expires_at' => null,
            'sales_reports_enabled' => false,
            'sales_reports_last_sent_at' => null,
        ])->save();

        return $this->success($this->masked($org, $request), 'Telegram chat disconnected.');
    }

    public function sendTest(Request $request)
    {
        $org = $this->org($request);
        $this->authorizeOwner($request, $org);

        if (! $org->telegram_chat_id) {
            return $this->error('Link a Telegram chat first.', 422);
        }

        $message = '🔔 Tavro test notification'.PHP_EOL.PHP_EOL
            .$this->builder->build($org, 'right now');

        return $this->telegram->sendMessage($org->telegram_chat_id, $message)
            ? $this->success(null, 'Test notification sent to your Telegram.')
            : $this->error('Could not reach Telegram. Check TELEGRAM_BOT_TOKEN.', 502);
    }

    private function org(Request $request): Organization
    {
        return Organization::findOrFail($request->user()->organization_id);
    }

    private function authorizeOwner(Request $request, Organization $org): void
    {
        abort_if($request->user()->organization_id !== $org->id, 404);

        if (! $request->user()->hasAnyRole(self::OWNER_ROLES)) {
            abort(403, 'Only the brand owner can change notification settings.');
        }
    }

    /** Stropped view of the channel state for responses. */
    private function masked(Organization $org, Request $request): array
    {
        return [
            'connected' => $org->telegram_chat_id !== null,
            'sales_reports_enabled' => (bool) $org->sales_reports_enabled,
            'sales_report_frequency' => $org->sales_report_frequency,
            'last_sent_at' => $org->sales_reports_last_sent_at,
        ];
    }
}
