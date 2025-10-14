<?php

namespace App\Services\Teams;

use App\Models\Team;

interface TeamServiceInterface
{
    public function getAll(int $perPage = 15, int $page = 1, string $search = null);
    public function findById(int $teamId): Team;
    public function create(array $data): Team;
    public function update(int $teamId, array $data): Team;
    public function delete(int $teamId): bool;
    public function bulkDelete(array $params): int;
    public function addUserToTeam(int $teamId, int $userId): void;
    public function removeUserFromTeam(int $teamId, int $userId): void;
}
