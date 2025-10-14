<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Notification extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'title',
        'message',
        'action',
        'image',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    protected $appends = [
        'read_by_current_user',
    ];

    /**
     * Get the user that owns the notification.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Many-to-many relationship with users
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'notification_user')
            ->withPivot('read_at')
            ->withTimestamps();
    }

    /**
     * Users who have read this notification
     */
    public function readByUsers()
    {
        return $this->belongsToMany(User::class, 'notification_user')
            ->wherePivotNotNull('read_at')
            ->withPivot('read_at')
            ->withTimestamps();
    }

    /**
     * Users who haven't read this notification
     */
    public function unreadByUsers()
    {
        return $this->belongsToMany(User::class, 'notification_user')
            ->wherePivotNull('read_at')
            ->withTimestamps();
    }

    /**
     * Append read status for the current user
     */
    public function getReadByCurrentUserAttribute()
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        return $this->users()
            ->where('user_id', $user->id)
            ->whereNotNull('notification_user.read_at')
            ->exists();
    }
}
