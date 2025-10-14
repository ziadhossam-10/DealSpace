<?php

namespace App\Services\People;

use App\Models\Person;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

interface PersonServiceInterface
{
    /**
     * Retrieves all people with pagination.
     *
     * @param int $perPage The number of items per page
     * @param int $page The page number to retrieve
     * @param array $filters The filters to apply
     * @return LengthAwarePaginator Paginated list of people
     */
    public function getAll(int $perPage = 15, int $page = 1, array $filters = []): LengthAwarePaginator;

    /**
     * Retrieve all people for bulk operations with optional filters.
     *
     * This method returns a collection of Person models, suitable for bulk actions
     * like bulkDelete or bulkExport. It does not paginate the results.
     *
     * @param array $filters Filters to apply (e.g., stage_id, team_id, user_ids, search)
     * @return \Illuminate\Support\Collection Collection of Person models
     */
    public function getAllForBulk(array $filters = []): \Illuminate\Support\Collection;


    /**
     * Finds a person by its ID.
     *
     * @param int $id The ID of the person to find
     * @return Person The person with the given ID
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When person not found
     */
    public function findById(int $id): Person;

    /**
     * Finds a person by ID with navigation context based on filters.
     *
     * @param int $id The ID of the person to find
     * @param array $filters The same filters used in getAll method
     * @return array Array containing the person and navigation data
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When person not found
     */
    public function findByIdWithNavigation(int $id, array $filters = []): array;

    /**
     * Creates a new person with associated data.
     *
     * @param array $data The person data including associated entities
     * @return Person The newly created person with all associated data loaded
     */
    public function create(array $data): Person;

    /**
     * Updates an existing person's information.
     *
     * @param int $id The ID of the person to update
     * @param array $data The data to update the person with
     * @return Person The updated person with all associated data loaded
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When person not found
     */
    public function update(int $id, array $data): Person;

    /**
     * Delete a person by ID.
     *
     * @param int $id The ID of the person to delete
     * @return int The number of deleted records
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When person not found
     */
    public function delete(int $id): int;

    /**
     * Delete multiple people in a single transaction.
     *
     * @param array $params Parameters to control the deletion operation
     * @param array $filters Filters to apply before deletion
     * @return int Number of deleted records
     */
    public function bulkDelete(array $params, array $filters = []): int;

    /**
     * Finds a person by their email address.
     *
     * @param string $email The email address to search for
     * @return Person|null The person with the given email address, or null if none found
     */
    public function findByEmail(string $email): ?Person;

    /**
     * Attach a collaborator to a person.
     *
     * @param int $personId ID of the person
     * @param int $collaboratorId ID of the collaborator to attach
     * @return void
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When person not found
     */
    public function attachCollaborator(int $personId, int $collaboratorId): void;

    /**
     * Detach a collaborator from a person.
     *
     * @param int $personId ID of the person
     * @param int $collaboratorId ID of the collaborator to detach
     * @return void
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When person not found
     */
    public function detachCollaborator(int $personId, int $collaboratorId): void;

    /**
     * Attach multiple collaborators to a person.
     *
     * @param int $personId ID of the person
     * @param array $collaboratorIds Array of collaborator IDs to attach
     * @return void
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When person not found
     */
    public function attachCollaborators(int $personId, array $collaboratorIds): void;

    /**
     * Import people from an Excel file.
     *
     * @param UploadedFile $file Excel file to import
     * @return array Results of the import operation
     */
    public function importExcel(UploadedFile $file): array;

    /**
     * Download Excel template for people import.
     *
     * @return BinaryFileResponse Excel file for download
     */
    public function downloadExcelTemplate(): BinaryFileResponse;

    /**
     * Export people to Excel based on provided parameters.
     *
     * @param array $params Parameters to control the export operation
     * @return BinaryFileResponse Excel file for download
     */
    public function bulkExport(array $params): BinaryFileResponse;

    /**
     * Set custom field values for a person.
     *
     * @param int $personId
     * @param array $customFieldData
     * @return void
     */
    public function setCustomFieldValues(int $personId, array $customFieldData): void;
}
