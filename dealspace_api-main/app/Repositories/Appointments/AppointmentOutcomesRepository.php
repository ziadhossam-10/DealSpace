<?php

namespace App\Repositories\Appointments;

use App\Models\AppointmentOutcome;
use Illuminate\Support\Facades\DB;

class AppointmentOutcomesRepository implements AppointmentOutcomesRepositoryInterface
{
    protected $model;

    public function __construct(AppointmentOutcome $model)
    {
        $this->model = $model;
    }

    /**
     * Get all outcomes with pagination.
     *
     * @return Collection outcome records with relationships loaded.
     */
    public function getAll()
    {
        $outcomeQuery = $this->model->query();
        return $outcomeQuery
            ->orderBy('sort')
            ->get();
    }

    /**
     * Find a outcome by its ID.
     *
     * @param int $outcomeId The ID of the outcome to find.
     * @return AppointmentOutcome|null The found outcome or null if not found.
     */
    public function findById(int $outcomeId): ?AppointmentOutcome
    {
        return $this->model->find($outcomeId);
    }

    /**
     * Create a new outcome record.
     *
     * @param array $data The data for the new outcome.
     * @return AppointmentOutcome The newly created AppointmentOutcome model instance.
     */
    public function create(array $data): AppointmentOutcome
    {
        return $this->model->create($data);
    }

    /**
     * Update an existing outcome.
     *
     * @param AppointmentOutcome $outcome The outcome to update.
     * @param array $data The updated outcome data.
     * @return AppointmentOutcome The updated AppointmentOutcome model instance with fresh relationships.
     */
    public function update(AppointmentOutcome $outcome, array $data): AppointmentOutcome
    {
        $outcome->update($data);
        return $outcome->fresh();
    }

    /**
     * Delete a outcome.
     *
     * @param AppointmentOutcome $outcome The outcome to delete.
     * @return bool True if deletion was successful, false otherwise.
     */
    public function delete(AppointmentOutcome $outcome): bool
    {
        return $outcome->delete();
    }

    /**
     * Update the sort order of a appointment outcome with automatic reordering within its outcome.
     * When a outcome is moved to a new position, other outcomes within the same outcome are shifted accordingly.
     *
     * @param int $outcomeId The ID of the outcome to update.
     * @param int $newSortOrder The new sort order value.
     * @return void
     */
    public function updateSortOrder(int $outcomeId, int $newSortOrder): void
    {
        DB::transaction(function () use ($outcomeId, $newSortOrder) {
            // Get current sort order and outcome_id of the outcome being moved
            $outcome = $this->model->find($outcomeId);
            if (!$outcome) {
                return; // Outcome not found
            }

            $currentSortOrder = $outcome->sort;

            // If the position hasn't changed, do nothing
            if ($currentSortOrder == $newSortOrder) {
                return;
            }

            if ($newSortOrder < $currentSortOrder) {
                // Moving up: increment sort order for outcomes between new and current position within the same outcome
                $this->model
                    ->where('sort', '>=', $newSortOrder)
                    ->where('sort', '<', $currentSortOrder)
                    ->increment('sort');
            } else {
                // Moving down: decrement sort order for outcomes between current and new position within the same outcome
                $this->model
                    ->where('sort', '>', $currentSortOrder)
                    ->where('sort', '<=', $newSortOrder)
                    ->decrement('sort');
            }

            // Update the target outcome's sort order
            $outcome->update(['sort' => $newSortOrder]);
        });
    }
}
