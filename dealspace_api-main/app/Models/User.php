<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Enums\ProviderEnum;
use App\Enums\IndustryEnum;
use App\Enums\RoleEnum;
use App\Enums\UsageEnum;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use App\Scopes\UserScopes;
use Laravel\Cashier\Billable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, Billable;
    use BelongsToTenant;
    use UserScopes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'provider',
        'provider_id',
        'role',
        'tenant_id',
        'assigned_user_id',
        'twilio_phone_number',
        'twilio_phone_sid'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
        'provider' => ProviderEnum::class,
        'industry' => IndustryEnum::class,
        'usage' => UsageEnum::class,
        'role' => RoleEnum::class,
    ];

    /**
     * The groups that belong to the user.
     */
    public function groups()
    {
        return $this->belongsToMany(Group::class)
            ->withPivot('sort_order')
            ->withTimestamps();
    }
    /**
     * The teams that belong to the user.
     */
    public function teams()
    {
        return $this->belongsToMany(Team::class, 'team_users', 'user_id', 'team_id')
            ->withTimestamps();
    }

    /**
     * The ponds that belong to the user.
     */
    public function ponds()
    {
        return $this->hasMany(Pond::class);
    }
    /**
     * Many-to-many relationship with Ponds
     */

    public function ponds_users()
    {
        return $this->belongsToMany(
            Pond::class,   
            'pond_user',
            'user_id',
            'pond_id'
        )->withTimestamps();
    }


    public function deals()
    {
        return $this->belongsToMany(Deal::class);
    }


    /**
     * Many-to-many relationship with notifications
     */
    public function notifications()
    {
        return $this->belongsToMany(Notification::class, 'notification_user')
            ->withPivot('read_at')
            ->withTimestamps()
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get unread notifications for this user
     */
    public function unreadNotifications()
    {
        return $this->belongsToMany(Notification::class, 'notification_user')
            ->wherePivotNull('read_at')
            ->withTimestamps()
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get read notifications for this user
     */
    public function readNotifications()
    {
        return $this->belongsToMany(Notification::class, 'notification_user')
            ->wherePivotNotNull('read_at')
            ->withPivot('read_at')
            ->withTimestamps()
            ->orderBy('pivot_read_at', 'desc');
    }

    /**
     * Mark a notification as read
     */
    public function markNotificationAsRead($notificationId)
    {
        return $this->notifications()
            ->updateExistingPivot($notificationId, [
                'read_at' => now()
            ]);
    }

    /**
     * Get count of unread notifications
     */
    public function getUnreadNotificationsCountAttribute()
    {
        return $this->unreadNotifications()->count();
    }

    public function noteMentions()
    {
        return $this->hasMany(NoteMention::class, 'mentioned_user_id');
    }

    public function mentionedInNotes()
    {
        return $this->belongsToMany(Note::class, 'note_mentions', 'mentioned_user_id', 'note_id');
    }

}
