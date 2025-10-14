<?php

namespace App\Services\People;

use App\Models\PersonFile;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

interface FileServiceInterface
{
    /**
     * Get all files for a specific person.
     *
     * @param int $personId The ID of the person
     * @return Collection Collection of files
     * @throws ModelNotFoundException
     */
    public function getAll(int $personId): Collection;

    /**
     * Get a specific file for a person.
     *
     * @param int $personId The ID of the person
     * @param int $fileId The ID of the file
     * @return PersonFile
     * @throws ModelNotFoundException
     */
    public function findById(int $personId, int $fileId): PersonFile;

    /**
     * Add a new file to a person.
     *
     * @param int $personId The ID of the person
     * @param array $data The file data
     * @return PersonFile
     * @throws ModelNotFoundException
     */
    public function create(int $personId, array $data): PersonFile;

    /**
     * Update an existing file for a person.
     *
     * @param int $personId
     * @param int $fileId
     * @param array $data
     * @return PersonFile
     * @throws ModelNotFoundException
     */
    public function update(int $personId, int $fileId, array $data): PersonFile;

    /**
     * Delete a file for a person.
     *
     * @param int $personId
     * @param int $fileId
     * @return bool
     * @throws ModelNotFoundException
     */
    public function delete(int $personId, int $fileId): bool;
}
