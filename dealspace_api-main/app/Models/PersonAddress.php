<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\BelongsToPrimaryModel;

class PersonAddress extends Model
{
    use BelongsToPrimaryModel;

    protected $fillable = [
        'person_id',
        'street_address',
        'city',
        'state',
        'postal_code',
        'country',
        'type',
        'is_primary'
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function getRelationshipToPrimaryModel(): string
    {
        return 'person';
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}