<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\BelongsToPrimaryModel;

class PersonPhone extends Model
{
    use BelongsToPrimaryModel;

    protected $fillable = [
        'person_id',
        'value',
        'type',
        'is_primary',
        'status'
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