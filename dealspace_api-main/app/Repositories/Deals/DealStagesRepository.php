<?php

namespace App\Repositories\Deals;

use App\Models\DealStage;
use Illuminate\Support\Facades\DB;

class DealStagesRepository implements DealStagesRepositoryInterface
{
    protected $model;

    public function __construct(DealStage $model)
    {
        $this->model = $model;
    }

    /**
     * Get all stages with pagination.
     *
     * @param int $typeId The ID of the deal type to filter stages by.
     * @return Collection stage records with relationships loaded.
     */
    public function getAll(int $typeId)
    {
        $stageQuery = $this->model->query();
        if ($typeId) {
            $stageQuery->where('type_id', $typeId);
        }
        return $stageQuery->with(['type'])
            ->orderBy('sort')
            ->get();
    }

    /**
     * Find a stage by its ID.
     *
     * @param int $stageId The ID of the stage to find.
     * @return DealStage|null The found stage or null if not found.
     */
    public function findById(int $stageId): ?DealStage
    {
        return $this->model->with(['type'])->find($stageId);
    }

    /**
     * Create a new stage record.
     *
     * @param array $data The data for the new stage.
     * @return DealStage The newly created DealStage model instance.
     */
    public function create(array $data): DealStage
    {
        return $this->model->create($data);
    }

    /**
     * Update an existing stage.
     *
     * @param DealStage $stage The stage to update.
     * @param array $data The updated stage data.
     * @return DealStage The updated DealStage model instance with fresh relationships.
     */
    public function update(DealStage $stage, array $data): DealStage
    {
        $stage->update($data);
        return $stage->fresh(['type']);
    }

    /**
     * Delete a stage.
     *
     * @param DealStage $stage The stage to delete.
     * @return bool True if deletion was successful, false otherwise.
     */
    public function delete(DealStage $stage): bool
    {
        return $stage->delete();
    }

    /**
     * Update the sort order of a deal stage with automatic reordering within its type.
     * When a stage is moved to a new position, other stages within the same type are shifted accordingly.
     *
     * @param int $stageId The ID of the stage to update.
     * @param int $newSortOrder The new sort order value.
     * @return void
     */
    public function updateSortOrder(int $stageId, int $newSortOrder): void
    {
        DB::transaction(function () use ($stageId, $newSortOrder) {
            // Get current sort order and type_id of the stage being moved
            $stage = $this->model->find($stageId);
            if (!$stage) {
                return; // Stage not found
            }

            $currentSortOrder = $stage->sort;
            $typeId = $stage->type_id;

            // If the position hasn't changed, do nothing
            if ($currentSortOrder == $newSortOrder) {
                return;
            }

            if ($newSortOrder < $currentSortOrder) {
                // Moving up: increment sort order for stages between new and current position within the same type
                $this->model->where('type_id', $typeId)
                    ->where('sort', '>=', $newSortOrder)
                    ->where('sort', '<', $currentSortOrder)
                    ->increment('sort');
            } else {
                // Moving down: decrement sort order for stages between current and new position within the same type
                $this->model->where('type_id', $typeId)
                    ->where('sort', '>', $currentSortOrder)
                    ->where('sort', '<=', $newSortOrder)
                    ->decrement('sort');
            }

            // Update the target stage's sort order
            $stage->update(['sort' => $newSortOrder]);
        });
    }
}