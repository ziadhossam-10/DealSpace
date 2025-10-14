<?php

namespace App\Repositories\Deals;

use App\Models\DealType;

interface DealTypesRepositoryInterface
{
    public function getAll();
    public function findById(int $dealTypeId): ?DealType;
    public function create(array $data): DealType;
    public function update(DealType $dealType, array $data): DealType;
    public function delete(DealType $dealType): bool;
    public function updateSortOrder(int $dealTypeId, int $newSortOrder): void;
}
