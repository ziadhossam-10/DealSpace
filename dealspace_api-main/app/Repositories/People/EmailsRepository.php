<?php

namespace App\Repositories\People;

use App\Models\PersonEmail;
use Illuminate\Support\Collection;

class EmailsRepository implements EmailsRepositoryInterface
{
    /**
     * Get all emails for a specific person.
     *
     * @param int $personId The ID of the person.
     * @return Collection Collection of PersonEmail objects.
     */
    public function all(int $personId): Collection
    {
        return PersonEmail::where('person_id', $personId)->get();
    }

    /**
     * Find an email by its ID and the ID of the person it belongs to.
     *
     * @param int $emailId The ID of the email to find.
     * @param int $personId The ID of the person the email belongs to.
     *
     * @return PersonEmail|null The found email or null if not found.
     */
    public function find(int $emailId, int $personId): ?PersonEmail
    {
        return PersonEmail::where('person_id', $personId)->find($emailId);
    }

    /**
     * Find an email by its address for a specific person.
     *
     * @param string $emailAddress The email address to search for.
     * @return PersonEmail|null The found email or null if not found.
     */
    public function findByEmailAddress(string $emailAddress): ?PersonEmail
    {
        return PersonEmail::where('value', $emailAddress)
            ->first();
    }

    /**
     * Create a new email record for a specific person.
     *
     * @param int $personId The ID of the person to associate the email with.
     * @param array $data The data for the new email, including:
     * ['value'] (string) The email address.
     * ['type'] (string) The type of email (home,work,other).
     * ['is_primary'] (bool) Whether this is the primary email.
     * ['status'] (string) The status of the email (Valid,Invalid,Not Validated).
     * @return PersonEmail The newly created PersonEmail model instance.
     */
    public function create(int $personId, array $data): PersonEmail
    {
        $data['person_id'] = $personId;
        return PersonEmail::create($data);
    }

    /**
     * Update an existing email for a person.
     *
     * @param PersonEmail $email The email to update.
     * @param array $data The updated email data including:
     * ['value'] (string) The email address.
     * ['type'] (string) The type of email (home,work,other).
     * ['is_primary'] (bool) Whether this is the primary email.
     * ['status'] (string) The status of the email (Valid,Invalid,Not Validated).
     * @return PersonEmail The updated PersonEmail model instance.
     */
    public function update(PersonEmail $email, array $data): PersonEmail
    {
        $email->update($data);
        return $email;
    }

    /**
     * Delete an email from a person.
     *
     * @param PersonEmail $email The email to delete.
     * @return bool True if deletion was successful, false otherwise.
     */
    public function delete(PersonEmail $email): bool
    {
        return $email->delete();
    }


    public function setPrimary(PersonEmail $email): PersonEmail
    {
        $email->is_primary = true;
        $email->save();

        // Set all other emails to not primary
        PersonEmail::where('person_id', $email->person_id)
            ->where('id', '!=', $email->id)
            ->update(['is_primary' => false]);

        return $email;
    }
}
