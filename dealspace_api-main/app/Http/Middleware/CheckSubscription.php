<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $plan = null): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required',
            ], 401);
        }

        // Check if user has active subscription
        if (!$user->subscribed('default')) {
            return response()->json([
                'success' => false,
                'message' => 'Active subscription required to access this feature',
                'code' => 'SUBSCRIPTION_REQUIRED',
            ], 403);
        }

        // Check if specific plan is required
        if ($plan) {
            $requiredPriceId = config("subscriptions.plans.{$plan}.price_id");
            
            if (!$requiredPriceId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid plan specified',
                ], 500);
            }

            if (!$user->subscribedToPrice($requiredPriceId, 'default')) {
                return response()->json([
                    'success' => false,
                    'message' => "This feature requires the {$plan} plan or higher",
                    'code' => 'PLAN_UPGRADE_REQUIRED',
                    'required_plan' => $plan,
                ], 403);
            }
        }

        return $next($request);
    }
}