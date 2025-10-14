<?php

namespace App\Repositories\Appointments;

use App\Models\AppointmentOutcome;

interface AppointmentOutcomesRepositoryInterface
{
    public function getAll();
    public function findById(int $outcomeId): ?AppointmentOutcome;
    public function create(array $data): AppointmentOutcome;
    public function update(AppointmentOutcome $AppointmentOutcome, array $data): AppointmentOutcome;
    public function delete(AppointmentOutcome $AppointmentOutcome): bool;
    public function updateSortOrder(int $outcomeId, int $newSortOrder): void;
}
