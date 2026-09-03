<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    // Frontend base URL — used to build invite / verification links.
    'frontend' => [
        'base_url' => env('FRONTEND_URL', 'http://localhost:3000'),
    ],

    // Telegram bot used for brand-owner sales notifications.
    // Zero cost: no paid WhatsApp Business API needed.
    'telegram' => [
        'token'    => env('TELEGRAM_BOT_TOKEN'),
        'username' => env('TELEGRAM_BOT_USERNAME'),
    ],

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'paystack' => [
        'public_key'   => env('PAYSTACK_PUBLIC_KEY'),
        'secret_key'   => env('PAYSTACK_SECRET_KEY'),
        'callback_url' => env('PAYSTACK_CALLBACK_URL'),
        // Webhook endpoint protection: secret appended to the webhook URL path
        // (`/api/v1/webhooks/paystack/{webhook_token}`). If empty, webhook
        // delivery is refused (provider + operator must configure it).
        'webhook_token' => env('PAYSTACK_WEBHOOK_TOKEN'),
        // Comma-separated list of IPs/CIDRs permitted to hit the webhook.
        // Empty = allow all (signature + token still enforced).
        'allowed_ips'   => env('PAYSTACK_ALLOWED_IPS', ''),
    ],

    'flutterwave' => [
        'public_key'   => env('FLUTTERWAVE_PUBLIC_KEY'),
        'secret_key'   => env('FLUTTERWAVE_SECRET_KEY'),
        'secret_hash'  => env('FLUTTERWAVE_SECRET_HASH'),
        // Webhook endpoint protection — see `paystack.webhook_token`.
        'webhook_token' => env('FLUTTERWAVE_WEBHOOK_TOKEN'),
        // Comma-separated IPs/CIDRs permitted to hit the webhook.
        'allowed_ips'   => env('FLUTTERWAVE_ALLOWED_IPS', ''),
    ],

    // ── Paid AI / LLM APIs ────────────────────────────────────────────────
    // Rate limits are enforced by the 'throttle:paid_api.{provider}' middleware.
    'openai' => [
        'api_key'    => env('OPENAI_API_KEY'),
        'project_id' => env('OPENAI_PROJECT_ID'),
        'base_url'   => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'rate_limit' => [
            'requests_per_minute' => (int) env('OPENAI_RATE_LIMIT_RPM', 20),
            'window_minutes'      => 1,
        ],
    ],

    'anthropic' => [
        'api_key'    => env('ANTHROPIC_API_KEY'),
        'base_url'   => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com'),
        'rate_limit' => [
            'requests_per_minute' => (int) env('ANTHROPIC_RATE_LIMIT_RPM', 15),
            'window_minutes'      => 1,
        ],
    ],

    'google_ai' => [
        'api_key'    => env('GOOGLE_AI_API_KEY'),
        'base_url'   => env('GOOGLE_AI_BASE_URL', 'https://generativelanguage.googleapis.com'),
        'rate_limit' => [
            'requests_per_minute' => (int) env('GOOGLE_AI_RATE_LIMIT_RPM', 20),
            'window_minutes'      => 1,
        ],
    ],

    'deepseek' => [
        'api_key'    => env('DEEPSEEK_API_KEY'),
        'base_url'   => env('DEEPSEEK_BASE_URL', 'https://api.deepseek.com'),
        'rate_limit' => [
            'requests_per_minute' => (int) env('DEEPSEEK_RATE_LIMIT_RPM', 30),
            'window_minutes'      => 1,
        ],
    ],

];
