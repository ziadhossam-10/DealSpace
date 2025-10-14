<?php

namespace App\Repositories\Appointments;

use App\Models\AppointmentType;

interface AppointmentTypesRepositoryInterface
{
    public function getAll();
    public function findById(int $typeId): ?AppointmentType;
    public function create(array $data): AppointmentType;
    public function update(AppointmentType $AppointmentType, array $data): AppointmentType;
    public function delete(AppointmentType $AppointmentType): bool;
    public function updateSortOrder(int $typeId, int $newSortOrder): void;
}
