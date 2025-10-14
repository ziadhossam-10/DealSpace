<?php
namespace App\Services\People;

use App\Models\PersonAddress;
use App\Repositories\People\PeopleRepositoryInterface;
use App\Repositories\People\AddressesRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

class AddressService implements AddressServiceInterface
{
    protected $peopleRepository;
    protected $addressRepository;

    public function __construct(
        PeopleRepositoryInterface $peopleRepository,
        AddressesRepositoryInterface $addressRepository
    ) {
        $this->peopleRepository = $peopleRepository;
        $this->addressRepository = $addressRepository;
    }

    /**
     * Get all addresses for a specific person.
     *
     * @param int $personId The ID of the person
     * @return Collection Collection of addresses
     * @throws ModelNotFoundException
     */
    public function getAll(int $personId): Collection
    {
        // Verify the person exists
        $this->peopleRepository->findById($personId);

        // Get all addresses for this person
        return $this->addressRepository->all($personId);
    }

    /**
     * Get a specific address for a person.
     *
     * @param int $personId The ID of the person
     * @param int $addressId The ID of the address
     * @return PersonAddress
     * @throws ModelNotFoundException
     */
    public function findById(int $personId, int $addressId): PersonAddress
    {
        $address = $this->addressRepository->find($addressId, $personId);

        if (!$address) {
            throw new ModelNotFoundException('Address not found for this person');
        }

        return $address;
    }

    /**
     * Add a new address to a person.
     *
     * @param int $personId The ID of the person
     * @param array $data The address data including:
     * - 'street_address' (string)
     * - 'city' (string)
     * - 'state' (string)
     * - 'postal_code' (string)
     * - ['country'] (string)
     * - ['type'] (string)
     * - ['is_primary'] (bool)
     * @return PersonAddress
     */
    public function create(int $personId, array $data): PersonAddress
    {
        $person = $this->peopleRepository->findById($personId);

        if (!$person) {
            throw new ModelNotFoundException('Person not found');
        }

        return $this->addressRepository->create($personId, $data);
    }

    /**
     * Update an existing address for a person.
     *
     * @param int $personId
     * @param int $addressId
     * @param array $data
     * @return PersonAddress
     * @throws ModelNotFoundException
     */
    public function update(int $personId, int $addressId, array $data): PersonAddress
    {
        $address = $this->addressRepository->find($addressId, $personId);
        if (!$address) {
            throw new ModelNotFoundException('Address not found for this person');
        }

        return $this->addressRepository->update($address, $data);
    }

    /**
     * Delete an address for a person.
     *
     * @param int $personId
     * @param int $addressId
     * @return bool
     * @throws ModelNotFoundException
     */
    public function delete(int $personId, int $addressId): bool
    {
        $address = $this->addressRepository->find($addressId, $personId);
        if (!$address) {
            throw new ModelNotFoundException('Address not found for this person');
        }
        return $this->addressRepository->delete($address);
    }

    /**
     * Set an address as primary for a person.
     *
     * @param int $personId
     * @param int $addressId
     * @return PersonAddress
     * @throws ModelNotFoundException
     */
    public function setPrimary(int $personId, int $addressId): PersonAddress
    {
        $address = $this->addressRepository->find($addressId, $personId);
        if (!$address) {
            throw new ModelNotFoundException('Address not found for this person');
        }
        return $this->addressRepository->setPrimary($address);
    }
}
