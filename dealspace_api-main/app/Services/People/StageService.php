<?php
namespace App\Services\People;

use App\Models\Stage;
use App\Repositories\People\StagesRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class StageService implements StageServiceInterface
{
    protected $stageRepository;

    public function __construct(
        StagesRepositoryInterface $stageRepository
    ) {
        $this->stageRepository = $stageRepository;
    }

    /**
     * Get all stages.
     *
     * @return Collection Collection of Stage model instances
     */
    public function getAll() : Collection
    {
        return $this->stageRepository->getAll();
    }

    /**
     * Get a specific stage by ID.
     *
     * @param int $id The ID of the stage to retrieve
     * @return Stage The Stage model instance
     * @throws ModelNotFoundException
     */
    public function findById(int $id): Stage
    {
        $stage = $this->stageRepository->findById($id);

        if (!$stage) {
            throw new ModelNotFoundException('Stage not found');
        }

        return $stage;
    }

    /**
     * Create a new stage.
     *
     * @param array $data The stage data including:
     * - 'name' (string) The name of the stage
     * - 'description' (string) The description of the stage
     * @return Stage The newly created Stage model instance
     */
    public function create(array $data): Stage
    {
        return $this->stageRepository->create($data);
    }

    /**
     * Update an existing stage.
     *
     * @param int $id The ID of the stage to update
     * @param array $data The updated stage data including:
     * - ['name'] (string) The updated name of the stage
     * - ['description'] (string) The updated description of the stage
     * @return Stage The updated Stage model instance
     * @throws ModelNotFoundException
     */
    public function update(int $id, array $data): Stage
    {
        $stage = $this->stageRepository->findById($id);

        if (!$stage) {
            throw new ModelNotFoundException('Stage not found');
        }

        return $this->stageRepository->update($stage, $data);
    }

    /**
     * Delete a stage.
     *
     * @param int $id The ID of the stage to delete
     * @return bool True if the deletion was successful, false otherwise
     * @throws ModelNotFoundException
     */
    public function delete(int $id): bool
    {
        $stage = $this->stageRepository->findById($id);

        if (!$stage) {
            throw new ModelNotFoundException('Stage not found');
        }

        return $this->stageRepository->delete($stage);
    }
}