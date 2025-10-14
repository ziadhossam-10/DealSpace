<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\BelongsToPrimaryModel;

class PersonCustomFieldValue extends Model
{
    use BelongsToPrimaryModel;

    protected $fillable = [
        'person_id',
        'custom_field_id',
        'value',
    ];

    public function getRelationshipToPrimaryModel(): string
    {
        return 'person';
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function customField(): BelongsTo
    {
        return $this->belongsTo(CustomField::class);
    }
}
