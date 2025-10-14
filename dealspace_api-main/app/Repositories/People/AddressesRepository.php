<?php
namespace App\Repositories\People;

use App\Models\PersonAddress;
use Illuminate\Support\Collection;

class AddressesRepository implements AddressesRepositoryInterface
{
    /**
     * Get all addresses for a specific person.
     *
     * @param int $personId The ID of the person.
     * @return Collection Collection of PersonAddress objects.
     */
    public function all(int $personId): Collection
    {
        return PersonAddress::where('person_id', $personId)->get();
    }

    /**
     * Find an address by its ID and the ID of the person it belongs to.
     *
     * @param int $addressId The ID of the address to find.
     * @param int $personId The ID of the person the address belongs to.
     *
     * @return PersonAddress|null The found address or null if not found.
     */
    public function find(int $addressId, int $personId): ?PersonAddress
    {
        return PersonAddress::where('person_id', $personId)->find($addressId);
    }

    /**
     * Create a new address record for a specific person.
     *
     * @param int $personId The ID of the person to associate the address with.
     * @param array $data The data for the new address, including:
     * - 'street_address' (string) The street address.
     * - 'city' (string) The city name.
     * - 'state' (string) The state or province.
     * - 'postal_code' (string) The postal or ZIP code.
     * - ['country'] (string) The country name (optional).
     * - ['type'] (string) The type of the address (optional).
     * - ['is_primary'] (bool) Whether this is the primary address (optional).
     *
     * @return PersonAddress The newly created PersonAddress model instance.
     */
    public function create(int $personId, array $data): PersonAddress
    {
        $data['person_id'] = $personId;
        return PersonAddress::create($data);
    }

    /**
     * Update an existing address for a person.
     *
     * @param PersonAddress $address The address to update.
     * @param array $data The updated address data including:
     * - ['street_address'] (string) The updated street address.
     * - ['city'] (string) The updated city name.
     * - ['state'] (string) The updated state or province.
     * - ['postal_code'] (string) The updated postal or ZIP code.
     * - ['country'] (string) The updated country name.
     * - ['type'] (string) The updated type of address.
     * - ['is_primary'] (bool) Whether this should be the primary address.
     *
     * @return PersonAddress The updated PersonAddress model instance.
     */
    public function update(PersonAddress $address, array $data): PersonAddress
    {
        $address->update($data);
        return $address;
    }

    /**
     * Delete an address from a person.
     *
     * @param PersonAddress $address The address to delete.
     * @return bool True if deletion was successful, false otherwise.
     */
    public function delete(PersonAddress $address): bool
    {
        return $address->delete();
    }


    public function setPrimary(PersonAddress $address): PersonAddress
    {
        $address->is_primary = true;
        $address->save();

        // Set all other addresses to not primary
        PersonAddress::where('person_id', $address->person_id)
            ->where('id', '!=', $address->id)
            ->update(['is_primary' => false]);

        return $address;
    }
}
