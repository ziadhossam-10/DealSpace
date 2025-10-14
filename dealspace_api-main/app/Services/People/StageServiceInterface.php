<?php
namespace App\Services\People;

use App\Models\Stage;
use Illuminate\Database\Eloquent\Collection;

interface StageServiceInterface
{
    /**
     * Get all stages.
     *
     * @return Collection Collection of stages
     */
    public function getAll(): Collection;

    /**
     * Get a stage by id.
     *
     * @param int $id The ID of the stage
     * @return Stage
     */
    public function findById(int $id): Stage;

    /**
     * Create a new stage.
     *
     * @param array $data The stage data
     * @return Stage
     */
    public function create(array $data): Stage;

    /**
     * Update an existing stage.
     *
     * @param int $id The ID of the stage
     * @param array $data The updated stage data
     * @return Stage
     */
    public function update(int $id, array $data): Stage;

    /**
     * Delete a stage.
     *
     * @param int $id The ID of the stage
     * @return bool
     */
    public function delete(int $id): bool;
}