<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Manage webhook endpoint URL tokens.
 *
 * The token is appended to the webhook URL path
 * (`/api/v1/webhooks/{provider}/{token}`) and enforced by VerifyWebhook.
 * Generate one per provider, set it in your .env, and configure the full URL
 * in the provider dashboard.
 *
 * Usage:
 *   php artisan webhook:token gen paystack
 *   php artisan webhook:token show flutterwave
 */
class WebhookToken extends Command
{
    protected $signature = 'webhook:token {action=gen : gen for a new token | show for the configured token} {provider=paystack : paystack | flutterwave}';

    protected $description = 'Generate or show the webhook endpoint URL token for a payment provider';

    public function handle(): int
    {
        $action   = strtolower($this->argument('action'));
        $provider = strtolower($this->argument('provider'));

        if (!in_array($provider, ['paystack', 'flutterwave'], true)) {
            $this->error('Provider must be "paystack" or "flutterwave".');
            return self::INVALID;
        }

        $configKey = "services.{$provider}.webhook_token";
        $current   = (string) config($configKey, '');

        if ($action === 'show') {
            if ($current === '') {
                $this->warn("No webhook token configured for {$provider}.");
                $this->comment("Generate one with: php artisan webhook:token gen {$provider}");
                return self::FAILURE;
            }

            $this->info("Configured {$provider} token: {$current}");
            $this->line($this->url($provider, $current));
            return self::SUCCESS;
        }

        if ($action !== 'gen') {
            $this->error("Unknown action \"{$action}\" — use gen or show.");
            return self::INVALID;
        }

        $this->info("Generated {$provider} webhook URL token:");

        $token = bin2hex(random_bytes(32));
        $this->comment($token);
        $this->line('');
        $this->line('Set it in your .env:');
        $this->line(strtoupper($provider) . '_WEBHOOK_TOKEN=' . $token);
        $this->line('');
        $this->line('Then configure this URL in the ' . ucfirst($provider) . " dashboard:");
        $this->line($this->url($provider, $token));

        return self::SUCCESS;
    }

    private function url(string $provider, string $token): string
    {
        return url("api/v1/webhooks/{$provider}/{$token}");
    }
}