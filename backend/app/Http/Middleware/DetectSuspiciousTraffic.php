<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class DetectSuspiciousTraffic
{
    private const RATE_THRESHOLD = 120;

    private const BLOCKED_USER_AGENTS = [
        'masscan', 'nikto', 'sqlmap', 'nmap', 'dirbuster', 'gobuster',
        'ffuf', 'wfuzz', 'zgrab', 'censys', 'shodan', 'httpclient',
        'python-requests', 'python-urllib', 'go-http-client', 'curl/',
        'wget/', 'scrapy', 'httrack', 'webcopier', 'teleport',
        'sqlninja', 'w3af', 'acunetix', 'nessus', 'openvas',
        ' Qualys', 'Burp Suite', 'ZmEu', 'Morfeus', 'wpscan',
        'joomscan', 'droopescan', 'Arachni', 'WhatWeb',
    ];

    private const EMPTY_USER_AGENTS = [
        '-', 'null', 'undefined', 'test', 'bot', 'spider', 'crawler',
    ];

    /**
     * SQL injection patterns in query params or body.
     */
    private const SQL_INJECTION_PATTERNS = [
        '/(\b(SELECT|INSERT|UPDATE|DELETE|DROP|UNION|ALTER|CREATE|EXEC|EXECUTE)\b.{0,30}\b(FROM|INTO|SET|WHERE|TABLE|DATABASE)\b)/iu',
        '/(;\s*(DROP|DELETE|UPDATE|INSERT|ALTER)\b)/iu',
        '/(\'\s*(OR|AND)\s+[\'\d])/iu',
        '/(\bSLEEP\s*\(\s*\d)/iu',
        '/(\bBENCHMARK\s*\(\s*\d)/iu',
        '/(\bLOAD_FILE\s*\()/iu',
        '/(\bINTO\s+(OUTFILE|DUMPFILE)\b)/iu',
    ];

    /**
     * XSS patterns in query params or body.
     */
    private const XSS_PATTERNS = [
        '/<script[\s>]/iu',
        '/javascript\s*:/iu',
        '/on\w+\s*=\s*["\']/iu',
        '/<iframe[\s>]/iu',
        '/<object[\s>]/iu',
        '/<embed[\s>]/iu',
        '/eval\s*\(/iu',
        '/document\.(cookie|write|location)/iu',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();
        $path = $request->path();
        $method = $request->method();
        $userAgent = strtolower($request->userAgent() ?? '');

        // Webhook endpoints carry their own dedicated security controls
        // (VerifyWebhook: URL token, provider signature, IP allowlist) plus
        // `throttle:webhooks`. UA heuristics and body-injection scanning are
        // counterproductive there — providers don't send browser UAs, and a
        // legitimate `gateway_response` could trip the SQLi/XSS regexes.
        // Routing is preserved; VerifyWebhook still enforces everything.
        if (str_starts_with($path, 'api/v1/webhooks/')) {
            return $next($request);
        }

        // ── Block known attack tools ────────────────────────────────────────
        foreach (self::BLOCKED_USER_AGENTS as $bad) {
            if (str_contains($userAgent, strtolower($bad))) {
                Log::channel('security')->warning('Blocked known attack tool', [
                    'ip'         => $ip,
                    'user_agent' => $request->userAgent(),
                    'path'       => $path,
                    'matched'    => $bad,
                ]);

                return response()->json(['message' => 'Forbidden.'], 403);
            }
        }

        // ── Block empty or suspiciously short User-Agents ───────────────────
        if (empty($userAgent) || strlen($userAgent) < 5) {
            $cacheKey = 'suspicious_ua:' . $ip;
            $attempts = Cache::increment($cacheKey);

            if ($attempts === 1) {
                Cache::put($cacheKey, 1, 3600);
            }

            if ($attempts > 10) {
                Log::channel('security')->warning('Blocked IP for repeated empty User-Agent', [
                    'ip'      => $ip,
                    'attempts' => $attempts,
                ]);

                return response()->json(['message' => 'Forbidden.'], 403);
            }
        }

        // ── Block scanner paths ─────────────────────────────────────────────
        $attackPaths = [
            '.env', 'wp-admin', 'wp-login.php', 'xmlrpc.php',
            '.git', '.svn', 'phpmyadmin', 'admin', 'debug',
            '.well-known', 'actuator', 'console', 'HNAP1',
            'vendor/phpunit', 'config.php', 'setup.php',
            'install.php', '.htaccess', 'web.config',
            'cgi-bin', 'shell', 'cmd', 'passwd',
            'etc/shadow', 'proc/self', 'TELEGRAM', 'bot.php',
        ];

        foreach ($attackPaths as $attackPath) {
            if (str_contains($path, $attackPath)) {
                Log::channel('security')->warning('Attack path probe detected', [
                    'ip'        => $ip,
                    'method'    => $method,
                    'path'      => $path,
                    'user_agent' => $request->userAgent(),
                    'pattern'   => $attackPath,
                ]);

                return response()->json(['message' => 'Not found.'], 404);
            }
        }

        // ── Inspect query parameters for injection patterns ─────────────────
        $queryString = $request->getQueryString() ?? '';
        if ($this->containsInjectionPatterns($queryString)) {
            Log::channel('security')->warning('Injection pattern in query string', [
                'ip'     => $ip,
                'path'   => $path,
                'query'  => $queryString,
            ]);

            return response()->json(['message' => 'Bad request.'], 400);
        }

        // ── Inspect request body for injection patterns (POST/PUT/PATCH) ────
        if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $content = $request->getContent();
            if ($content && strlen($content) < 50000) {
                if ($this->containsInjectionPatterns($content)) {
                    Log::channel('security')->warning('Injection pattern in request body', [
                        'ip'     => $ip,
                        'path'   => $path,
                        'method' => $method,
                    ]);

                    return response()->json(['message' => 'Bad request.'], 400);
                }
            }
        }

        // ── Track request rate per IP ───────────────────────────────────────
        $cacheKey = 'traffic:' . $ip;
        $count = Cache::increment($cacheKey);

        if ($count === 1) {
            Cache::put($cacheKey, 1, 60);
        }

        if ($count > self::RATE_THRESHOLD) {
            $blockKey = 'blocked_ip:' . $ip;
            $blockCount = Cache::increment($blockKey);

            if ($blockCount === 1) {
                Cache::put($blockKey, 1, 600);
            }

            if ($blockCount >= 2) {
                Log::channel('security')->critical('IP blocked for sustained excessive traffic', [
                    'ip'          => $ip,
                    'requests'    => $count,
                    'block_count' => $blockCount,
                    'user_agent'  => $request->userAgent(),
                ]);

                return response()->json([
                    'message' => 'Too many requests. Your IP has been temporarily blocked.',
                ], 429);
            }

            Log::channel('security')->critical('Excessive request rate detected', [
                'ip'       => $ip,
                'requests' => $count,
                'path'     => $path,
                'method'   => $method,
            ]);
        }

        // ── Concurrent request flood (single user hitting many endpoints) ───
        $concurrentKey = 'concurrent:' . $ip;
        $concurrent = Cache::increment($concurrentKey);
        if ($concurrent === 1) {
            Cache::put($concurrentKey, 1, 10); // 10 second window
        }
        if ($concurrent > 50) {
            Log::channel('security')->critical('Concurrent request flood detected', [
                'ip'          => $ip,
                'concurrent'  => $concurrent,
                'user_agent'  => $request->userAgent(),
            ]);

            return response()->json([
                'message' => 'Too many concurrent requests.',
            ], 429);
        }

        $response = $next($request);

        // Decrement concurrent counter
        if ($concurrent > 0) {
            Cache::decrement($concurrentKey);
        }

        // ── Log 4xx and 5xx responses ───────────────────────────────────────
        $status = $response->getStatusCode();

        if ($status >= 400) {
            $logLevel = $status >= 500 ? 'error' : 'warning';

            Log::channel('security')->$logLevel('API error response', [
                'ip'         => $ip,
                'method'     => $method,
                'path'       => $path,
                'status'     => $status,
                'user_agent' => $request->userAgent(),
                'user_id'    => $request->user()?->id,
            ]);

            // Track repeated 404s — vulnerability scanner detection
            if ($status === 404) {
                $fourOhFourKey = '404s:' . $ip;
                $fourOhFourCount = Cache::increment($fourOhFourKey);

                if ($fourOhFourCount === 1) {
                    Cache::put($fourOhFourKey, 1, 300);
                }

                if ($fourOhFourCount > 30) {
                    Log::channel('security')->critical('Possible vulnerability scanner — excessive 404s', [
                        'ip'        => $ip,
                        'count'     => $fourOhFourCount,
                        'user_agent' => $request->userAgent(),
                    ]);
                }
            }

            // Track authentication failures per IP (possible credential stuffing)
            if ($status === 401) {
                $authFailKey = 'auth_fails:' . $ip;
                $authFails = Cache::increment($authFailKey);
                if ($authFails === 1) {
                    Cache::put($authFailKey, 1, 300);
                }
                if ($authFails > 20) {
                    Log::channel('security')->critical('Possible credential stuffing — excessive 401s', [
                        'ip'     => $ip,
                        'count'  => $authFails,
                    ]);
                }
            }
        }

        return $response;
    }

    /**
     * Check if a string contains SQL injection or XSS patterns.
     */
    private function containsInjectionPatterns(string $input): bool
    {
        foreach (self::SQL_INJECTION_PATTERNS as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        foreach (self::XSS_PATTERNS as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }
}
