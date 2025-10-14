<?php
namespace App\Repositories\People;

use App\Models\PersonAddress;
use Illuminate\Support\Collection;

interface AddressesRepositoryInterface
{
    /**
     * Get all addresses for a specific person.
     *
     * @param int $personId The ID of the person.
     * @return Collection Collection of PersonAddress objects.
     */
    public function all(int $personId): Collection;

    /**
     * Find an address by its ID and the ID of the person it belongs to.
     *
     * @param int $addressId The ID of the address to find.
     * @param int $personId The ID of the person the address belongs to.
     * @return PersonAddress|null The found address or null if not found.
     */
    public function find(int $addressId, int $personId): ?PersonAddress;

    /**
     * Create a new address record for a specific person.
     *
     * @param int $personId The ID of the person to associate the address with.
     * @param array $data The data for the new address.
     * @return PersonAddress The newly created PersonAddress model instance.
     */
    public function create(int $personId, array $data): PersonAddress;

    /**
     * Update an existing address for a person.
     *
     * @param PersonAddress $address The address to update.
     * @param array $data The updated address data.
     * @return PersonAddress The updated PersonAddress model instance.
     */
    public function update(PersonAddress $address, array $data): PersonAddress;

    /**
     * Delete an address from a person.
     *
     * @param PersonAddress $address The address to delete.
     * @return bool True if deletion was successful, false otherwise.
     */
    public function delete(PersonAddress $address): bool;

    /**
     * Reset all primary addresses for a person to false, except for the specified address.
     *
     * @param int $personId The ID of the person to reset the primary address for.
     * @param int|null $exceptAddressId The ID of an address to exclude from resetting.
     * @return PersonAddress
     */
    public function setPrimary(PersonAddress $address): PersonAddress;
}