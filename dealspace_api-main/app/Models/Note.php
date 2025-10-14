<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Note extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'subject',
        'body',
        'person_id',
        'created_by',
        'updated_by',
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function mentions()
    {
        return $this->hasMany(NoteMention::class);
    }

    public function mentionedUsers()
    {
        return $this->belongsToMany(User::class, 'note_mentions', 'note_id', 'mentioned_user_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    public function person()
    {
        return $this->belongsTo(Person::class);
    }
}
