<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Subscriptions\SubscriptionUsageServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;

class SubscriptionController extends Controller
{
    protected SubscriptionUsageServiceInterface $usageService;

    public function __construct(SubscriptionUsageServiceInterface $usageService)
    {
        $this->usageService = $usageService;
    }

    /**
     * Get available subscription plans.
     */
    public function plans(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => config('subscriptions.plans'),
        ]);
    }

    /**
     * Get current user subscription status.
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        $subscription = $user->subscription('default');

        if (!$subscription) {
            return response()->json([
                'success' => true,
                'data' => [
                    'subscribed' => false,
                    'subscription' => null,
                    'plan' => 'free',
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'subscribed' => $subscription->valid(),
                'subscription' => [
                    'id' => $subscription->id,
                    'stripe_status' => $subscription->stripe_status,
                    'stripe_price' => $subscription->stripe_price,
                    'quantity' => $subscription->quantity,
                    'ends_at' => $subscription->ends_at?->toDateString(),
                    'on_grace_period' => $subscription->onGracePeriod(),
                    'canceled' => $subscription->canceled(),
                    'on_trial' => $subscription->onTrial(),
                    'plan' => $this->usageService->getUserPlan($user),
                ],
            ],
        ]);
    }

    /**
     * Create a Stripe Checkout session for new subscriptions.
     */
    public function createCheckoutSession(Request $request): JsonResponse
    {
        $request->validate([
            'plan' => 'required|string|in:basic,pro,enterprise',
        ]);

        $user = $request->user();
        $plan = config("subscriptions.plans.{$request->plan}");

        if (!$plan) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid plan selected.',
            ], 400);
        }

        try {
            if (!$user->hasStripeId()) {
                $user->createAsStripeCustomer([
                    'name' => $user->name,
                    'email' => $user->email,
                ]);
            }

            $existingSubscription = $user->subscription('default');
            if ($existingSubscription && $existingSubscription->valid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You already have an active subscription. Please use the billing portal to change plans.',
                ], 400);
            }

            $checkout = $user->newSubscription('default', $plan['price_id'])
                ->checkout([
                    'success_url' => config('app.frontend_url') . '/admin/subscriptions/success?session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url' => config('app.frontend_url') . '/admin/subscriptions/plans',
                ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'checkout_url' => $checkout->url,
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Failed to create checkout session', [
                'user_id' => $user->id,
                'plan' => $request->plan,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create checkout session. Please try again.',
            ], 500);
        }
    }

    /**
     * Verify checkout session after payment success.
     */
    public function verifyCheckoutSession(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|string',
        ]);

        $user = $request->user();

        try {
            $stripe = new \Stripe\StripeClient(config('cashier.secret'));
            $session = $stripe->checkout->sessions->retrieve($request->session_id);

            if ($session->payment_status !== 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not completed.',
                ], 400);
            }

            $subscription = $user->subscription('default');

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subscription not found.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'subscription' => [
                        'id' => $subscription->id,
                        'stripe_status' => $subscription->stripe_status,
                        'stripe_price' => $subscription->stripe_price,
                        'plan' => $this->usageService->getUserPlan($user),
                    ],
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Failed to verify checkout session', [
                'user_id' => $user->id,
                'session_id' => $request->session_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to verify checkout session.',
            ], 500);
        }
    }

    /**
     * Create a Stripe Billing Portal session.
     */
    public function createPortalSession(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasStripeId()) {
            return response()->json([
                'success' => false,
                'message' => 'No Stripe customer found. Please subscribe first.',
            ], 400);
        }

        try {
            $stripe = new \Stripe\StripeClient(config('cashier.secret'));
            
            $session = $stripe->billingPortal->sessions->create([
                'customer' => $user->stripe_id,
                'return_url' => config('app.frontend_url') . '/admin/subscriptions/status',
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'portal_url' => $session->url,
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Failed to create billing portal session', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create billing portal session.',
            ], 500);
        }
    }

    /**
     * Cancel subscription at period end.
     */
    public function cancel(Request $request): JsonResponse
    {
        $user = $request->user();
        $subscription = $user->subscription('default');

        if (!$subscription || !$subscription->valid()) {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription found.',
            ], 400);
        }

        if ($subscription->canceled()) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription is already scheduled for cancellation.',
            ], 400);
        }

        try {
            $subscription->cancel();

            Log::info('Subscription cancellation scheduled', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'ends_at' => $subscription->ends_at,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Subscription will be cancelled at the end of the billing period.',
                'data' => [
                    'ends_at' => $subscription->ends_at?->toDateString(),
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Failed to cancel subscription', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel subscription. Please try again.',
            ], 500);
        }
    }

    /**
     * Resume a cancelled subscription.
     */
    public function resume(Request $request): JsonResponse
    {
        $user = $request->user();
        $subscription = $user->subscription('default');

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'No subscription found.',
            ], 400);
        }

        if (!$subscription->canceled()) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription is not scheduled for cancellation.',
            ], 400);
        }

        if (!$subscription->onGracePeriod()) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription has already ended. Please create a new subscription.',
            ], 400);
        }

        try {
            $subscription->resume();

            Log::info('Subscription resumed', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Subscription has been resumed successfully.',
            ]);
        } catch (Exception $e) {
            Log::error('Failed to resume subscription', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to resume subscription. Please try again.',
            ], 500);
        }
    }

    /**
     * Immediately cancel subscription.
     */
    public function cancelNow(Request $request): JsonResponse
    {
        $user = $request->user();
        $subscription = $user->subscription('default');

        if (!$subscription || !$subscription->valid()) {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription found.',
            ], 400);
        }

        try {
            $subscription->cancelNow();

            Log::warning('Subscription cancelled immediately', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Subscription cancelled immediately.',
            ]);
        } catch (Exception $e) {
            Log::error('Failed to cancel subscription immediately', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel subscription immediately. Please try again.',
            ], 500);
        }
    }

    /**
     * Get user's invoices.
     */
    public function invoices(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasStripeId()) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        try {
            $invoices = $user->invoices()->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'date' => $invoice->date()->toDateString(),
                    'total' => $invoice->total(),
                    'currency' => $invoice->currency,
                    'status' => $invoice->status,
                    'hosted_invoice_url' => $invoice->hosted_invoice_url ?? null,
                    'invoice_pdf' => $invoice->invoice_pdf ?? null,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $invoices,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to retrieve invoices', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve invoices.',
            ], 500);
        }
    }

    /**
     * Download a specific invoice.
     */
    public function downloadInvoice(Request $request, string $invoiceId)
    {
        try {
            return $request->user()->downloadInvoice($invoiceId, [
                'vendor' => config('app.name'),
                'product' => 'Subscription',
            ]);
        } catch (Exception $e) {
            Log::error('Failed to download invoice', [
                'user_id' => $request->user()->id,
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to download invoice.',
            ], 404);
        }
    }

    /**
     * Get usage and limits.
     */
    public function usage(Request $request): JsonResponse
    {
        $user = $request->user();

        try {
            $stats = $this->usageService->getUserUsageStats($user);
            $upgrade = $this->usageService->getUpgradeRecommendation($user);

            return response()->json([
                'success' => true,
                'data' => array_merge($stats, [
                    'upgrade_recommendation' => $upgrade,
                ]),
            ]);
        } catch (Exception $e) {
            Log::error('Failed to retrieve usage stats', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve usage statistics.',
            ], 500);
        }
    }
}
