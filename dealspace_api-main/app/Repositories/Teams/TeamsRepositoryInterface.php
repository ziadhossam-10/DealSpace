<?php

namespace App\Repositories\Teams;

use App\Models\Team;

interface TeamsRepositoryInterface
{
    public function getAll(int $perPage = 15, int $page = 1, string $search = null);
    public function findById(int $teamId): ?Team;
    public function create(array $data): Team;
    public function update(Team $team, array $data): Team;
    public function delete(Team $team): bool;
    public function deleteAll(): int;
    public function deleteAllExcept(array $ids): int;
    public function deleteSome(array $ids): int;
    public function addUser(Team $team, int $userId): void;
    public function removeUser(Team $team, int $userId): void;
    public function addLeader(Team $team, int $userId): void;
    public function removeLeader(Team $team, int $userId): void;
}
