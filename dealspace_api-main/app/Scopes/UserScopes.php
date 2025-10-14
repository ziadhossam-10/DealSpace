<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use App\Models\User;

trait UserScopes
{
    /**
     * Scope to get only users in the same teams as the given assigned user.
     */
    public function scopeAssignedUserTeamMembers(Builder $query, User $user): Builder
    {
        return $query->where(function ($q) use ($user) {
            $q->whereHas('teams', function ($teamQuery) use ($user) {
                $teamQuery->whereIn('teams.id', $user->teams()->pluck('teams.id'));
            })
            ->orWhere('role', '0')
            ->orWhere('role', '1');
        });
    }
}
