<?php

namespace App\Policies;

use App\Models\DealStage;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use App\Enums\RoleEnum;

class DealStagePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, DealStage $dealStage): bool
    {
        return $this->isAdminOrOwner($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->isAdminOrOwner($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, DealStage $dealStage): bool
    {
        return $this->isAdminOrOwner($user);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, DealStage $dealStage): bool
    {
        return $this->isAdminOrOwner($user);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, DealStage $dealStage): bool
    {
        return $this->isAdminOrOwner($user);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, DealStage $dealStage): bool
    {
        return $this->isAdminOrOwner($user);
    }
    private function isAdminOrOwner(User $user): bool
    {
        return $user->role === RoleEnum::ADMIN || $user->role === RoleEnum::OWNER;
    }
}
