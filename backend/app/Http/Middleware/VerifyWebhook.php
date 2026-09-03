<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Webhook endpoint protection.
 *
 * Every provider webhook passes through three independent checks before the
 * controller can process it:
 *
 *  1. Endpoint URL token  — the provider must POST to `/webhooks/{provider}/{token}`.
 *     The token is compared in constant time against the configured value. This
 *     hides the endpoint from scanners and gives a second factor beyond signing.
 *     If no token is configured the endpoint is refused (503) so providers never
 *     silently deliver unauthenticated traffic.
 *
 *  2. IP allowlist       — when `services.{provider}.allowed_ips` is non-empty,
 *     the caller must originate from one of the listed IPs or CIDR ranges.
 *
 *  3. Provider signature — the provider-specific secret handshake:
 *       - Paystack:    `X-Paystack-Signature` = HMAC-SHA512(raw body, secret_key)
 *       - Flutterwave: `verif-hash` = configured secret hash
 *
 * Signature verification is centralized here so EVERY webhook receives it —
 * there is no per-controller path that can forget a check.
 */
class VerifyWebhook
{
    private const PROVIDER_HEADERS = [
        'paystack'    => 'x-paystack-signature',
        'flutterwave' => 'verif-hash',
    ];

    public function handle(Request $request, Closure $next, string $provider): Response
    {
        $config = (array) config("services.{$provider}", []);
        $sender = [
            'provider' => $provider,
            'ip'       => $request->ip(),
            'ua'       => $request->userAgent(),
            'path'     => $request->path(),
        ];

        // ── 1. Endpoint URL token protection ────────────────────────────────
        $expectedToken = $config['webhook_token'] ?? '';

        if ($expectedToken === '') {
            Log::channel('security')->warning('Webhook endpoint not configured — refusing delivery', $sender);
            return response()->json(['message' => 'Webhook endpoint is not configured.'], 503);
        }

        $pathToken = (string) $request->route('token');

        if ($pathToken === '' || !hash_equals($expectedToken, $pathToken)) {
            Log::channel('security')->warning('Webhook URL token mismatch', $sender);
            // 404 (not 403/401) so URIs with a wrong token are indistinguishable
            // from resources that simply don't exist.
            return response()->json(['message' => 'Not found.'], 404);
        }

        // ── 2. IP allowlist ─────────────────────────────────────────────────
        $allowed = $this->parseAllowlist($config['allowed_ips'] ?? '');

        if (!empty($allowed) && !$this->ipMatches($request->ip(), $allowed)) {
            Log::channel('security')->warning('Webhook blocked by IP allowlist', $sender);
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        // ── 3. Provider signature verification ─────────────────────────────
        if (!$this->verifySignature($provider, $request, $config)) {
            Log::channel('security')->warning("{$provider} webhook signature mismatch", $sender);
            return response()->json(['message' => 'Invalid signature.'], 400);
        }

        // Stamp metadata the controller/audit trail can use.
        $request->attributes->set('webhook_verified_at', now()->toIso8601String());
        $request->attributes->set('webhook_sender_ip', $request->ip());

        return $next($request);
    }

    private function verifySignature(string $provider, Request $request, array $config): bool
    {
        if (!isset(self::PROVIDER_HEADERS[$provider])) {
            return false;
        }

        $headerKey = self::PROVIDER_HEADERS[$provider];
        $provided  = (string) $request->header($headerKey);

        if ($provider === 'paystack') {
            $secret = $config['secret_key'] ?? '';
            if ($secret === '') {
                return false;
            }
            $expected = hash_hmac('sha512', $request->getContent(), $secret);

            return hash_equals($expected, $provided);
        }

        if ($provider === 'flutterwave') {
            $secret = $config['secret_hash'] ?? '';
            if ($secret === '') {
                return false;
            }

            return hash_equals((string) $secret, $provided);
        }

        return false;
    }

    /**
     * Parse a comma-separated list of IPs and CIDR ranges into a normalized set.
     *
     * @return array<int, string>
     */
    private function parseAllowlist(string $raw): array
    {
        return array_values(array_filter(array_map(
            fn (string $entry) => trim($entry),
            explode(',', $raw),
        ), fn (string $entry) => $entry !== ''));
    }

    /**
     * Match a request IP against the allowlist (supports CIDR + bare IPs).
     */
    private function ipMatches(?string $ip, array $allowed): bool
    {
        if (!$ip) {
            return false;
        }

        foreach ($allowed as $range) {
            if ($this->ipInRange($ip, $range)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Pure-IPv4/IPv6 CIDR membership test (no external deps).
     */
    private function ipInRange(string $ip, string $range): bool
    {
        [$net, $bits] = array_pad(explode('/', $range, 2), 2, null);

        if ($net === '' || !filter_var($net, FILTER_VALIDATE_IP)) {
            return false;
        }

        $isV6 = str_contains($net, ':');

        $ipBin   = $isV6
            ? inet_pton($ip)     // throws on mismatch — guard below
            : inet_pton($ip);
        $netBin  = inet_pton($net);

        if ($ipBin === false || $netBin === false || strlen($ipBin) !== strlen($netBin)) {
            return false;
        }

        $maxBits = strlen($ipBin) * 8;
        $bits    = $bits === null ? $maxBits : (int) $bits;

        if ($bits < 0 || $bits > $maxBits) {
            return false;
        }

        // mask = first $bits bits of the network address
        $mask = $bits === 0
            ? ''
            : str_repeat("\xff", intdiv($bits, 8)) . chr((0xff << (8 - ($bits % 8))) & 0xff);

        $mask = str_pad($mask, strlen($ipBin), "\0");

        return ($ipBin & $mask) === ($netBin & $mask);
    }
}