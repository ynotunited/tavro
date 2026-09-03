<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Subscription;

class CheckSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $org = $request->user()?->organization;

        if (!$org) {
            return response()->json(['message' => 'No organization found.'], 403);
        }

        $subscription = Subscription::where('organization_id', $org->id)->first();

        // If no subscription exists, or it's not active/trialing, block access
        if (!$subscription || !$subscription->isActive()) {
            return response()->json([
                'message' => 'Payment required. Your subscription has expired or is inactive.',
                'error_code' => 'SUBSCRIPTION_REQUIRED'
            ], 402);
        }

        return $next($request);
    }
}
