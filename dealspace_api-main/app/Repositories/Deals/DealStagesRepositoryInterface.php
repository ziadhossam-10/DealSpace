<?php

namespace App\Repositories\Deals;

use App\Models\DealStage;

interface DealStagesRepositoryInterface
{
    public function getAll(int $typeId);
    public function findById(int $stageId): ?DealStage;
    public function create(array $data): DealStage;
    public function update(DealStage $DealStage, array $data): DealStage;
    public function delete(DealStage $DealStage): bool;
    public function updateSortOrder(int $stageId, int $newSortOrder): void;
}
