<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\Tenants\TenantSubscriptionService;

class CheckTenantSubscription
{
    protected TenantSubscriptionService $tenantSubscriptionService;

    public function __construct(TenantSubscriptionService $tenantSubscriptionService)
    {
        $this->tenantSubscriptionService = $tenantSubscriptionService;
    }

    /**
     * Handle an incoming request.
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

        $tenant = $this->tenantSubscriptionService->getTenantFromUser($user);

        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'User does not belong to a tenant',
            ], 403);
        }

        // Check if tenant has active subscription
        if (!$tenant->hasActiveSubscription()) {
            return response()->json([
                'success' => false,
                'message' => 'Active subscription required to access this feature',
                'code' => 'SUBSCRIPTION_REQUIRED',
            ], 403);
        }

        // Check if specific plan is required
        if ($plan) {
            $currentPlan = $tenant->currentPlan();
            $requiredPriceId = config("subscriptions.plans.{$plan}.price_id");
            $subscription = $tenant->subscription();

            if ($subscription->stripe_price !== $requiredPriceId) {
                return response()->json([
                    'success' => false,
                    'message' => "This feature requires the {$plan} plan",
                    'code' => 'PLAN_UPGRADE_REQUIRED',
                    'data' => [
                        'current_plan' => $currentPlan,
                        'required_plan' => $plan,
                    ],
                ], 403);
            }
        }

        return $next($request);
    }
}