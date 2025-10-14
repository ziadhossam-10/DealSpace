<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class CalendarAccount extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'provider', // 'google' or 'outlook'
        'email',
        'calendar_id',
        'calendar_name',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'is_active',
        'webhook_subscription_id',
        'webhook_expires_at',
        'webhook_registered_at',
        'webhook_registration_failed',
        'webhook_channel_id', // for Google Calendar
        'webhook_resource_id', // for Google Calendar
        'sync_token', // for incremental sync
        'last_sync_at',
        'settings',
        'tasks_last_sync_at',
        'tasks_sync_frequency',
        'enable_task_sync',
        'sync_errors',
        'last_successful_sync_at',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'webhook_expires_at' => 'datetime',
        'webhook_registered_at' => 'datetime',
        'last_sync_at' => 'datetime',
        'is_active' => 'boolean',
        'webhook_registration_failed' => 'boolean',
        'settings' => 'array',
        'tasks_last_sync_at' => 'datetime',
        'last_successful_sync_at' => 'datetime',
        'enable_task_sync' => 'boolean',
    ];


    /**
     * Get the calendar events for this account.
     */
    public function events(): HasMany
    {
        return $this->hasMany(CalendarEvent::class);
    }

    /**
     * Check if the access token is expired.
     */
    public function isTokenExpired(): bool
    {
        return $this->token_expires_at && $this->token_expires_at->isPast();
    }

    /**
     * Check if webhook is about to expire (within 1 hour).
     */
    public function isWebhookExpiring(): bool
    {
        return $this->webhook_expires_at && $this->webhook_expires_at->subHour()->isPast();
    }

    /**
     * Get the default calendar settings.
     */
    public function getDefaultSettings(): array
    {
        return [
            'sync_direction' => 'bidirectional', // 'in', 'out', 'bidirectional'
            'sync_all_events' => true,
            'sync_attendees' => true,
            'sync_reminders' => true,
            'auto_create_meetings' => false,
            'meeting_prefix' => '[CRM]',
            'sync_private_events' => false
        ];
    }

    /**
     * Get merged settings with defaults.
     */
    public function getMergedSettings(): array
    {
        return array_merge($this->getDefaultSettings(), $this->settings ?? []);
    }
}