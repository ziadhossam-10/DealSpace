<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionUsage extends Model
{
    protected $table = 'subscription_usage';

    protected $fillable = [
        'user_id',
        'feature',
        'used',
        'limit',
        'period_start',
        'period_end',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'used' => 'integer',
        'limit' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if usage limit is reached
     */
    public function isLimitReached(): bool
    {
        if ($this->limit === null) {
            return false; // Unlimited
        }

        return $this->used >= $this->limit;
    }

    /**
     * Get remaining usage
     */
    public function remaining(): ?int
    {
        if ($this->limit === null) {
            return null; // Unlimited
        }

        return max(0, $this->limit - $this->used);
    }
}