<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class TenantScopeMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $context = app(TenantContext::class);
        $user = $request->user();

        if ($user?->organization_id !== null) {
            $context->set($user->organization_id);
        }

        try {
            return $next($request);
        } finally {
            $context->clear();
        }
    }
}
