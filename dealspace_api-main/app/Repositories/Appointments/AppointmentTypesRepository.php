<?php

namespace App\Repositories\Appointments;

use App\Models\AppointmentType;
use Illuminate\Support\Facades\DB;

class AppointmentTypesRepository implements AppointmentTypesRepositoryInterface
{
    protected $model;

    public function __construct(AppointmentType $model)
    {
        $this->model = $model;
    }

    /**
     * Get all types with pagination.
     *
     * @return Collection type records with relationships loaded.
     */
    public function getAll()
    {
        $typeQuery = $this->model->query();
        return $typeQuery
            ->orderBy('sort')
            ->get();
    }

    /**
     * Find a type by its ID.
     *
     * @param int $typeId The ID of the type to find.
     * @return AppointmentType|null The found type or null if not found.
     */
    public function findById(int $typeId): ?AppointmentType
    {
        return $this->model->find($typeId);
    }

    /**
     * Create a new type record.
     *
     * @param array $data The data for the new type.
     * @return AppointmentType The newly created AppointmentType model instance.
     */
    public function create(array $data): AppointmentType
    {
        return $this->model->create($data);
    }

    /**
     * Update an existing type.
     *
     * @param AppointmentType $type The type to update.
     * @param array $data The updated type data.
     * @return AppointmentType The updated AppointmentType model instance with fresh relationships.
     */
    public function update(AppointmentType $type, array $data): AppointmentType
    {
        $type->update($data);
        return $type->fresh();
    }

    /**
     * Delete a type.
     *
     * @param AppointmentType $type The type to delete.
     * @return bool True if deletion was successful, false otherwise.
     */
    public function delete(AppointmentType $type): bool
    {
        return $type->delete();
    }

    /**
     * Update the sort order of a appointment type with automatic reordering within its type.
     * When a type is moved to a new position, other types within the same type are shifted accordingly.
     *
     * @param int $typeId The ID of the type to update.
     * @param int $newSortOrder The new sort order value.
     * @return void
     */
    public function updateSortOrder(int $typeId, int $newSortOrder): void
    {
        DB::transaction(function () use ($typeId, $newSortOrder) {
            // Get current sort order and type_id of the type being moved
            $type = $this->model->find($typeId);
            if (!$type) {
                return; // Type not found
            }

            $currentSortOrder = $type->sort;

            // If the position hasn't changed, do nothing
            if ($currentSortOrder == $newSortOrder) {
                return;
            }

            if ($newSortOrder < $currentSortOrder) {
                // Moving up: increment sort order for types between new and current position within the same type
                $this->model
                    ->where('sort', '>=', $newSortOrder)
                    ->where('sort', '<', $currentSortOrder)
                    ->increment('sort');
            } else {
                // Moving down: decrement sort order for types between current and new position within the same type
                $this->model
                    ->where('sort', '>', $currentSortOrder)
                    ->where('sort', '<=', $newSortOrder)
                    ->decrement('sort');
            }

            // Update the target type's sort order
            $type->update(['sort' => $newSortOrder]);
        });
    }
}
