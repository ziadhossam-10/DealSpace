<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\Tenants\TenantSubscriptionServiceInterface;

class CheckFeatureLimit
{
    protected TenantSubscriptionServiceInterface $tenantService;

    public function __construct(TenantSubscriptionServiceInterface $tenantService)
    {
        $this->tenantService = $tenantService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required',
            ], 401);
        }

        $tenant = $this->tenantService->getTenantFromUser($user);

        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'User does not belong to a tenant',
            ], 403);
        }

        // Check if tenant can use this feature
        if (!$tenant->canUseFeature($feature)) {
            $limit = $tenant->getFeatureLimit($feature);
            $usage = $tenant->getFeatureUsage($feature);

            return response()->json([
                'success' => false,
                'message' => "You've reached the limit for {$feature}",
                'code' => 'FEATURE_LIMIT_REACHED',
                'data' => [
                    'feature' => $feature,
                    'limit' => $limit,
                    'used' => $usage,
                    'plan' => $tenant->currentPlan(),
                ],
            ], 403);
        }

        return $next($request);
    }
}