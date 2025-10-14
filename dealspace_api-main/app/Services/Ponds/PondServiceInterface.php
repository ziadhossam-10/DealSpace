<?php

namespace App\Services\Ponds;

use App\Models\Pond;

interface PondServiceInterface
{
    public function getAll(int $perPage = 15, int $page = 1, string $search = null);
    public function findById(int $pondId): Pond;
    public function create(array $data): Pond;
    public function update(int $pondId, array $data): Pond;
    public function delete(int $pondId): bool;
    public function addUserToPond(int $pondId, int $userId): void;
    public function removeUserFromPond(int $pondId, int $userId): void;
    public function bulkDelete(array $params): int;
}
