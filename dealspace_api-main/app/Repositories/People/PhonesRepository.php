<?php

namespace App\Repositories\People;

use App\Models\PersonPhone;
use Illuminate\Support\Collection;

class PhonesRepository implements PhonesRepositoryInterface
{
    /**
     * Get all phones for a specific person.
     *
     * @param int $personId The ID of the person.
     * @return Collection Collection of PersonPhone objects.
     */
    public function all(int $personId): Collection
    {
        return PersonPhone::where('person_id', $personId)->get();
    }

    /**
     * Find an phone by its ID and the ID of the person it belongs to.
     *
     * @param int $phoneId The ID of the phone to find.
     * @param int $personId The ID of the person the phone belongs to.
     *
     * @return PersonPhone|null The found phone or null if not found.
     */
    public function find(int $phoneId, int $personId): ?PersonPhone
    {
        return PersonPhone::where('person_id', $personId)->find($phoneId);
    }

    /**
     * Create a new phone record for a specific person.
     *
     * @param int $personId The ID of the person to associate the phone with.
     * @param array $data The data for the new phone, including:
     * ['value'] (string) The phone address.
     * ['type'] (string) The type of phone (home,work,other).
     * ['is_primary'] (bool) Whether this is the primary phone.
     * ['status'] (string) The status of the phone (Valid,Invalid,Not Validated).
     *
     * @return PersonPhone The newly created PersonPhone model instance.
     */
    public function create(int $personId, array $data): PersonPhone
    {
        $data['person_id'] = $personId;
        return PersonPhone::create($data);
    }

    /**
     * Update an existing phone for a person.
     *
     * @param PersonPhone $phone The phone to update.
     * @param array $data The updated phone data including:
     * ['value'] (string) The phone address.
     * ['type'] (string) The type of phone (home,work,other).
     * ['is_primary'] (bool) Whether this is the primary phone.
     * ['status'] (string) The status of the phone (Valid,Invalid,Not Validated).
     *
     * @return PersonPhone The updated PersonPhone model instance.
     */
    public function update(PersonPhone $phone, array $data): PersonPhone
    {
        $phone->update($data);
        return $phone;
    }

    /**
     * Delete an phone from a person.
     *
     * @param PersonPhone $phone The phone to delete.
     * @return bool True if deletion was successful, false otherwise.
     */
    public function delete(PersonPhone $phone): bool
    {
        return $phone->delete();
    }


    public function setPrimary(PersonPhone $phone): PersonPhone
    {
        $phone->is_primary = true;
        $phone->save();

        // Set all other phones to not primary
        PersonPhone::where('person_id', $phone->person_id)
            ->where('id', '!=', $phone->id)
            ->update(['is_primary' => false]);

        return $phone;
    }
}
