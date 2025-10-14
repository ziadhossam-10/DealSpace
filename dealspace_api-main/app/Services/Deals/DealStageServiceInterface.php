<?php

namespace App\Services\Deals;

use App\Models\DealStage;

interface DealStageServiceInterface
{
    public function getAll(int $typeId);
    public function findById(int $stageId): DealStage;
    public function create(array $data): DealStage;
    public function update(int $stageId, array $data): DealStage;
    public function delete(int $stageId): bool;
    public function updateSortOrder(int $stageId, int $newSortOrder): void;
}
