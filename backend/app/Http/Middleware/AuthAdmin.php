<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guard for the admin (dev company) panel routes.
 *
 * Enforces the dedicated 'admin' session guard. Unauthenticated callers get a
 * JSON 401; authenticated but deactivated admins get a 403. This middleware
 * must be present on EVERY admin route.
 */
class AuthAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $admin = $request->user('admin');

        if (! $admin) {
            return response()->json(['message' => 'Admin authentication required.'], 401);
        }

        if (! $admin->is_active) {
            return response()->json(['message' => 'This admin account is deactivated.'], 403);
        }

        return $next($request);
    }
}
