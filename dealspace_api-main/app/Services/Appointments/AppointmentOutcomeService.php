<?php

namespace App\Services\Appointments;

use App\Models\AppointmentOutcome;
use App\Repositories\Appointments\AppointmentOutcomesRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AppointmentOutcomeService implements AppointmentOutcomeServiceInterface
{
    protected $appointmentOutcomesRepository;

    public function __construct(
        AppointmentOutcomesRepositoryInterface $appointmentOutcomesRepository,
    ) {
        $this->appointmentOutcomesRepository = $appointmentOutcomesRepository;
    }

    /**
     * Get all outcomes.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAll()
    {
        return $this->appointmentOutcomesRepository->getAll();
    }

    /**
     * Get a outcome by ID.
     *
     * @param int $outcomeId
     * @return AppointmentOutcome
     * @throws ModelNotFoundException
     */
    public function findById(int $outcomeId): AppointmentOutcome
    {
        $outcome = $this->appointmentOutcomesRepository->findById($outcomeId);
        if (!$outcome) {
            throw new ModelNotFoundException('AppointmentOutcome not found');
        }
        return $outcome;
    }

    /**
     * Create a new outcome.
     *
     * @param array $data The outcome data including:
     * - 'name' (string) The name of the outcome
     * - 'sort' (int) The sort order
     * - 'outcome_id' (int) The ID of the appointment outcome
     * @return AppointmentOutcome
     * @throws ModelNotFoundException
     */
    public function create(array $data): AppointmentOutcome
    {
        return $this->appointmentOutcomesRepository->create($data);
    }

    /**
     * Update an existing outcome.
     *
     * @param int $outcomeId
     * @param array $data The updated outcome data
     * @return AppointmentOutcome
     * @throws ModelNotFoundException
     */
    public function update(int $outcomeId, array $data): AppointmentOutcome
    {
        $outcome = $this->appointmentOutcomesRepository->findById($outcomeId);
        if (!$outcome) {
            throw new ModelNotFoundException('AppointmentOutcome not found');
        }

        return $this->appointmentOutcomesRepository->update($outcome, $data);
    }

    /**
     * Delete a outcome.
     *
     * @param int $outcomeId
     * @return bool
     * @throws ModelNotFoundException
     */
    public function delete(int $outcomeId): bool
    {
        $outcome = $this->appointmentOutcomesRepository->findById($outcomeId);
        if (!$outcome) {
            throw new ModelNotFoundException('AppointmentOutcome not found');
        }
        return $this->appointmentOutcomesRepository->delete($outcome);
    }

    /**
     * Update the sort order of a appointment outcome.
     *
     * @param int $outcomeId The ID of the outcome to update.
     * @param int $newSortOrder The new sort order value.
     * @return void
     * @throws ModelNotFoundException
     */
    public function updateSortOrder(int $outcomeId, int $newSortOrder): void
    {
        $outcome = $this->appointmentOutcomesRepository->findById($outcomeId);
        if (!$outcome) {
            throw new ModelNotFoundException('AppointmentOutcome not found');
        }

        $this->appointmentOutcomesRepository->updateSortOrder($outcomeId, $newSortOrder);
    }
}
