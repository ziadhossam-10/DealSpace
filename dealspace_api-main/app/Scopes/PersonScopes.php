<?php

namespace App\Scopes;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use App\Enums\RoleEnum;

trait PersonScopes
{
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        $role = $user->role;

        return match ($role) {
            RoleEnum::OWNER, RoleEnum::ADMIN => $query,
            RoleEnum::AGENT => $this->scopeVisibleToAgent($query, $user),
            RoleEnum::LENDER => $this->scopeVisibleToLender($query, $user),
            RoleEnum::ISAs => $this->scopeVisibleToIsa($query, $user),
        };
    }

    public function scopeVisibleToAgent(Builder $query, User $user): Builder
    {
        return $query->where(function ($q) use ($user) {
            $q->whereHas('assignedUser', fn ($qq) => $qq->where('id', $user->id))
              ->orWhereHas('collaborators', fn ($qq) => $qq->where('user_id', $user->id))
              ->orWhereHas('deals', fn ($qq) => $qq->whereHas('users', fn ($qq) => $qq->where('users.id', $user->id)))
              ->orWhereHas('assignedPond', fn ($qq) => $qq->whereHas('users', fn ($qq) => $qq->where('users.id', $user->id)));
        });
    }

    public function scopeVisibleToLender(Builder $query, User $user): Builder
    {
        return $query->where(function ($q) use ($user) {
            $q->whereHas('assignedLender', fn ($qq) => $qq->where('id', $user->id))
              ->orWhereHas('collaborators', fn ($qq) => $qq->where('user_id', $user->id));
        });
    }

    public function scopeVisibleToIsa(Builder $query, User $user): Builder
    {
        return $query->where('assigned_user_id', $user->id);
    }
}
