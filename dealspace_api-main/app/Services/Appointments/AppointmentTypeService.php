<?php

namespace App\Services\Appointments;

use App\Models\AppointmentType;
use App\Repositories\Appointments\AppointmentTypesRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AppointmentTypeService implements AppointmentTypeServiceInterface
{
    protected $appointmentTypesRepository;

    public function __construct(
        AppointmentTypesRepositoryInterface $appointmentTypesRepository,
    ) {
        $this->appointmentTypesRepository = $appointmentTypesRepository;
    }

    /**
     * Get all types.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAll()
    {
        return $this->appointmentTypesRepository->getAll();
    }

    /**
     * Get a type by ID.
     *
     * @param int $typeId
     * @return AppointmentType
     * @throws ModelNotFoundException
     */
    public function findById(int $typeId): AppointmentType
    {
        $type = $this->appointmentTypesRepository->findById($typeId);
        if (!$type) {
            throw new ModelNotFoundException('AppointmentType not found');
        }
        return $type;
    }

    /**
     * Create a new type.
     *
     * @param array $data The type data including:
     * - 'name' (string) The name of the type
     * - 'sort' (int) The sort order
     * - 'type_id' (int) The ID of the appointment type
     * @return AppointmentType
     * @throws ModelNotFoundException
     */
    public function create(array $data): AppointmentType
    {
        return $this->appointmentTypesRepository->create($data);
    }

    /**
     * Update an existing type.
     *
     * @param int $typeId
     * @param array $data The updated type data
     * @return AppointmentType
     * @throws ModelNotFoundException
     */
    public function update(int $typeId, array $data): AppointmentType
    {
        $type = $this->appointmentTypesRepository->findById($typeId);
        if (!$type) {
            throw new ModelNotFoundException('AppointmentType not found');
        }

        return $this->appointmentTypesRepository->update($type, $data);
    }

    /**
     * Delete a type.
     *
     * @param int $typeId
     * @return bool
     * @throws ModelNotFoundException
     */
    public function delete(int $typeId): bool
    {
        $type = $this->appointmentTypesRepository->findById($typeId);
        if (!$type) {
            throw new ModelNotFoundException('AppointmentType not found');
        }
        return $this->appointmentTypesRepository->delete($type);
    }

    /**
     * Update the sort order of a appointment type.
     *
     * @param int $typeId The ID of the type to update.
     * @param int $newSortOrder The new sort order value.
     * @return void
     * @throws ModelNotFoundException
     */
    public function updateSortOrder(int $typeId, int $newSortOrder): void
    {
        $type = $this->appointmentTypesRepository->findById($typeId);
        if (!$type) {
            throw new ModelNotFoundException('AppointmentType not found');
        }

        $this->appointmentTypesRepository->updateSortOrder($typeId, $newSortOrder);
    }
}
