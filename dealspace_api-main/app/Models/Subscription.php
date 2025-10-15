<?php

namespace App\Models;

use Laravel\Cashier\Subscription as CashierSubscription;

class Subscription extends CashierSubscription
{
    protected $fillable = [
        'tenant_id',
        'type',
        'stripe_id',
        'stripe_status',
        'stripe_price',
        'quantity',
        'trial_ends_at',
        'ends_at',
        'cancel_at_period_end',
        'current_period_start',
        'current_period_end',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'ends_at' => 'datetime',
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'cancel_at_period_end' => 'boolean',
    ];

    /**
     * Get the tenant that owns the subscription.
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }


    /**
     * Get the plan name for this subscription.
     */
    public function planName(): ?string
    {
        $plans = config('plans');
        
        foreach ($plans as $key => $plan) {
            if ($plan['stripe_price_id'] === $this->stripe_price) {
                return $key;
            }
        }
        
        return null;
    }

    /**
     * Check if subscription is in grace period (canceled but still active).
     */
    public function inGracePeriod(): bool
    {
        return $this->cancel_at_period_end && $this->onGracePeriod();
    }
}