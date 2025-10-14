<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use App\Enums\RoleEnum;
use App\Policies\PersonPolicy;

class Deal extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'name',
        'stage_id',
        'type_id',
        'description',
        'price',
        'projected_close_date',
        'order_weight',
        'commission_value',
        'agent_commission',
        'team_commission',
    ];

    protected $casts = [
        'projected_close_date' => 'date',
        'price' => 'integer',
        'order_weight' => 'integer',
        'commission_value' => 'integer',
        'agent_commission' => 'integer',
        'team_commission' => 'integer',
    ];

    /**
     * Get the stage that this deal belongs to.
     */
    public function stage()
    {
        return $this->belongsTo(DealStage::class);
    }

    /**
     * Get the type that this deal belongs to.
     */
    public function type()
    {
        return $this->belongsTo(DealType::class);
    }

    /**
     * The people that belong to the deal.
     */
    public function people()
    {
        return $this->belongsToMany(Person::class)
            ->withTimestamps();
    }

    /**
     * The users that belong to the deal.
     */
    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withTimestamps();
    }

    /**
     * The attachments that belong to the deal.
     */
    public function attachments()
    {
        return $this->hasMany(DealAttachment::class);
    }

    /**
     * Scope a query to only include deals visible to the user.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        $role = $user->role instanceof RoleEnum ? $user->role : RoleEnum::from($user->role);
        return match ($role) {
            RoleEnum::OWNER, RoleEnum::ADMIN => $query,
            RoleEnum::AGENT => $query->where(function (Builder $q) use ($user) {
                $q->whereHas('users', fn ($qq) => $qq->where('users.id', $user->id))
                  ->orWhereHas('people', fn ($qq) => $qq->where('assigned_user_id', $user->id));
            })
        };
    }
}   
