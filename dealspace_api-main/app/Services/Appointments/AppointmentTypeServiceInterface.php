<?php

namespace App\Services\Appointments;

use App\Models\AppointmentType;

interface AppointmentTypeServiceInterface
{
    public function getAll();
    public function findById(int $typeId): AppointmentType;
    public function create(array $data): AppointmentType;
    public function update(int $typeId, array $data): AppointmentType;
    public function delete(int $typeId): bool;
    public function updateSortOrder(int $typeId, int $newSortOrder): void;
}
