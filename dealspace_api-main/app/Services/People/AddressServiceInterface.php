<?php
namespace App\Services\People;

use App\Models\PersonAddress;
use Illuminate\Support\Collection;

interface AddressServiceInterface
{
    /**
     * Get all addresses for a specific person.
     *
     * @param int $personId The ID of the person
     * @return Collection Collection of addresses
     */
    public function getAll(int $personId): Collection;

    /**
     * Get a specific address for a person.
     *
     * @param int $personId The ID of the person
     * @param int $addressId The ID of the address
     * @return PersonAddress
     */
    public function findById(int $personId, int $addressId): PersonAddress;

    /**
     * Add a new address to a person.
     *
     * @param int $personId The ID of the person
     * @param array $data The address data
     * @return PersonAddress
     */
    public function create(int $personId, array $data): PersonAddress;

    /**
     * Update an existing address for a person.
     *
     * @param int $personId The ID of the person
     * @param int $addressId The ID of the address
     * @param array $data The updated address data
     * @return PersonAddress
     */
    public function update(int $personId, int $addressId, array $data): PersonAddress;

    /**
     * Delete an address for a person.
     *
     * @param int $personId The ID of the person
     * @param int $addressId The ID of the address
     * @return bool
     */
    public function delete(int $personId, int $addressId): bool;

    /**
     * Set an address as primary for a person.
     *
     * @param int $personId The ID of the person
     * @param int $addressId The ID of the address to set as primary
     * @return PersonAddress
     */
    public function setPrimary(int $personId, int $addressId): PersonAddress;
}
