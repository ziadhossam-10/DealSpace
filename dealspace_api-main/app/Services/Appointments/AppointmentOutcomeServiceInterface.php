<?php

namespace App\Services\Appointments;

use App\Models\AppointmentOutcome;

interface AppointmentOutcomeServiceInterface
{
    public function getAll();
    public function findById(int $outcomeId): AppointmentOutcome;
    public function create(array $data): AppointmentOutcome;
    public function update(int $outcomeId, array $data): AppointmentOutcome;
    public function delete(int $outcomeId): bool;
    public function updateSortOrder(int $outcomeId, int $newSortOrder): void;
}
