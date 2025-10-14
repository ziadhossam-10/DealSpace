<?php

namespace App\Repositories\People;

use App\Models\PersonEmail;
use Illuminate\Support\Collection;

interface EmailsRepositoryInterface
{
    /**
     * Get all emails for a specific person.
     *
     * @param int $personId The ID of the person.
     * @return Collection Collection of PersonEmail objects.
     */
    public function all(int $personId): Collection;

    /**
     * Find an email by its ID and the ID of the person it belongs to.
     *
     * @param int $emailId The ID of the email to find.
     * @param int $personId The ID of the person the email belongs to.
     * @return PersonEmail|null The found email or null if not found.
     */
    public function find(int $emailId, int $personId): ?PersonEmail;

    /**
     * Find an email by its address for a specific person.
     *
     * @param string $emailAddress The email address to search for.
     * @return PersonEmail|null The found email or null if not found.
     */
    public function findByEmailAddress(string $emailAddress): ?PersonEmail;

    /**
     * Create a new email record for a specific person.
     *
     * @param int $personId The ID of the person to associate the email with.
     * @param array $data The data for the new email.
     * @return PersonEmail The newly created PersonEmail model instance.
     */
    public function create(int $personId, array $data): PersonEmail;

    /**
     * Update an existing email for a person.
     *
     * @param PersonEmail $email The email to update.
     * @param array $data The updated email data.
     * @return PersonEmail The updated PersonEmail model instance.
     */
    public function update(PersonEmail $email, array $data): PersonEmail;

    /**
     * Delete an email from a person.
     *
     * @param PersonEmail $email The email to delete.
     * @return bool True if deletion was successful, false otherwise.
     */
    public function delete(PersonEmail $email): bool;

    /**
     * Reset all primary emails for a person to false, except for the specified email.
     *
     * @param int $personId The ID of the person to reset the primary email for.
     * @param int|null $exceptEmailId The ID of an email to exclude from resetting.
     * @return PersonEmail
     */
    public function setPrimary(PersonEmail $email): PersonEmail;
}
