<?php

namespace App\Services\Deals;

use App\Models\DealStage;
use App\Repositories\Deals\DealStagesRepositoryInterface;
use App\Repositories\Deals\DealTypesRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DealStageService implements DealStageServiceInterface
{
    protected $stagesRepository;
    protected $dealTypesRepository;

    public function __construct(
        DealStagesRepositoryInterface $stagesRepository,
        DealTypesRepositoryInterface $dealTypesRepository
    ) {
        $this->stagesRepository = $stagesRepository;
        $this->dealTypesRepository = $dealTypesRepository;
    }

    /**
     * Get all stages.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAll(int $typeId)
    {
        return $this->stagesRepository->getAll($typeId);
    }

    /**
     * Get a stage by ID.
     *
     * @param int $stageId
     * @return DealStage
     * @throws ModelNotFoundException
     */
    public function findById(int $stageId): DealStage
    {
        $stage = $this->stagesRepository->findById($stageId);
        if (!$stage) {
            throw new ModelNotFoundException('DealStage not found');
        }
        return $stage;
    }

    /**
     * Create a new stage.
     *
     * @param array $data The stage data including:
     * - 'name' (string) The name of the stage
     * - 'sort' (int) The sort order
     * - 'type_id' (int) The ID of the deal type
     * @return DealStage
     * @throws ModelNotFoundException
     */
    public function create(array $data): DealStage
    {
        // Verify that the deal type exists
        $dealType = $this->dealTypesRepository->findById($data['type_id']);
        if (!$dealType) {
            throw new ModelNotFoundException('Deal type not found');
        }
        return $this->stagesRepository->create($data);
    }

    /**
     * Update an existing stage.
     *
     * @param int $stageId
     * @param array $data The updated stage data
     * @return DealStage
     * @throws ModelNotFoundException
     */
    public function update(int $stageId, array $data): DealStage
    {
        $stage = $this->stagesRepository->findById($stageId);
        if (!$stage) {
            throw new ModelNotFoundException('DealStage not found');
        }

        // If changing deal type, verify that the new deal type exists
        if (isset($data['type_id'])) {
            $dealType = $this->dealTypesRepository->findById($data['type_id']);
            if (!$dealType) {
                throw new ModelNotFoundException('Deal type not found');
            }
        }

        return $this->stagesRepository->update($stage, $data);
    }

    /**
     * Delete a stage.
     *
     * @param int $stageId
     * @return bool
     * @throws ModelNotFoundException
     */
    public function delete(int $stageId): bool
    {
        $stage = $this->stagesRepository->findById($stageId);
        if (!$stage) {
            throw new ModelNotFoundException('DealStage not found');
        }
        return $this->stagesRepository->delete($stage);
    }

    /**
     * Update the sort order of a deal stage.
     *
     * @param int $stageId The ID of the stage to update.
     * @param int $newSortOrder The new sort order value.
     * @return void
     * @throws ModelNotFoundException
     */
    public function updateSortOrder(int $stageId, int $newSortOrder): void
    {
        $stage = $this->stagesRepository->findById($stageId);
        if (!$stage) {
            throw new ModelNotFoundException('DealStage not found');
        }

        $this->stagesRepository->updateSortOrder($stageId, $newSortOrder);
    }
}
