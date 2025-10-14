<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;
use App\Enums\RoleEnum;

class TeamPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user, Team $team): bool
    {
        return in_array($user->role, [RoleEnum::ADMIN, RoleEnum::OWNER]) || $this->isAssignedUser($user, $team);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Team $team): bool
    {
        return in_array($user->role, [RoleEnum::ADMIN, RoleEnum::OWNER]);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, [RoleEnum::ADMIN, RoleEnum::OWNER]);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Team $team): bool
    {
        return in_array($user->role, [RoleEnum::ADMIN, RoleEnum::OWNER]);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Team $team): bool
    {
        return in_array($user->role, [RoleEnum::ADMIN, RoleEnum::OWNER]);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Team $team): bool
    {
        return in_array($user->role, [RoleEnum::ADMIN, RoleEnum::OWNER]);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Team $team): bool
    {
        return in_array($user->role, [RoleEnum::ADMIN, RoleEnum::OWNER]);
    }
    /**
     * Get Assigned User of Team.
     */
    private function isAssignedUser(User $user, Team $team): bool
    {
        return $team->users->contains($user);
    }
}
