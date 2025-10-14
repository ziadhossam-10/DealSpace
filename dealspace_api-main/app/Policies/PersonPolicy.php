<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\Person;
use App\Models\User;

class PersonPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return in_array($this->role($user), [
            RoleEnum::OWNER,
            RoleEnum::ADMIN,
        ], true);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Person $person): bool
    {
        return $this->isOwnerOrAdmin($user)
            || $this->isAssignedAgent($user, $person)
            || $this->isAssignedLender($user, $person)
            || $this->isCollaborator($user, $person)
            || $this->sharedViaPonds($user, $person)
            || in_array($this->role($user), [
                RoleEnum::AGENT,
                RoleEnum::LENDER,
                RoleEnum::ISAs,
            ], true);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return in_array($this->role($user), [
            RoleEnum::OWNER,
            RoleEnum::ADMIN,
            RoleEnum::AGENT,
            RoleEnum::ISAs,
        ], true);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Person $person): bool
    {
        if ($this->isOwnerOrAdmin($user)) {
            return true;
        }

        return $this->isAssignedAgent($user, $person)
            || ($this->role($user) === RoleEnum::ISAs && $this->isAssignedAgent($user, $person))
            || $this->isCollaborator($user, $person)
            || $this->sharedViaPonds($user, $person)
            || $this->role($user) === RoleEnum::AGENT;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Person $person): bool
    {
        return $this->isOwnerOrAdmin($user);

    }
    
    /**
     * Determine whether the user can bulkDelete the models.
     */
    public function bulkDelete(User $user, array $people): bool
    {
        return $this->isOwnerOrAdmin($user);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Person $person): bool
    {
        return $this->delete($user, $person);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Person $person): bool
    {
        return $this->role($user) === RoleEnum::OWNER;
    }

    /**
     * Get the user's role.
     */
    private function role(User $user): RoleEnum
    {
        return $user->role instanceof RoleEnum
            ? $user->role
            : RoleEnum::from((int) $user->role);
    }

    /**
     * Check if the user is an owner or admin.
     */
    private function isOwnerOrAdmin(User $user): bool
    {
        return in_array($this->role($user), [RoleEnum::OWNER, RoleEnum::ADMIN], true);
    }

    /**
     * Check if the user is the assigned agent for the person.
     */
    private function isAssignedAgent(User $user, Person $person): bool
    {
        $userId = $user->id;

        return (int)($person->assigned_user_id ?? 0) === $userId;
    }

    /**
     * Check if the user is the assigned lender for the person.
     */
    private function isAssignedLender(User $user, Person $person): bool
    {
        return $this->role($user) === RoleEnum::LENDER
            && (int)($person->assigned_lender_id ?? 0) === $user->id;
    }

    /**
     * Check if the user is a collaborator for the person.
     */
    private function isCollaborator(User $user, Person $person): bool
    {
        return $person->collaborators()
            ->where('user_id', $user->id)
            ->exists();
    }
    
    /**
     * Check if the person is shared with the user via ponds.
     */
    private function sharedViaPonds(User $user, Person $person): bool
    {
        return $person->assignedPond()
            ->whereHas('users', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            })->exists();
    }
}
