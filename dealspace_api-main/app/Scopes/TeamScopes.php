<?php

namespace App\Scopes;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait TeamScopes
{
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return match ($user->role) {
            RoleEnum::OWNER, RoleEnum::ADMIN => $query,
            RoleEnum::AGENT, RoleEnum::LENDER, RoleEnum::ISAs => $query->whereHas('users', fn ($q) => $q->where('users.id', $user->id)),
            default => $query->whereRaw('1 = 0'),
        };
    }

    public function scopeWithAgentCounts(Builder $query): Builder
    {
        return $query->withCount(['users as agent_count' => function ($q) {
            $q->where('role', RoleEnum::AGENT);
        }]);
    }
}
