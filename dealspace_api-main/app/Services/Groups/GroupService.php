<?php

namespace App\Services\Groups;

use App\Models\Group;
use App\Repositories\Groups\GroupsRepositoryInterface;
use App\Repositories\Users\UsersRepositoryInterface;
use App\Repositories\Ponds\PondsRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class GroupService implements GroupServiceInterface
{
    protected $groupsRepository;
    protected $usersRepository;
    protected $pondsRepository;

    public function __construct(
        GroupsRepositoryInterface $groupsRepository,
        UsersRepositoryInterface $usersRepository,
        PondsRepositoryInterface $pondsRepository
    ) {
        $this->groupsRepository = $groupsRepository;
        $this->usersRepository = $usersRepository;
        $this->pondsRepository = $pondsRepository;
    }

    /**
     * Get all groups.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAll(int $perPage = 15, int $page = 1, $search = null)
    {
        return $this->groupsRepository->getAll($perPage, $page, $search);
    }

    /**
     * Get a group by ID.
     *
     * @param int $groupId
     * @return Group
     * @throws ModelNotFoundException
     */
    public function findById(int $groupId): Group
    {
        $group = $this->groupsRepository->findById($groupId);
        if (!$group) {
            throw new ModelNotFoundException('Group not found');
        }
        return $group;
    }

    /**
     * Create a new group with users.
     *
     * @param array $data The complete group data including:
     * - 'name' (string) The name of the group
     * - 'type' (string) The type of group (Lender or Agent)
     * - 'distribution' (string) The distribution method (round-robin or first-to-claim)
     * - ['default_user_id'] (int) The ID of the default user for this group
     * - ['default_pond_id'] (int) The ID of the default pond for this group
     * - ['default_group_id'] (int) The ID of the default group for this group
     * - ['claim_window'] (int) The claim window in hours/minutes
     * - ['is_primary'] (bool) Whether this is the primary group
     * - ['user_ids'] (array) Array of user IDs to add to the group (order matters: index 0 = first, index 1 = second, etc.)
     * @return Group
     * @throws ModelNotFoundException
     * @throws InvalidArgumentException
     */
    public function create(array $data): Group
    {
        return DB::transaction(function () use ($data) {
            // Extract user-related arrays
            $userIds = $data['user_ids'] ?? [];

            // Remove user arrays from data to prevent SQL errors
            unset($data['user_ids']);

            // Validate default user if provided
            if (isset($data['default_user_id'])) {
                $user = $this->usersRepository->findById($data['default_user_id']);
                if (!$user) {
                    throw new ModelNotFoundException('Default user not found');
                }
            }

            // Validate default pond if provided
            if (isset($data['default_pond_id'])) {
                $pond = $this->pondsRepository->findById($data['default_pond_id']);
                if (!$pond) {
                    throw new ModelNotFoundException('Default pond not found');
                }
            }

            // Validate default group if provided
            if (isset($data['default_group_id'])) {
                $defaultGroup = $this->groupsRepository->findById($data['default_group_id']);
                if (!$defaultGroup) {
                    throw new ModelNotFoundException('Default group not found');
                }
            }

            // If this group is set as primary, reset other primary groups
            if (isset($data['is_primary']) && $data['is_primary']) {
                $this->resetPrimary();
            }

            // Create the group
            $group = $this->groupsRepository->create($data);

            // Add users to group if user_ids array is provided
            if (!empty($userIds)) {
                $this->addUsersToGroup($group->id, $userIds);
            }

            return $group;
        });
    }

    /**
     * Update an existing group and its users.
     *
     * @param int $groupId
     * @param array $data The complete group data including:
     * - Group fields to update
     * - ['user_ids'] (array) Array of user IDs to add to the group (order matters: index 0 = first, index 1 = second, etc.)
     * - ['user_ids_to_delete'] (array) Array of user IDs to remove from the group
     * @return Group
     * @throws ModelNotFoundException
     * @throws InvalidArgumentException
     */
    public function update(int $groupId, array $data): Group
    {
        return DB::transaction(function () use ($groupId, $data) {
            $group = $this->groupsRepository->findById($groupId);
            if (!$group) {
                throw new ModelNotFoundException('Group not found');
            }

            // Extract user-related arrays
            $userIdsToAdd = $data['user_ids'] ?? [];
            $userIdsToDelete = $data['user_ids_to_delete'] ?? [];

            // Remove user arrays from data to prevent SQL errors
            unset($data['user_ids'], $data['user_ids_to_delete']);

            // Validate default user if provided
            if (isset($data['default_user_id'])) {
                $user = $this->usersRepository->findById($data['default_user_id']);
                if (!$user) {
                    throw new ModelNotFoundException('Default user not found');
                }
            }

            // Validate default pond if provided
            if (isset($data['default_pond_id'])) {
                $pond = $this->pondsRepository->findById($data['default_pond_id']);
                if (!$pond) {
                    throw new ModelNotFoundException('Default pond not found');
                }
            }

            // Validate default group if provided
            if (isset($data['default_group_id'])) {
                // Prevent circular reference
                if ($data['default_group_id'] == $groupId) {
                    throw new InvalidArgumentException('A group cannot be its own default group');
                }

                $defaultGroup = $this->groupsRepository->findById($data['default_group_id']);
                if (!$defaultGroup) {
                    throw new ModelNotFoundException('Default group not found');
                }
            }

            // If this group is set as primary, reset other primary groups
            if (isset($data['is_primary']) && $data['is_primary']) {
                $this->resetPrimary($groupId);
            }

            // Update the group
            $updatedGroup = $this->groupsRepository->update($group, $data);

            // Add users to group
            if (!empty($userIdsToAdd)) {
                $this->addUsersToGroup($groupId, $userIdsToAdd);
            }

            // Remove users from group
            if (!empty($userIdsToDelete)) {
                $this->removeUsersFromGroup($groupId, $userIdsToDelete);
            }

            return $updatedGroup;
        });
    }

    /**
     * Delete a group.
     *
     * @param int $groupId
     * @return bool
     * @throws ModelNotFoundException
     */
    public function delete(int $groupId): bool
    {
        $group = $this->groupsRepository->findById($groupId);
        if (!$group) {
            throw new ModelNotFoundException('Group not found');
        }

        return $this->groupsRepository->delete($group);
    }

    /**
     * Get groups by type.
     *
     * @param string $type The type of group (Lender or Agent)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllByType(string $type, int $perPage = 15, int $page = 1)
    {
        return $this->groupsRepository->getByType($type, $perPage, $page);
    }

    /**
     * Get the primary group.
     *
     * @return Group|null
     */
    public function getPrimary(): ?Group
    {
        return $this->groupsRepository->getPrimary();
    }

    /**
     * Add a user to a group.
     *
     * @param int $groupId
     * @param int $userId
     * @param int $sortOrder
     * @return void
     * @throws ModelNotFoundException
     */
    public function addUserToGroup(int $groupId, int $userId, int $sortOrder = 0): void
    {
        $group = $this->groupsRepository->findById($groupId);
        if (!$group) {
            throw new ModelNotFoundException('Group not found');
        }

        $user = $this->usersRepository->findById($userId);
        if (!$user) {
            throw new ModelNotFoundException('User not found');
        }

        $this->groupsRepository->addUser($group, $userId, $sortOrder);
    }

    /**
     * Remove a user from a group.
     *
     * @param int $groupId
     * @param int $userId
     * @return void
     * @throws ModelNotFoundException
     */
    public function removeUserFromGroup(int $groupId, int $userId): void
    {
        $group = $this->groupsRepository->findById($groupId);
        if (!$group) {
            throw new ModelNotFoundException('Group not found');
        }

        $this->groupsRepository->removeUser($group, $userId);
    }

    /**
     * Update a user's sort order within a group.
     *
     * @param int $groupId
     * @param int $userId
     * @param int $sortOrder
     * @return void
     * @throws ModelNotFoundException
     */
    public function updateUserSortOrder(int $groupId, int $userId, int $sortOrder): void
    {
        $group = $this->groupsRepository->findById($groupId);
        if (!$group) {
            throw new ModelNotFoundException('Group not found');
        }

        $this->groupsRepository->updateUserSortOrder($group, $userId, $sortOrder);
    }

    /**
     * Get all users in a group.
     *
     * @param int $groupId
     * @return \Illuminate\Database\Eloquent\Collection
     * @throws ModelNotFoundException
     */
    public function getGroupUsers(int $groupId, int $perPage = 15, int $page = 1)
    {
        $group = $this->groupsRepository->findById($groupId);
        if (!$group) {
            throw new ModelNotFoundException('Group not found');
        }

        return $this->groupsRepository->getGroupUsers($groupId, $perPage, $page);
    }

    /**
     * Reset all primary groups except for the specified group.
     *
     * @param int|null $exceptGroupId The ID of a group to exclude from resetting.
     * @return void
     */
    public function resetPrimary(int $exceptGroupId = null): void
    {
        $primaryGroups = Group::where('is_primary', true);

        if ($exceptGroupId) {
            $primaryGroups->where('id', '!=', $exceptGroupId);
        }

        $primaryGroups->update(['is_primary' => false]);
    }

    /**
     * Add multiple users to a group using their array index as sort order.
     *
     * @param int $groupId
     * @param array $userIds Array of user IDs where index 0 = first user, index 1 = second user, etc.
     * @return void
     * @throws ModelNotFoundException
     */
    protected function addUsersToGroup(int $groupId, array $userIds): void
    {
        foreach ($userIds as $index => $userId) {
            // Use array index as sort order (0 = first, 1 = second, etc.)
            $this->addUserToGroup($groupId, $userId, $index);
        }
    }

    /**
     * Remove multiple users from a group.
     *
     * @param int $groupId
     * @param array $userIds
     * @return void
     * @throws ModelNotFoundException
     */
    protected function removeUsersFromGroup(int $groupId, array $userIds): void
    {
        foreach ($userIds as $userId) {
            $this->removeUserFromGroup($groupId, $userId);
        }
    }
}
