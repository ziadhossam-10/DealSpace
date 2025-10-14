<?php

namespace App\Services\People;

use App\Models\PersonEmail;
use Illuminate\Support\Collection;

interface EmailServiceInterface
{
    /**
     * Get all emails for a specific person.
     *
     * @param int $personId The ID of the person
     * @return Collection Collection of emails
     */
    public function getAll(int $personId): Collection;

    /**
     * Get a specific email for a person.
     *
     * @param int $personId The ID of the person
     * @param int $emailId The ID of the email
     * @return PersonEmail
     */
    public function findById(int $personId, int $emailId): PersonEmail;

    /**
     * Get a specific email by its address for a person.
     *
     * @param string $emailAddress The email address to search for
     * @return PersonEmail|null
     */
    public function findByEmailAddress(string $emailAddress): ?PersonEmail;

    /**
     * Add a new email to a person.
     *
     * @param int $personId The ID of the person
     * @param array $data The email data
     * @return PersonEmail
     */
    public function create(int $personId, array $data): PersonEmail;

    /**
     * Update an existing email for a person.
     *
     * @param int $personId The ID of the person
     * @param int $emailId The ID of the email
     * @param array $data The updated email data
     * @return PersonEmail
     */
    public function update(int $personId, int $emailId, array $data): PersonEmail;

    /**
     * Delete an email for a person.
     *
     * @param int $personId The ID of the person
     * @param int $emailId The ID of the email
     * @return bool
     */
    public function delete(int $personId, int $emailId): bool;

    /**
     * Set an email as primary for a person.
     *
     * @param int $personId The ID of the person
     * @param int $emailId The ID of the email to set as primary
     * @return PersonEmail
     */
    public function setPrimary(int $personId, int $emailId): PersonEmail;
}
