<?php

namespace App\Repositories\People;

use App\Models\Person;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface PeopleRepositoryInterface
{
    /**
     * Retrieves all records with pagination.
     *
     * @param int $perPage The number of items per page
     * @param int $page The page number to retrieve
     * @param array $filters The filters to apply
     * @return LengthAwarePaginator Paginated list of records
     */
    public function getAll(int $perPage = 15, int $page = 1, array $filters = []): LengthAwarePaginator;

    /**
     * Find a record by its ID.
     *
     * @param int $id The ID of the record to find
     * @return Person The person with the given ID
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When record not found
     */
    public function findById(int $id): Person;

    /**
     * Get navigation context for a person within filtered results.
     *
     * @param int $id The ID of the current person
     * @param array $filters The same filters used in getAll method
     * @return array Navigation context containing position, total, next_id, previous_id
     */
    public function getNavigationContext(int $id, array $filters = []): array;

    /**
     * Creates a new record with the given data.
     *
     * @param array $data The data to use when creating the record
     * @return Person The newly created record
     */
    public function create(array $data): Person;

    /**
     * Update a record with the given ID.
     *
     * @param int $id The ID of the record to update
     * @param array $data The data to update the record with
     * @return Person The updated record
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When record not found
     */
    public function update(int $id, array $data): Person;

    /**
     * Deletes a record with the given ID.
     *
     * @param int $id The ID of the record to delete
     * @return int The number of deleted records
     */
    public function delete(int $id): int;

    /**
     * Finds a person by email address.
     *
     * @param string $email The email address to search for
     * @return Person|null The person with the given email address, or null if none found
     */
    public function findByEmail(string $email): ?Person;

    /**
     * Delete all records with optional filters.
     *
     * @param array $filters Filters to apply before deletion
     * @return int Number of deleted records
     */
    public function deleteAll(array $filters = []): int;

    /**
     * Delete all records except those with specified IDs, with optional filters.
     *
     * @param array $ids IDs to exclude from deletion
     * @param array $filters Filters to apply before deletion
     * @return int Number of deleted records
     */
    public function deleteAllExcept(array $ids, array $filters = []): int;

    /**
     * Delete multiple records by their IDs.
     *
     * @param array $ids IDs of records to delete
     * @return int Number of deleted records
     */
    public function deleteSome(array $ids): int;

    /**
     * Import multiple people records at once.
     *
     * @param array $people Array of people data to import
     * @return Collection Collection of created Person models
     */
    public function import(array $people): Collection;

    /**
     * Attach multiple collaborators to a person.
     *
     * @param Person $person The person to attach collaborators to
     * @param array $collaboratorIds Array of collaborator IDs to attach
     * @return void
     */
    public function attachCollaborators(Person $person, array $collaboratorIds): void;

    /**
     * Attach a single collaborator to a person.
     *
     * @param Person $person The person to attach the collaborator to
     * @param int $collaboratorId ID of the collaborator to attach
     * @return void
     */
    public function attachCollaborator(Person $person, int $collaboratorId): void;

    /**
     * Detach a single collaborator from a person.
     *
     * @param Person $person The person to detach the collaborator from
     * @param int $collaboratorId ID of the collaborator to detach
     * @return void
     */
    public function detachCollaborator(Person $person, int $collaboratorId): void;

    /**
     * Set multiple custom field values for a person.
     *
     * @param int $personId The person ID
     * @param array $customFieldValues Array of custom field ID => value pairs
     * @return void
     */
    public function setCustomFieldValues(int $personId, array $customFieldValues): void;
}
