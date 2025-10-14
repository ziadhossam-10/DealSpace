<?php
namespace App\Services\People;

use App\Models\PersonPhone;
use Illuminate\Support\Collection;

interface PhoneServiceInterface
{
    /**
     * Get all phones for a specific person.
     *
     * @param int $personId The ID of the person
     * @return Collection Collection of phones
     */
    public function getAll(int $personId): Collection;

    /**
     * Get a specific phone for a person.
     *
     * @param int $personId The ID of the person
     * @param int $phoneId The ID of the phone
     * @return PersonPhone
     */
    public function findById(int $personId, int $phoneId): PersonPhone;

    /**
     * Add a new phone to a person.
     *
     * @param int $personId The ID of the person
     * @param array $data The phone data
     * @return PersonPhone
     */
    public function create(int $personId, array $data): PersonPhone;

    /**
     * Update an existing phone for a person.
     *
     * @param int $personId The ID of the person
     * @param int $phoneId The ID of the phone
     * @param array $data The updated phone data
     * @return PersonPhone
     */
    public function update(int $personId, int $phoneId, array $data): PersonPhone;

    /**
     * Delete an phone for a person.
     *
     * @param int $personId The ID of the person
     * @param int $phoneId The ID of the phone
     * @return bool
     */
    public function delete(int $personId, int $phoneId): bool;

    /**
     * Set an phone as primary for a person.
     *
     * @param int $personId The ID of the person
     * @param int $phoneId The ID of the phone to set as primary
     * @return PersonPhone
     */
    public function setPrimary(int $personId, int $phoneId): PersonPhone;
}
