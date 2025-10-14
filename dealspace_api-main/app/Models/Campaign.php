<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Campaign extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'name',
        'description',
        'subject',
        'body',
        'body_html',
        'status',
        'scheduled_at',
        'sent_at',
        'user_id',
        'email_account_id',
        'tenant_id',
        'total_recipients',
        'emails_sent',
        'emails_delivered',
        'emails_opened',
        'emails_clicked',
        'emails_bounced',
        'emails_unsubscribed'
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function recipients(): HasMany
    {
        return $this->hasMany(CampaignRecipient::class);
    }

    public function links(): HasMany
    {
        return $this->hasMany(CampaignLink::class);
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(CampaignClick::class);
    }

    public function emailAccount(): BelongsTo
    {
        return $this->belongsTo(EmailAccount::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Calculate metrics
    public function getOpenRateAttribute(): float
    {
        return $this->emails_delivered > 0 ? ($this->emails_opened / $this->emails_delivered) * 100 : 0;
    }

    public function getClickRateAttribute(): float
    {
        return $this->emails_delivered > 0 ? ($this->emails_clicked / $this->emails_delivered) * 100 : 0;
    }

    public function getBounceRateAttribute(): float
    {
        return $this->total_recipients > 0 ? ($this->emails_bounced / $this->total_recipients) * 100 : 0;
    }
}
