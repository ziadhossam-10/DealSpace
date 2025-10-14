<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SubscriptionUsageService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SubscriptionManagementController extends Controller
{
    protected SubscriptionUsageService $usageService;

    public function __construct(SubscriptionUsageService $usageService)
    {
        $this->usageService = $usageService;
    }

    /**
     * Get all subscriptions
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $search = $request->input('search');
        $status = $request->input('status'); // active, canceled, past_due

        $query = User::query()
            ->with(['subscriptions' => function ($q) {
                $q->where('name', 'default');
            }])
            ->whereHas('subscriptions', function ($q) {
                $q->where('name', 'default');
            });

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status) {
            $query->whereHas('subscriptions', function ($q) use ($status) {
                $q->where('stripe_status', $status);
            });
        }

        $users = $query->paginate($perPage);

        $data = $users->map(function ($user) {
            $subscription = $user->subscription('default');
            
            return [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_email' => $user->email,
                'subscription' => $subscription ? [
                    'id' => $subscription->id,
                    'stripe_id' => $subscription->stripe_id,
                    'stripe_status' => $subscription->stripe_status,
                    'stripe_price' => $subscription->stripe_price,
                    'quantity' => $subscription->quantity,
                    'trial_ends_at' => $subscription->trial_ends_at,
                    'ends_at' => $subscription->ends_at,
                    'created_at' => $subscription->created_at,
                ] : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'total' => $users->total(),
                'per_page' => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
            ],
        ]);
    }

    /**
     * Get specific user's subscription details
     */
    public function show(User $user): JsonResponse
    {
        $subscription = $user->subscription('default');
        
        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'User has no subscription',
            ], 404);
        }

        $usage = $this->usageService->getUserUsageStats($user);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'subscription' => [
                    'id' => $subscription->id,
                    'stripe_id' => $subscription->stripe_id,
                    'stripe_status' => $subscription->stripe_status,
                    'stripe_price' => $subscription->stripe_price,
                    'quantity' => $subscription->quantity,
                    'trial_ends_at' => $subscription->trial_ends_at,
                    'ends_at' => $subscription->ends_at,
                    'created_at' => $subscription->created_at,
                ],
                'usage' => $usage,
            ],
        ]);
    }

    /**
     * Cancel user's subscription (admin)
     */
    public function cancel(User $user): JsonResponse
    {
        try {
            if (!$user->subscribed('default')) {
                return response()->json([
                    'success' => false,
                    'message' => 'User has no active subscription',
                ], 400);
            }

            $user->subscription('default')->cancel();
            
            return response()->json([
                'success' => true,
                'message' => 'Subscription cancelled successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel subscription: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel subscription immediately (admin)
     */
    public function cancelNow(User $user): JsonResponse
    {
        try {
            if (!$user->subscribed('default')) {
                return response()->json([
                    'success' => false,
                    'message' => 'User has no active subscription',
                ], 400);
            }

            $user->subscription('default')->cancelNow();
            
            return response()->json([
                'success' => true,
                'message' => 'Subscription cancelled immediately',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel subscription: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Resume cancelled subscription (admin)
     */
    public function resume(User $user): JsonResponse
    {
        try {
            if (!$user->subscription('default')->onGracePeriod()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subscription is not scheduled for cancellation',
                ], 400);
            }

            $user->subscription('default')->resume();
            
            return response()->json([
                'success' => true,
                'message' => 'Subscription resumed successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to resume subscription: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Change user's plan (admin)
     */
    public function changePlan(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'plan' => 'required|string|in:basic,pro,enterprise',
        ]);

        $plan = config("subscriptions.plans.{$request->plan}");

        try {
            if (!$user->subscribed('default')) {
                return response()->json([
                    'success' => false,
                    'message' => 'User has no active subscription',
                ], 400);
            }

            $user->subscription('default')->swap($plan['price_id']);
            
            return response()->json([
                'success' => true,
                'message' => 'Plan changed successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to change plan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get subscription statistics
     */
    public function statistics(): JsonResponse
    {
        $totalSubscriptions = User::whereHas('subscriptions', function ($q) {
            $q->where('name', 'default')->where('stripe_status', 'active');
        })->count();

        $byPlan = [];
        foreach (config('subscriptions.plans') as $key => $plan) {
            $byPlan[$key] = User::whereHas('subscriptions', function ($q) use ($plan) {
                $q->where('name', 'default')
                  ->where('stripe_status', 'active')
                  ->where('stripe_price', $plan['price_id']);
            })->count();
        }

        $cancelledThisMonth = User::whereHas('subscriptions', function ($q) {
            $q->where('name', 'default')
              ->whereNotNull('ends_at')
              ->whereMonth('ends_at', now()->month);
        })->count();

        $newThisMonth = User::whereHas('subscriptions', function ($q) {
            $q->where('name', 'default')
              ->whereMonth('created_at', now()->month);
        })->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_active_subscriptions' => $totalSubscriptions,
                'subscriptions_by_plan' => $byPlan,
                'new_this_month' => $newThisMonth,
                'cancelled_this_month' => $cancelledThisMonth,
            ],
        ]);
    }
}