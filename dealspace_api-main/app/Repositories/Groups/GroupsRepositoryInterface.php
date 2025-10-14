<?php

namespace App\Repositories\Groups;

use App\Models\Group;

interface GroupsRepositoryInterface
{
    public function getAll(int $perPage, int $page, string $search = null);
    public function findById(int $groupId): ?Group;
    public function create(array $data): Group;
    public function update(Group $group, array $data): Group;
    public function delete(Group $group): bool;
    public function getByType(string $type, int $perPage, int $page);
    public function getPrimary(): ?Group;
    public function addUser(Group $group, int $userId, int $sortOrder = 0): void;
    public function removeUser(Group $group, int $userId): void;
    public function updateUserSortOrder(Group $group, int $userId, int $sortOrder): void;
    public function getGroupUsers(int $groupId, int $perPage = 15, int $page = 1);
}
