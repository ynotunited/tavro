<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use App\Services\ApiSigner;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

/**
 * HMAC request-signature enforcement.
 *
 * Every mutating call (POST/PUT/PATCH/DELETE) must carry:
 *   X-Timestamp — Unix seconds at signing time (anti-replay freshness)
 *   X-Nonce     — UUID, used once (replay detection)
 *   X-Signature — hex HMAC-SHA256 over METHOD\n{path}\n{sha256(body)}\n{ts}\n{nonce}
 *
 * The secret is bound to the credential:
 *   - Bearer token (first-party POS)      → signing_secret on the Sanctum token
 *   - X-Api-Key (gateway / integrations)  → signing_secret on the API key
 *
 * Read-only requests (GET/HEAD/OPTIONS) pass through untouched.
 */
class VerifyRequestSignature
{
    public function __construct(private readonly ApiSigner $signer)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        // Only mutating endpoints require a signature.
        if (!$this->signer->isMutating($request->method())) {
            return $next($request);
        }

        $timestamp = (int) $request->header('X-Timestamp');
        $nonce     = (string) $request->header('X-Nonce');
        $signature = (string) $request->header('X-Signature');

        if ($timestamp === 0 || $nonce === '' || $signature === '') {
            return $this->reject($request, 'MISSING_SIGNATURE', 'This endpoint requires request signing. Supply X-Timestamp, X-Nonce and X-Signature headers.');
        }

        if (!ctype_digit($request->header('X-Timestamp'))) {
            return $this->reject($request, 'INVALID_TIMESTAMP', 'X-Timestamp must be a Unix timestamp in seconds.');
        }

        if (!$this->signer->isFresh($timestamp)) {
            return $this->reject($request, 'STALE_TIMESTAMP', 'The request timestamp is outside the allowed freshness window. Please sync your clock and retry.');
        }

        $secret = $this->resolveSecret($request);

        if (!$secret) {
            return $this->reject($request, 'SIGNING_SECRET_MISSING', 'No signing credential is associated with this account/auth token. Re-authenticate or regenerate your API key.');
        }

        // Replay protection — nonce must never have been seen before.
        // SETNX is atomic: returns true only the first time this nonce is used.
        $nonceKey      = config('security.nonce_prefix') . hash('sha256', $nonce);
        $nonceTtl      = $this->signer->window() + (int) config('security.nonce_buffer', 60);
        $nonceReserved = Redis::connection()->set($nonceKey, 1, 'EX', $nonceTtl, 'NX');

        if (!$nonceReserved) {
            return $this->reject($request, 'REPLAYED_NONCE', 'This request nonce has already been used. Generate a new X-Nonce per request.');
        }

        $canonical = $this->signer->canonicalForRequest($request);

        if (!$this->signer->verify($secret, $canonical, $signature)) {
            return $this->reject($request, 'INVALID_SIGNATURE', 'Signature verification failed. Check the signing secret and canonical payload.');
        }

        $request->attributes->set('signed_request', true);

        return $next($request);
    }

    /**
     * Resolve the HMAC secret bound to the presented credential.
     * Prefers an already-validated API key (from api.gateway), then falls back
     * to the Sanctum bearer token's signing secret.
     */
    private function resolveSecret(Request $request): ?string
    {
        $apiKey = $request->attributes->get('api_key');

        if ($apiKey instanceof ApiKey) {
            return $this->decrypt($apiKey->signing_secret);
        }

        $rawKey = $request->header('X-Api-Key');

        if ($rawKey) {
            $candidate = ApiKey::where('key_hash', ApiKey::hashKey($rawKey))->first();

            return $candidate ? $this->decrypt($candidate->signing_secret) : null;
        }

        $token = $request->user()?->currentAccessToken();

        if ($token) {
            return $this->decrypt($token->signing_secret);
        }

        return null;
    }

    private function decrypt(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function reject(Request $request, string $code, string $message): Response
    {
        Log::channel('security')->warning('Request signature rejected', [
            'code'   => $code,
            'method' => $request->method(),
            'path'   => $request->path(),
            'ip'     => $request->ip(),
            'user'   => $request->user()?->id,
        ]);

        return response()->json([
            'message' => $message,
            'code'    => $code,
        ], 401);
    }
}