<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class DealStage extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'name',
        'sort',
        'color',
        'type_id',
        'tenant_id'
    ];

    protected $casts = [
        'sort' => 'integer',
        'type_id' => 'integer',
    ];

    /**
     * Get the deal type that owns this stage.
     */
    public function type()
    {
        return $this->belongsTo(DealType::class, 'type_id');
    }

    /**
     * Get the deals for this stage.
     */
    public function deals()
    {
        return $this->hasMany(Deal::class, 'stage_id');
    }
}
