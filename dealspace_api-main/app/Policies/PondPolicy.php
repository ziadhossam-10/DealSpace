<?php

namespace App\Policies;

use App\Models\Pond;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use App\Enums\RoleEnum;


class PondPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user, Pond $pond): bool
    {
        return $this->isOwnerOrAdminOwner($user, $pond) || $this->isAssignedToPond($user, $pond);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Pond $pond): bool
    {
        return $this->isOwnerOrAdminOwner($user, $pond);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->isOwnerOrAdminOwner($user, new Pond());
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Pond $pond): bool
    {
        return $this->isOwnerOrAdminOwner($user, $pond);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Pond $pond): bool
    {
        return $this->isOwnerOrAdminOwner($user, $pond);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Pond $pond): bool
    {
        return $this->isOwnerOrAdminOwner($user, $pond);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Pond $pond): bool
    {
        return $this->isOwnerOrAdminOwner($user, $pond);
    }

    private function role(User $user): RoleEnum
    {
        return $user->role instanceof RoleEnum
            ? $user->role
            : RoleEnum::from((int) $user->role);
        }
        
    private function isPondOwner(User $user, Pond $pond): bool
    {
        if (!method_exists($pond, 'user')) {
            return false;
        }

        return $pond->user_id === $user->id;
    }
    private function isOwnerOrAdminOwner(User $user, Pond $pond): bool
    {
        return in_array($this->role($user), [RoleEnum::OWNER, RoleEnum::ADMIN]) || $this->isPondOwner($user, $pond);
    }
    private function isAssignedToPond(User $user, Pond $pond): bool
    {
        if (!method_exists($pond, 'users')) {
            return false;
        }

        return $pond->users->contains($user);
    }
}
