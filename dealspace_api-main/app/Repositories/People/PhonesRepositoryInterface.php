<?php
namespace App\Repositories\People;

use App\Models\PersonPhone;
use Illuminate\Support\Collection;

interface PhonesRepositoryInterface
{
    /**
     * Get all phones for a specific person.
     *
     * @param int $personId The ID of the person.
     * @return Collection Collection of PersonPhone objects.
     */
    public function all(int $personId): Collection;

    /**
     * Find an phone by its ID and the ID of the person it belongs to.
     *
     * @param int $phoneId The ID of the phone to find.
     * @param int $personId The ID of the person the phone belongs to.
     * @return PersonPhone|null The found phone or null if not found.
     */
    public function find(int $phoneId, int $personId): ?PersonPhone;

    /**
     * Create a new phone record for a specific person.
     *
     * @param int $personId The ID of the person to associate the phone with.
     * @param array $data The data for the new phone.
     * @return PersonPhone The newly created PersonPhone model instance.
     */
    public function create(int $personId, array $data): PersonPhone;

    /**
     * Update an existing phone for a person.
     *
     * @param PersonPhone $phone The phone to update.
     * @param array $data The updated phone data.
     * @return PersonPhone The updated PersonPhone model instance.
     */
    public function update(PersonPhone $phone, array $data): PersonPhone;

    /**
     * Delete an phone from a person.
     *
     * @param PersonPhone $phone The phone to delete.
     * @return bool True if deletion was successful, false otherwise.
     */
    public function delete(PersonPhone $phone): bool;

    /**
     * Reset all primary phones for a person to false, except for the specified phone.
     *
     * @param int $personId The ID of the person to reset the primary phone for.
     * @param int|null $exceptPhoneId The ID of an phone to exclude from resetting.
     * @return PersonPhone
     */
    public function setPrimary(PersonPhone $phone): PersonPhone;
}