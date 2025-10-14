<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

class WebhookController extends CashierController
{
    /**
     * Override to add debug logging for all webhooks
     */
    public function handleWebhook(Request $request): Response
    {
        $payload = json_decode($request->getContent(), true);
        $eventType = $payload['type'] ?? 'unknown';
        
        // Log the full webhook payload for type generation
        Log::channel('daily')->info('WEBHOOK_RECEIVED', [
            'event_type' => $eventType,
            'stripe_event_id' => $payload['id'] ?? null,
            'timestamp' => now()->toIso8601String(),
            'payload' => $payload,
        ]);
        
        try {
            $response = parent::handleWebhook($request);
            
            Log::channel('daily')->info('WEBHOOK_HANDLED', [
                'event_type' => $eventType,
                'status_code' => $response->getStatusCode(),
            ]);
            
            return $response;
        } catch (\Exception $e) {
            Log::channel('daily')->error('WEBHOOK_ERROR', [
                'event_type' => $eventType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Handle customer deleted.
     */
    public function handleCustomerDeleted(array $payload)
    {
        $stripeCustomer = $payload['data']['object'];
        $user = User::where('stripe_id', $stripeCustomer['id'])->first();
        
        if ($user) {
            Log::info('Customer deleted in Stripe, resetting user data', [
                'user_id' => $user->id,
                'stripe_id' => $stripeCustomer['id'],
            ]);
            
            $this->resetUserStripeData($user);
        }
        
        return response('Webhook Handled', 200);
    }

    /**
     * Handle customer subscription deleted.
     */
    public function handleCustomerSubscriptionDeleted(array $payload)
    {
        $response = parent::handleCustomerSubscriptionDeleted($payload);
        
        $stripeSubscription = $payload['data']['object'];
        $subscription = \Laravel\Cashier\Subscription::where('stripe_id', $stripeSubscription['id'])->first();
        
        if ($subscription) {
            $subscription->stripe_status = 'canceled';
            $subscription->ends_at = isset($stripeSubscription['current_period_end'])
                ? \Carbon\Carbon::createFromTimestamp($stripeSubscription['current_period_end'])
                : now();
            $subscription->save();

            Log::info('Subscription canceled via webhook', [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'ends_at' => $subscription->ends_at,
            ]);
        }
        
        return $response;
    }

    /**
     * Handle customer subscription updated.
     */
    public function handleCustomerSubscriptionUpdated(array $payload)
    {
        $response = parent::handleCustomerSubscriptionUpdated($payload);
        
        $stripeSubscription = $payload['data']['object'];
        $subscription = \Laravel\Cashier\Subscription::where('stripe_id', $stripeSubscription['id'])->first();
        
        if (!$subscription) {
            Log::warning('Subscription not found for update', [
                'stripe_id' => $stripeSubscription['id'],
            ]);
            return $response;
        }
        
        $subscription->stripe_status = $stripeSubscription['status'];
        $subscription->stripe_price = $stripeSubscription['items']['data'][0]['price']['id'] ?? $subscription->stripe_price;
        $subscription->quantity = $stripeSubscription['items']['data'][0]['quantity'] ?? 1;
        
        $cancelAtPeriodEnd = $stripeSubscription['cancel_at_period_end'] ?? false;
        $status = $stripeSubscription['status'];
        
        if ($status === 'canceled') {
            $subscription->ends_at = isset($stripeSubscription['current_period_end'])
                ? \Carbon\Carbon::createFromTimestamp($stripeSubscription['current_period_end'])
                : now();
        } elseif ($cancelAtPeriodEnd) {
            $endTimestamp = $stripeSubscription['cancel_at']
                ?? $stripeSubscription['current_period_end']
                ?? null;
            $subscription->ends_at = $endTimestamp
                ? \Carbon\Carbon::createFromTimestamp($endTimestamp)
                : now();
        } else {
            $subscription->ends_at = null;
        }
        
        $subscription->save();
        
        Log::info('Subscription updated successfully', [
            'subscription_id' => $subscription->id,
            'stripe_status' => $subscription->stripe_status,
            'cancel_at_period_end' => $cancelAtPeriodEnd,
            'ends_at' => $subscription->ends_at?->toDateString(),
        ]);
        
        return $response;
    }

    /**
     * Handle checkout session completed.
     */
    public function handleCheckoutSessionCompleted(array $payload)
    {
        $session = $payload['data']['object'];
        
        if ($session['mode'] === 'subscription' && isset($session['subscription'])) {
            try {
                sleep(1);
                
                $stripe = new \Stripe\StripeClient(config('cashier.secret'));
                $stripeSubscription = $stripe->subscriptions->retrieve($session['subscription']);
                
                $subscription = \Laravel\Cashier\Subscription::where('stripe_id', $stripeSubscription->id)->first();
                
                if ($subscription) {
                    $priceId = $stripeSubscription->items->data[0]->price->id ?? null;
                    
                    if ($priceId) {
                        $subscription->stripe_price = $priceId;
                        $subscription->stripe_status = $stripeSubscription->status;
                        $subscription->quantity = $stripeSubscription->items->data[0]->quantity ?? 1;
                        $subscription->ends_at = null;
                        $subscription->save();
                        
                        Log::info('Subscription synced after checkout', [
                            'subscription_id' => $subscription->id,
                            'user_id' => $subscription->user_id,
                            'stripe_price' => $subscription->stripe_price,
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::error('Failed to sync subscription after checkout', [
                    'error' => $e->getMessage(),
                    'session_id' => $session['id'] ?? null,
                ]);
            }
        }
        
        return response('Webhook Handled', 200);
    }

    /**
     * Handle invoice payment succeeded.
     */
    public function handleInvoicePaymentSucceeded(array $payload)
    {
        return parent::handleInvoicePaymentSucceeded($payload);
    }

    /**
     * Handle invoice payment failed.
     */
    public function handleInvoicePaymentFailed(array $payload)
    {
        $invoice = $payload['data']['object'];
        
        Log::warning('Invoice payment failed', [
            'invoice_id' => $invoice['id'] ?? null,
            'customer' => $invoice['customer'] ?? null,
            'amount' => $invoice['amount_due'] ?? null,
        ]);
        
        return parent::handleInvoicePaymentFailed($payload);
    }

    /**
     * Reset all Stripe-related data for a user
     */
    protected function resetUserStripeData($user): void
    {
        try {
            DB::beginTransaction();
            
            $subscriptions = $user->subscriptions()->get();
            
            foreach ($subscriptions as $subscription) {
                if ($subscription->stripe_status !== 'canceled') {
                    $subscription->stripe_status = 'canceled';
                    $subscription->ends_at = now();
                    $subscription->save();
                }
            }
            
            $oldStripeId = $user->stripe_id;
            $user->stripe_id = null;
            $user->pm_type = null;
            $user->pm_last_four = null;
            $user->trial_ends_at = null;
            $user->save();
            
            Log::info('User Stripe data reset', [
                'user_id' => $user->id,
                'old_stripe_id' => $oldStripeId,
            ]);
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to reset user Stripe data', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}