<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class AppointmentType extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'name',
        'description',
        'sort',
        'tenant_id'
    ];

    protected $casts = [
        'sort' => 'integer',
    ];

    /**
     * Get the appointments for this type.
     */
    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'type_id');
    }
}
