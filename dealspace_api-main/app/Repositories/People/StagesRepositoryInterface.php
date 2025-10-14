<?php
namespace App\Repositories\People;

use App\Models\Stage;

interface StagesRepositoryInterface
{

    /**
     * Get all stages.
     *
     * @return \Illuminate\Database\Eloquent\Collection Collection of Stage model instances.
     */
    public function getAll();

    /**
     * Find a stage by its ID.
     *
     * @param int $id The ID of the stage to find.
     * @return Stage|null The found stage or null if not found.
     */
    public function findById(int $id): ?Stage;

    /**
     * Create a new stage record.
     *
     * @param array $data The stage data including:
     * - 'name' (string) The name of the stage.
     * - 'description' (string) The description of the stage.
     * @return Stage The newly created Stage model instance.
     */
    public function create(array $data): Stage;

    /**
     * Update an existing stage record with new data.
     *
     * @param Stage $stage The stage instance to update.
     * @param array $data The updated stage data including:
     * - ['name'] (string) The updated name of the stage.
     * - ['description'] (string) The updated description of the stage.
     * @return Stage The updated Stage model instance.
     */
    public function update(Stage $stage, array $data): Stage;

    /**
     * Delete a stage record from the database.
     *
     * @param Stage $stage The stage instance to delete.
     * @return bool True if the deletion was successful, false otherwise.
     */
    public function delete(Stage $stage): bool;
}