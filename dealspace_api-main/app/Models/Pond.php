<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use App\Models\User;
use App\Scopes\PondScopes;

class Pond extends Model
{
    use BelongsToTenant,PondScopes;

    protected $fillable = [
        'name',
        'user_id',
    ];

    /**
     * Get the user that owns the pond.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    /**
     * The users that belong to the pond.
     */
    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withTimestamps();
    }
}
