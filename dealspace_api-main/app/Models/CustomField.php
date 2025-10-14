<?php

namespace App\Models;

use App\Enums\CustomFieldTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class CustomField extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'name',
        'label',
        'type',
        'options',
    ];

    protected $casts = [
        'options' => 'array',
        'type' => CustomFieldTypeEnum::class,
    ];

    public function personCustomFieldValues(): HasMany
    {
        return $this->hasMany(PersonCustomFieldValue::class);
    }

    public function people(): BelongsToMany
    {
        return $this->belongsToMany(Person::class, 'person_custom_field_values')
            ->withPivot('value')
            ->withTimestamps();
    }
}
