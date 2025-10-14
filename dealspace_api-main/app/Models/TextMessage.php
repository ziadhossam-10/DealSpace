<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class TextMessage extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'person_id',
        'message',
        'to_number',
        'from_number',
        'is_incoming',
        'external_label',
        'external_url',
        'user_id',
    ];

    protected $casts = [
        'is_incoming' => 'boolean',
    ];

    public function person()
    {
        return $this->belongsTo(Person::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
