<?php

namespace App\Http\Controllers\Traits;

use App\Services\Subscriptions\SubscriptionUsageServiceInterface;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

trait SubscriptionLimitable
{
    protected SubscriptionUsageServiceInterface $subscriptionUsage;

    /**
     * Inject the SubscriptionUsageServiceInterface automatically.
     */
    public function setSubscriptionUsageService(SubscriptionUsageServiceInterface $subscriptionUsage): void
    {
        $this->subscriptionUsage = $subscriptionUsage;
    }

    /**
     * Check if the current user can use a given feature.
     * Returns true if allowed, or aborts with a 403 if not.
     */
    protected function ensureFeatureAvailable(string $feature): bool
    {
        $user = Auth::user();

        if (!$this->subscriptionUsage->canUse($user, $feature)) {
            abort(Response::HTTP_FORBIDDEN, "You’ve reached your limit for {$feature} on your current plan.");
        }

        return true;
    }

    /**
     * Optionally check whether a user is near their limit and return a notice.
     */
    protected function checkNearLimitNotice(string $feature): ?array
    {
        $user = Auth::user();
        $usage = $this->subscriptionUsage->getUsage($user, $feature);

        if (!$usage['unlimited'] && $usage['remaining'] <= 2) {
            return [
                'message' => "You're nearing your {$feature} limit. Consider upgrading your plan.",
                'usage' => $usage,
            ];
        }

        return null;
    }
}
