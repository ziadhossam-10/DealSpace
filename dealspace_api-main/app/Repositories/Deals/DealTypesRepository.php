<?php

namespace App\Repositories\Deals;

use App\Models\DealType;
use Illuminate\Support\Facades\DB;

class DealTypesRepository implements DealTypesRepositoryInterface
{
    protected $model;

    public function __construct(DealType $model)
    {
        $this->model = $model;
    }

    /**
     * Get all deal types.
     *
     * @return Collection deal type records.
     */
    public function getAll()
    {
        return $this->model->orderBy('sort')->get();
    }

    /**
     * Find a deal type by its ID.
     *
     * @param int $dealTypeId The ID of the deal type to find.
     * @return DealType|null The found deal type or null if not found.
     */
    public function findById(int $dealTypeId): ?DealType
    {
        return $this->model->find($dealTypeId);
    }

    /**
     * Create a new deal type record.
     *
     * @param array $data The data for the new deal type.
     * @return DealType The newly created DealType model instance.
     */
    public function create(array $data): DealType
    {
        return $this->model->create($data);
    }

    /**
     * Update an existing deal type.
     *
     * @param DealType $dealType The deal type to update.
     * @param array $data The updated deal type data.
     * @return DealType The updated DealType model instance.
     */
    public function update(DealType $dealType, array $data): DealType
    {
        $dealType->update($data);
        return $dealType->fresh();
    }

    /**
     * Delete a deal type.
     *
     * @param DealType $dealType The deal type to delete.
     * @return bool True if deletion was successful, false otherwise.
     */
    public function delete(DealType $dealType): bool
    {
        return $dealType->delete();
    }

    /**
     * Update the sort order of a deal type with automatic reordering.
     * When a deal type is moved to a new position, other deal types are shifted accordingly.
     *
     * @param int $dealTypeId The ID of the deal type to update.
     * @param int $newSortOrder The new sort order value.
     * @return void
     */
    public function updateSortOrder(int $dealTypeId, int $newSortOrder): void
    {
        DB::transaction(function () use ($dealTypeId, $newSortOrder) {
            // Get current sort order of the deal type being moved
            $dealType = $this->model->find($dealTypeId);
            if (!$dealType) {
                return; // Deal type not found
            }

            $currentSortOrder = $dealType->sort;

            // If the position hasn't changed, do nothing
            if ($currentSortOrder == $newSortOrder) {
                return;
            }

            if ($newSortOrder < $currentSortOrder) {
                // Moving up: increment sort order for deal types between new and current position
                $this->model->where('sort', '>=', $newSortOrder)
                    ->where('sort', '<', $currentSortOrder)
                    ->increment('sort');
            } else {
                // Moving down: decrement sort order for deal types between current and new position
                $this->model->where('sort', '>', $currentSortOrder)
                    ->where('sort', '<=', $newSortOrder)
                    ->decrement('sort');
            }

            // Update the target deal type's sort order
            $dealType->update(['sort' => $newSortOrder]);
        });
    }
}
