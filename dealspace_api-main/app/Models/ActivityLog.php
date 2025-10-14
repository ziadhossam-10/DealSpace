<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class ActivityLog extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'person_id',
        'user_id',
        'activity_type', // 'Call', 'Email', 'TextMessage', 'Note'
        'activity_id',   // ID of the related record
        'title',         // Brief description
        'description',   // Detailed description
        'metadata',      // JSON field for type-specific data
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    // Polymorphic relationship to the actual activity
    public function activity()
    {
        return $this->morphTo(__FUNCTION__, 'App\Models\\' . 'activity_type', 'activity_id');
    }

    public function person()
    {
        return $this->belongsTo(Person::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeByPerson($query, $personId)
    {
        return $query->where('person_id', $personId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('activity_type', $type);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
