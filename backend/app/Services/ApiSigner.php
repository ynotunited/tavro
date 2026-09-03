<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

/**
 * HMAC request-signing core shared by the validation middleware and
 * documentation. Both halves — canonicalisation and verification — live here
 * so the server and client contract stays in exactly one place.
 */
class ApiSigner
{
    public function algorithm(): string
    {
        return Config::get('security.algorithm', 'sha256');
    }

    public function window(): int
    {
        return (int) Config::get('security.window', 300);
    }

    public function methods(): array
    {
        return Config::get('security.methods', ['POST', 'PUT', 'PATCH', 'DELETE']);
    }

    public function isMutating(string $method): bool
    {
        return in_array(strtoupper($method), $this->methods(), true);
    }

    /**
     * Build the canonical string that must be signed by the client.
     * Path is the full request path INCLUDING the API prefix so a signature
     * can never be replayed against a different version/endpoint.
     */
    public function canonical(string $method, string $path, string $rawBody, int $timestamp, string $nonce): string
    {
        return implode("\n", [
            strtoupper($method),
            $path,
            hash($this->algorithm(), $rawBody),
            (string) $timestamp,
            $nonce,
        ]);
    }

    /**
     * Canonicalise an incoming Laravel request directly from the wire.
     */
    public function canonicalForRequest(Request $request): string
    {
        return $this->canonical(
            $request->method(),
            $request->getPathInfo() ?: '/',
            $request->getContent() ?: '',
            (int) $request->header('X-Timestamp', 0),
            (string) $request->header('X-Nonce', ''),
        );
    }

    public function sign(string $secret, string $canonical): string
    {
        return hash_hmac($this->algorithm(), $canonical, $secret);
    }

    public function signRequestFor(string $method, string $path, string $rawBody, string $secret, int $timestamp, string $nonce): string
    {
        return $this->sign($secret, $this->canonical($method, $path, $rawBody, $timestamp, $nonce));
    }

    public function verify(string $secret, string $canonical, string $signature): bool
    {
        if ($secret === '' || $signature === '') {
            return false;
        }

        return hash_equals($this->sign($secret, $canonical), strtolower($signature));
    }

    public function isFresh(int $timestamp, ?int $now = null): bool
    {
        $now ??= time();

        return abs($now - $timestamp) <= $this->window();
    }
}