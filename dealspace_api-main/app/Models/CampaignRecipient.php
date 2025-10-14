<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class CampaignRecipient extends Model
{
    protected $fillable = [
        'campaign_id',
        'person_id',
        'email',
        'status',
        'sent_at',
        'delivered_at',
        'opened_at',
        'first_clicked_at',
        'last_clicked_at',
        'click_count',
        'open_count',
        'failure_reason',
        'tracking_token'
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'opened_at' => 'datetime',
        'first_clicked_at' => 'datetime',
        'last_clicked_at' => 'datetime',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(CampaignClick::class);
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($recipient) {
            if (empty($recipient->tracking_token)) {
                $recipient->tracking_token = bin2hex(random_bytes(16));
            }
        });
    }
}
