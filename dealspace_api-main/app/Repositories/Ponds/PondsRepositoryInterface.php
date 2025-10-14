<?php

namespace App\Repositories\Ponds;

use App\Models\Pond;

interface PondsRepositoryInterface
{
    public function getAll(int $perPage = 15, int $page = 1, string $serch = null);
    public function findById(int $pondId): ?Pond;
    public function create(array $data): Pond;
    public function update(Pond $pond, array $data): Pond;
    public function delete(Pond $pond): bool;
    public function getByUserId(int $userId);
    public function addUser(Pond $pond, int $userId): void;
    public function removeUser(Pond $pond, int $userId): void;
    public function deleteAll();
    public function deleteAllExcept(array $ids);
    public function deleteSome(array $ids);
}
