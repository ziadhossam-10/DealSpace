<?php

namespace App\Services\People;

use App\Models\PersonEmail;
use App\Repositories\People\PeopleRepositoryInterface;
use App\Repositories\People\EmailsRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

class EmailService implements EmailServiceInterface
{
    protected $peopleRepository;
    protected $emailRepository;

    public function __construct(
        PeopleRepositoryInterface $peopleRepository,
        EmailsRepositoryInterface $emailRepository
    ) {
        $this->peopleRepository = $peopleRepository;
        $this->emailRepository = $emailRepository;
    }

    /**
     * Get all emails for a specific person.
     *
     * @param int $personId The ID of the person
     * @return Collection Collection of emails
     * @throws ModelNotFoundException
     */
    public function getAll(int $personId): Collection
    {
        // Verify the person exists
        $this->peopleRepository->findById($personId);

        // Get all emails for this person
        return $this->emailRepository->all($personId);
    }

    /**
     * Get a specific email for a person.
     *
     * @param int $personId The ID of the person
     * @param int $emailId The ID of the email
     * @return PersonEmail
     * @throws ModelNotFoundException
     */
    public function findById(int $personId, int $emailId): PersonEmail
    {
        $email = $this->emailRepository->find($emailId, $personId);

        if (!$email) {
            throw new ModelNotFoundException('Email not found for this person');
        }

        return $email;
    }

    /**
     * Get a specific email by its address for a person.
     *
     * @param string $emailAddress The email address to search for
     * @return PersonEmail|null
     */
    public function findByEmailAddress(string $emailAddress): ?PersonEmail
    {
        // Find the email by address
        return $this->emailRepository->findByEmailAddress($emailAddress);
    }

    /**
     * Add a new email to a person.
     *
     * @param int $personId The ID of the person
     * @param array $data The email data including:
     * - 'street_email' (string)
     * - 'city' (string)
     * - 'state' (string)
     * - 'postal_code' (string)
     * - ['country'] (string)
     * - ['type'] (string)
     * - ['is_primary'] (bool)
     * @return PersonEmail
     */
    public function create(int $personId, array $data): PersonEmail
    {
        $person = $this->peopleRepository->findById($personId);

        if (!$person) {
            throw new ModelNotFoundException('Person not found');
        }

        return $this->emailRepository->create($personId, $data);
    }

    /**
     * Update an existing email for a person.
     *
     * @param int $personId
     * @param int $emailId
     * @param array $data
     * @return PersonEmail
     * @throws ModelNotFoundException
     */
    public function update(int $personId, int $emailId, array $data): PersonEmail
    {
        $email = $this->emailRepository->find($emailId, $personId);
        if (!$email) {
            throw new ModelNotFoundException('Email not found for this person');
        }

        return $this->emailRepository->update($email, $data);
    }

    /**
     * Delete an email for a person.
     *
     * @param int $personId
     * @param int $emailId
     * @return bool
     * @throws ModelNotFoundException
     */
    public function delete(int $personId, int $emailId): bool
    {
        $email = $this->emailRepository->find($emailId, $personId);
        if (!$email) {
            throw new ModelNotFoundException('Email not found for this person');
        }
        return $this->emailRepository->delete($email);
    }

    /**
     * Set an email as primary for a person.
     *
     * @param int $personId
     * @param int $emailId
     * @return PersonEmail
     * @throws ModelNotFoundException
     */
    public function setPrimary(int $personId, int $emailId): PersonEmail
    {
        $email = $this->emailRepository->find($emailId, $personId);
        if (!$email) {
            throw new ModelNotFoundException('Email not found for this person');
        }
        return $this->emailRepository->setPrimary($email);
    }
}
