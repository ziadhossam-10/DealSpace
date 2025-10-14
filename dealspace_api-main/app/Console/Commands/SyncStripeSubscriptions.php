<?php
// filepath: /workspaces/DealSpace/dealspace_api-main/app/Console/Commands/SyncStripeSubscriptions.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Laravel\Cashier\Subscription;

class SyncStripeSubscriptions extends Command
{
    protected $signature = 'subscriptions:sync {--user-id=}';
    protected $description = 'Sync subscription data from Stripe';

    public function handle()
    {
        $stripe = new \Stripe\StripeClient(config('cashier.secret'));
        
        if ($this->option('user-id')) {
            $users = User::where('id', $this->option('user-id'))->get();
        } else {
            $users = User::whereHas('subscriptions')->get();
        }

        $this->info("Syncing subscriptions for {$users->count()} users...");

        foreach ($users as $user) {
            try {
                $subscription = $user->subscription('default');
                
                if (!$subscription || !$subscription->stripe_id) {
                    $this->warn("User {$user->id} has no valid subscription");
                    continue;
                }

                // Fetch from Stripe
                $stripeSubscription = $stripe->subscriptions->retrieve($subscription->stripe_id);
                
                // Get the price ID
                $priceId = $stripeSubscription->items->data[0]->price->id ?? null;
                
                if ($priceId) {
                    $oldPriceId = $subscription->stripe_price;
                    
                    $subscription->stripe_price = $priceId;
                    $subscription->stripe_status = $stripeSubscription->status;
                    $subscription->quantity = $stripeSubscription->items->data[0]->quantity ?? 1;
                    $subscription->save();
                    
                    $this->info("✓ User {$user->id}: {$oldPriceId} → {$priceId} (Status: {$stripeSubscription->status})");
                } else {
                    $this->error("✗ User {$user->id}: Could not find price ID");
                }
                
            } catch (\Exception $e) {
                $this->error("✗ User {$user->id}: {$e->getMessage()}");
            }
        }

        $this->info('Sync complete!');
    }
}