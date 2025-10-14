<?php

namespace App\Scopes;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait GroupScopes
{
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return match ($user->role) {
            RoleEnum::OWNER, RoleEnum::ADMIN => $query,
        };
    }
}
