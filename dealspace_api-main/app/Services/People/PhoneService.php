<?php
namespace App\Services\People;

use App\Models\PersonPhone;
use App\Repositories\People\PeopleRepositoryInterface;
use App\Repositories\People\PhonesRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

class PhoneService implements PhoneServiceInterface
{
    protected $peopleRepository;
    protected $phoneRepository;

    public function __construct(
        PeopleRepositoryInterface $peopleRepository,
        PhonesRepositoryInterface $phoneRepository
    ) {
        $this->peopleRepository = $peopleRepository;
        $this->phoneRepository = $phoneRepository;
    }

    /**
     * Get all phones for a specific person.
     *
     * @param int $personId The ID of the person
     * @return Collection Collection of phones
     * @throws ModelNotFoundException
     */
    public function getAll(int $personId): Collection
    {
        // Verify the person exists
        $this->peopleRepository->findById($personId);

        // Get all phones for this person
        return $this->phoneRepository->all($personId);
    }

    /**
     * Get a specific phone for a person.
     *
     * @param int $personId The ID of the person
     * @param int $phoneId The ID of the phone
     * @return PersonPhone
     * @throws ModelNotFoundException
     */
    public function findById(int $personId, int $phoneId): PersonPhone
    {
        $phone = $this->phoneRepository->find($phoneId, $personId);

        if (!$phone) {
            throw new ModelNotFoundException('Phone not found for this person');
        }

        return $phone;
    }

    /**
     * Add a new phone to a person.
     *
     * @param int $personId The ID of the person
     * @param array $data The phone data including:
     * - 'street_phone' (string)
     * - 'city' (string)
     * - 'state' (string)
     * - 'postal_code' (string)
     * - ['country'] (string)
     * - ['type'] (string)
     * - ['is_primary'] (bool)
     * @return PersonPhone
     */
    public function create(int $personId, array $data): PersonPhone
    {
        $person = $this->peopleRepository->findById($personId);

        if (!$person) {
            throw new ModelNotFoundException('Person not found');
        }

        return $this->phoneRepository->create($personId, $data);
    }

    /**
     * Update an existing phone for a person.
     *
     * @param int $personId
     * @param int $phoneId
     * @param array $data
     * @return PersonPhone
     * @throws ModelNotFoundException
     */
    public function update(int $personId, int $phoneId, array $data): PersonPhone
    {
        $phone = $this->phoneRepository->find($phoneId, $personId);
        if (!$phone) {
            throw new ModelNotFoundException('Phone not found for this person');
        }

        return $this->phoneRepository->update($phone, $data);
    }

    /**
     * Delete an phone for a person.
     *
     * @param int $personId
     * @param int $phoneId
     * @return bool
     * @throws ModelNotFoundException
     */
    public function delete(int $personId, int $phoneId): bool
    {
        $phone = $this->phoneRepository->find($phoneId, $personId);
        if (!$phone) {
            throw new ModelNotFoundException('Phone not found for this person');
        }
        return $this->phoneRepository->delete($phone);
    }

    /**
     * Set an phone as primary for a person.
     *
     * @param int $personId
     * @param int $phoneId
     * @return PersonPhone
     * @throws ModelNotFoundException
     */
    public function setPrimary(int $personId, int $phoneId): PersonPhone
    {
        $phone = $this->phoneRepository->find($phoneId, $personId);
        if (!$phone) {
            throw new ModelNotFoundException('Phone not found for this person');
        }
        return $this->phoneRepository->setPrimary($phone);
    }
}
