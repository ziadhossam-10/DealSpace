<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class DealType extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'name',
        'sort',
        'tenant_id'
    ];

    protected $casts = [
        'sort' => 'integer',
    ];

    /**
     * Get the deals for this type.
     */
    public function deals()
    {
        return $this->hasMany(Deal::class, 'type_id');
    }

    /**
     * Get the stages for this type.
     */
    public function stages()
    {
        return $this->hasMany(Stage::class, 'type_id');
    }

    
}
