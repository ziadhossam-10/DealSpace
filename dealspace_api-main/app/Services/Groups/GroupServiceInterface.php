<?php

namespace App\Services\Groups;

use App\Models\Group;

interface GroupServiceInterface
{
    public function getAll(int $perPage = 15, int $page = 1, string $search = null);
    public function findById(int $groupId): Group;
    public function create(array $data): Group;
    public function update(int $groupId, array $data): Group;
    public function delete(int $groupId): bool;
    public function getAllByType(string $type);
    public function getPrimary(): ?Group;
    public function addUserToGroup(int $groupId, int $userId, int $sortOrder = 0): void;
    public function removeUserFromGroup(int $groupId, int $userId): void;
    public function updateUserSortOrder(int $groupId, int $userId, int $sortOrder): void;
    public function getGroupUsers(int $groupId, int $perPage = 15, int $page = 1);
    public function resetPrimary(int $exceptGroupId = null): void;
}
