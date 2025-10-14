<?php

namespace App\Services\Deals;

use App\Models\DealType;

interface DealTypeServiceInterface
{
    public function getAll();
    public function findById(int $dealTypeId): DealType;
    public function create(array $data): DealType;
    public function update(int $dealTypeId, array $data): DealType;
    public function delete(int $dealTypeId): bool;
    public function updateSortOrder(int $dealTypeId, int $newSortOrder): void;
}
