<?php

namespace App\Repositories\Groups;

use App\Models\Group;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\User;


class GroupsRepository implements GroupsRepositoryInterface
{
    protected $model;
    /**
     * Constructor
     *
     * @param Group $model The Group model instance
     */
    public function __construct(Group $model)
    {
        $this->model = $model;
    }

    /**
     * Retrieves all records with pagination.
     *
     * @param int $perPage The number of items per page
     * @param int $page The page number to retrieve
     * @return LengthAwarePaginator Paginated list of records
     */
    public function getAll(int $perPage = 15, int $page = 1, string $search = null)
    {
        $groupQuery = $this->model->query();
        $user = Auth::user();
        if ($user) {
            $this->model = $this->model->visibleTo($user);
        }
        if ($search) {
            $groupQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            });
        }
        return $groupQuery->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Find a group by its ID.
     *
     * @param int $groupId The ID of the group to find.
     *
     * @return Group|null The found group or null if not found.
     */
    public function findById(int $groupId): ?Group
    {
        return $this->model->with(['users', 'defaultUser', 'defaultGroup', 'defaultPond'])->find($groupId);
    }

    /**
     * Create a new group record.
     *
     * @param array $data The data for the new group.
     *
     * @return Group The newly created Group model instance.
     */
    public function create(array $data): Group
    {
        return $this->model->create($data);
    }

    /**
     * Update an existing group.
     *
     * @param Group $group The group to update.
     * @param array $data The updated group data.
     *
     * @return Group The updated Group model instance.
     */
    public function update(Group $group, array $data): Group
    {
        $group->update($data);
        return $group;
    }

    /**
     * Delete a group.
     *
     * @param Group $group The group to delete.
     * @return bool True if deletion was successful, false otherwise.
     */
    public function delete(Group $group): bool
    {
        return $group->delete();
    }

    /**
     * Get groups by type (Lender or Agent).
     *
     * @param string $type The type of group.
     * @return \Illuminate\Database\Eloquent\Collection Groups of specified type.
     */
    public function getByType(string $type, int $perPage = 15, int $page = 1)
    {
        return $this->model->where('type', $type)->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get the primary group.
     *
     * @return Group|null The primary group or null if none exists.
     */
    public function getPrimary(): ?Group
    {
        return $this->model->where('is_primary', true)->first();
    }

    /**
     * Add a user to a group.
     *
     * @param Group $group The group to add the user to.
     * @param int $userId The ID of the user to add.
     * @param int $sortOrder The sort order for the user in the group.
     * @return void
     */
    public function addUser(Group $group, int $userId, int $sortOrder = 0): void
    {
        if (!$group->users()->where('user_id', $userId)->exists()) {
            $group->users()->attach($userId, ['sort_order' => $sortOrder]);
        }
    }

    /**
     * Remove a user from a group.
     *
     * @param Group $group The group to remove the user from.
     * @param int $userId The ID of the user to remove.
     * @return void
     */
    public function removeUser(Group $group, int $userId): void
    {
        $group->users()->detach($userId);
    }

    /**
     * Update the sort order of a user in a group with automatic reordering.
     * When a user is moved to a new position, other users are shifted accordingly.
     *
     * @param Group $group The group containing the user.
     * @param int $userId The ID of the user to update.
     * @param int $newSortOrder The new sort order value (1-based).
     * @return void
     */
    public function updateUserSortOrder(Group $group, int $userId, int $newSortOrder): void
    {
        DB::transaction(function () use ($group, $userId, $newSortOrder) {
            // Get current sort order of the user being moved
            $currentUserPivot = $group->users()->where('user_id', $userId)->first();
            if (!$currentUserPivot) {
                return; // User not in group
            }

            $currentSortOrder = $currentUserPivot->pivot->sort_order;

            // If the position hasn't changed, do nothing
            if ($currentSortOrder == $newSortOrder) {
                return;
            }

            // Get the pivot table name (assuming it's group_user)
            $pivotTable = $group->users()->getTable();

            if ($newSortOrder < $currentSortOrder) {
                // Moving up: increment sort_order for users between new and current position
                DB::table($pivotTable)
                    ->where('group_id', $group->id)
                    ->where('sort_order', '>=', $newSortOrder)
                    ->where('sort_order', '<', $currentSortOrder)
                    ->increment('sort_order');
            } else {
                // Moving down: decrement sort_order for users between current and new position
                DB::table($pivotTable)
                    ->where('group_id', $group->id)
                    ->where('sort_order', '>', $currentSortOrder)
                    ->where('sort_order', '<=', $newSortOrder)
                    ->decrement('sort_order');
            }

            // Update the target user's sort order
            $group->users()->updateExistingPivot($userId, ['sort_order' => $newSortOrder]);
        });
    }

    /**
     * Get all users in a group ordered by sort_order.
     *
     * @param int $groupId The ID of the group.
     * @return \Illuminate\Database\Eloquent\Collection Users in the group.
     */
    public function getGroupUsers(int $groupId, int $perPage = 15, int $page = 1)
    {
        $group = $this->findById($groupId);
        return $group ? $group->users()->orderBy('sort_order')->paginate($perPage, ['*'], 'page', $page) : collect();
    }
}
