<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class EmailAccount extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'provider',
        'email',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'is_active',
        'webhook_subscription_id',
        'webhook_expires_at',
        'webhook_registered_at',
        'webhook_history_id',
        'tenant_id'
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'is_active' => 'boolean'
    ];

    public function isTokenExpired(): bool
    {
        return $this->token_expires_at->isPast();
    }

    public function emails()
    {
        return $this->hasMany(Email::class);
    }

    public function incomingEmails()
    {
        return $this->hasMany(Email::class)->where('is_incoming', true);
    }

    public function outgoingEmails()
    {
        return $this->hasMany(Email::class)->where('is_incoming', false);
    }
}
