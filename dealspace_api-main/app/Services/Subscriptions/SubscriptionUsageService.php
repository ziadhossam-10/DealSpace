<?php

namespace App\Services\Subscriptions;

use App\Models\User;
use App\Models\Deal;
use App\Models\Person;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SubscriptionUsageService implements SubscriptionUsageServiceInterface
{
    /**
     * Feature limits per plan
     */
    protected array $limits = [
        'basic' => [
            'deals' => 5,
            'contacts' => 100,
            'campaigns' => 3,
            'team_members' => 1,
        ],
        'pro' => [
            'deals' => null, // unlimited
            'contacts' => null,
            'campaigns' => null,
            'team_members' => 5,
        ],
        'enterprise' => [
            'deals' => null,
            'contacts' => null,
            'campaigns' => null,
            'team_members' => null,
        ],
    ];

    /**
     * Get current billing period dates
     */
    protected function getBillingPeriod(User $user): array
    {
        $subscription = $user->subscription('default');
        
        if (!$subscription) {
            throw new \Exception('No active subscription found');
        }

        try {
            $stripeSubscription = $subscription->asStripeSubscription();
            
            // Check if billing period is available
            if ($stripeSubscription->current_period_start && $stripeSubscription->current_period_end) {
                return [
                    'start' => Carbon::createFromTimestamp($stripeSubscription->current_period_start),
                    'end' => Carbon::createFromTimestamp($stripeSubscription->current_period_end),
                ];
            }
        } catch (\Exception $e) {
            Log::warning('Unable to get Stripe billing period', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Fallback: Use subscription created_at date and calculate monthly period
        $subscriptionStart = $subscription->created_at;
        $now = Carbon::now();
        
        // Calculate the start of the current billing period
        $monthsSinceStart = $subscriptionStart->diffInMonths($now);
        $periodStart = $subscriptionStart->copy()->addMonths($monthsSinceStart);
        $periodEnd = $periodStart->copy()->addMonth();

        Log::info('Using fallback billing period', [
            'user_id' => $user->id,
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
        ]);

        return [
            'start' => $periodStart,
            'end' => $periodEnd,
        ];
    }

    /**
     * Get user's current plan
     */
    public function getUserPlan(User $user): string
    {
        $subscription = $user->subscription('default');
        
        if (!$subscription) {
            Log::warning('No subscription found for user', ['user_id' => $user->id]);
            return 'basic';
        }

        $stripePriceId = $subscription->stripe_price;
        
        if (!$stripePriceId) {
            Log::warning('No stripe_price found in subscription', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
            ]);
            return 'basic';
        }

        $plans = config('subscriptions.plans');
        
        foreach ($plans as $key => $plan) {
            if (isset($plan['price_id']) && $stripePriceId === $plan['price_id']) {
                Log::info('Plan matched', [
                    'user_id' => $user->id,
                    'plan' => $key,
                    'price_id' => $stripePriceId,
                ]);
                return $key;
            }
        }

        Log::warning('No plan matched for price ID', [
            'user_id' => $user->id,
            'stripe_price_id' => $stripePriceId,
            'available_plans' => array_keys($plans),
        ]);

        return 'basic';
    }

    /**
     * Get actual usage count for a feature
     */
    protected function getActualUsage(User $user, string $feature): int
    {
        try {            
            switch ($feature) {
                case 'deals':
                    return Deal::count();
                case 'contacts':
                    return Person::count();
                default:
                    return 0;
            }
        } catch (\Exception $e) {
            Log::error('Error getting actual usage', [
                'user_id' => $user->id,
                'feature' => $feature,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 0;
        }
    }

    /**
     * Get usage data for a feature
     */
    public function getUsage(User $user, string $feature): array
    {
        $plan = $this->getUserPlan($user);
        $limit = $this->limits[$plan][$feature] ?? null;
        $used = $this->getActualUsage($user, $feature);
        $remaining = $limit !== null ? max(0, $limit - $used) : null;
        $limitReached = $limit !== null && $used >= $limit;

        return [
            'used' => $used,
            'limit' => $limit,
            'remaining' => $remaining,
            'unlimited' => $limit === null,
            'limit_reached' => $limitReached,
        ];
    }

    /**
     * Check if user can use a feature
     */
    public function canUse(User $user, string $feature): bool
    {
        // If user has no subscription, allow usage (for testing/development)
        // You can change this behavior based on your requirements
        if (!$user->subscribed('default')) {
            Log::warning('User accessing feature without subscription', [
                'user_id' => $user->id,
                'feature' => $feature,
            ]);
            return false;
        }

        try {
            $usage = $this->getUsage($user, $feature);
            return !$usage['limit_reached'];
        } catch (\Exception $e) {
            Log::error('Error checking feature usage', [
                'user_id' => $user->id,
                'feature' => $feature,
                'error' => $e->getMessage(),
            ]);
            // Default to allowing access if there's an error (graceful degradation)
            return true;
        }
    }

    /**
     * Get all usage statistics for user
     */
    public function getUserUsageStats(User $user): array
    {
        $plan = $this->getUserPlan($user);
        $stats = [];

        foreach ($this->limits[$plan] as $feature => $limit) {
            try {
                $stats[$feature] = $this->getUsage($user, $feature);
            } catch (\Exception $e) {
                Log::error('Error getting usage stats', [
                    'user_id' => $user->id,
                    'feature' => $feature,
                    'error' => $e->getMessage(),
                ]);
                $stats[$feature] = [
                    'used' => 0,
                    'limit' => $limit,
                    'remaining' => $limit,
                    'unlimited' => $limit === null,
                    'limit_reached' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        try {
            $period = $this->getBillingPeriod($user);
            $daysRemaining = Carbon::now()->diffInDays($period['end'], false);
            
            $periodInfo = [
                'start' => $period['start']->toDateString(),
                'end' => $period['end']->toDateString(),
                'days_remaining' => max(0, $daysRemaining),
            ];
        } catch (\Exception $e) {
            Log::error('Error getting billing period', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            
            $periodInfo = [
                'start' => Carbon::now()->startOfMonth()->toDateString(),
                'end' => Carbon::now()->endOfMonth()->toDateString(),
                'days_remaining' => Carbon::now()->diffInDays(Carbon::now()->endOfMonth()),
                'error' => 'Unable to retrieve billing period - using current month as fallback',
            ];
        }

        return [
            'plan' => $plan,
            'usage' => $stats,
            'billing_period' => $periodInfo,
        ];
    }

    /**
     * Check if feature usage would exceed limit
     */
    public function wouldExceedLimit(User $user, string $feature, int $additional = 1): bool
    {
        if (!$user->subscribed('default')) {
            return true;
        }

        try {
            $usage = $this->getUsage($user, $feature);
            
            if ($usage['unlimited']) {
                return false;
            }

            return ($usage['used'] + $additional) > $usage['limit'];
        } catch (\Exception $e) {
            Log::error('Error checking if would exceed limit', [
                'user_id' => $user->id,
                'feature' => $feature,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get upgrade recommendation based on usage
     */
    public function getUpgradeRecommendation(User $user): ?array
    {
        try {
            $plan = $this->getUserPlan($user);
            
            // If already on enterprise, no upgrade needed
            if ($plan === 'enterprise') {
                return null;
            }

            $stats = $this->getUserUsageStats($user);
            $nearLimit = [];

            foreach ($stats['usage'] as $feature => $usage) {
                if (isset($usage['limit']) && $usage['limit'] !== null && !isset($usage['error'])) {
                    $percentUsed = $usage['limit'] > 0 ? ($usage['used'] / $usage['limit']) * 100 : 0;
                    
                    if ($percentUsed >= 80) {
                        $nearLimit[] = [
                            'feature' => $feature,
                            'percent_used' => round($percentUsed, 1),
                            'used' => $usage['used'],
                            'limit' => $usage['limit'],
                        ];
                    }
                }
            }

            if (empty($nearLimit)) {
                return null;
            }

            $recommendedPlan = $plan === 'basic' ? 'pro' : 'enterprise';
            $recommendedPlanDetails = config("subscriptions.plans.{$recommendedPlan}");

            return [
                'current_plan' => $plan,
                'recommended_plan' => $recommendedPlan,
                'recommended_plan_price' => $recommendedPlanDetails['price'] ?? null,
                'reason' => 'You are approaching usage limits',
                'features_near_limit' => $nearLimit,
            ];
        } catch (\Exception $e) {
            Log::error('Error getting upgrade recommendation', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}