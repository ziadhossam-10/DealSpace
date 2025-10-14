<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Stage extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'name',
        'description',
        'slug',
        'tenant_id',
    ];

    public function people()
    {
        return $this->hasMany(Person::class);
    }
}
