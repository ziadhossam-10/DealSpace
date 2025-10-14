<?php

namespace App\Scopes;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait PondScopes
{
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        $role = $user->role instanceof RoleEnum ? $user->role : RoleEnum::from($user->role);

        return match ($role) {
            RoleEnum::OWNER, RoleEnum::ADMIN => $query,
            RoleEnum::AGENT, RoleEnum::ISAs, RoleEnum::LENDER => $query->where(function (Builder $q) use ($user) {
                $q->whereHas('users', fn (Builder $qq) => $qq->where('user_id', $user->id));
            }),
        };
    }
}
