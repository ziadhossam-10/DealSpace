<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\BelongsToPrimaryModel;

class PersonTag extends Model
{
    use BelongsToPrimaryModel;

    protected $fillable = [
        'person_id',
        'name',
        'color',
        'description'
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