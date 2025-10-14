<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignLink extends Model
{
    protected $fillable = [
        'campaign_id',
        'original_url',
        'tracking_token',
        'click_count'
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(CampaignClick::class);
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($link) {
            if (empty($link->tracking_token)) {
                $link->tracking_token = bin2hex(random_bytes(16));
            }
        });
    }
}
