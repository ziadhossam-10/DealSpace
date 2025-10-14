<?php

namespace App\Services\People;

use App\Models\PersonFile;
use App\Repositories\People\PeopleRepositoryInterface;
use App\Repositories\People\FilesRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

class FileService implements FileServiceInterface
{
    protected $peopleRepository;
    protected $fileRepository;

    public function __construct(
        PeopleRepositoryInterface $peopleRepository,
        FilesRepositoryInterface $fileRepository
    ) {
        $this->peopleRepository = $peopleRepository;
        $this->fileRepository = $fileRepository;
    }

    /**
     * Get all files for a specific person.
     *
     * @param int $personId The ID of the person
     * @return Collection Collection of files
     * @throws ModelNotFoundException
     */
    public function getAll(int $personId): Collection
    {
        // Verify the person exists
        $this->peopleRepository->findById($personId);

        // Get all files for this person
        return $this->fileRepository->all($personId);
    }

    /**
     * Get a specific file for a person.
     *
     * @param int $personId The ID of the person
     * @param int $fileId The ID of the file
     * @return PersonFile
     * @throws ModelNotFoundException
     */
    public function findById(int $personId, int $fileId): PersonFile
    {
        $file = $this->fileRepository->find($fileId, $personId);

        if (!$file) {
            throw new ModelNotFoundException('File not found for this person');
        }

        return $file;
    }

    /**
     * Add a new file to a person.
     *
     * @param int $personId The ID of the person
     * @param array $data The file data including:
     * - 'name' (string) The file name
     * - 'path' (string) The file path
     * - ['type'] (string) The file type or category
     * - ['size'] (int) The file size in bytes
     * - ['mime_type'] (string) The MIME type
     * - ['is_primary'] (bool) Whether this is the primary file
     * - ['description'] (string) Optional description
     * @return PersonFile
     */
    public function create(int $personId, array $data): PersonFile
    {
        $person = $this->peopleRepository->findById($personId);

        if (!$person) {
            throw new ModelNotFoundException('Person not found');
        }

        return $this->fileRepository->create($personId, $data);
    }

    /**
     * Update an existing file for a person.
     *
     * @param int $personId
     * @param int $fileId
     * @param array $data
     * @return PersonFile
     * @throws ModelNotFoundException
     */
    public function update(int $personId, int $fileId, array $data): PersonFile
    {
        $file = $this->fileRepository->find($fileId, $personId);

        if (!$file) {
            throw new ModelNotFoundException('File not found for this person');
        }

        return $this->fileRepository->update($file, $data);
    }

    /**
     * Delete a file for a person.
     *
     * @param int $personId
     * @param int $fileId
     * @return bool
     * @throws ModelNotFoundException
     */
    public function delete(int $personId, int $fileId): bool
    {
        $file = $this->fileRepository->find($fileId, $personId);

        if (!$file) {
            throw new ModelNotFoundException('File not found for this person');
        }

        return $this->fileRepository->delete($file);
    }
}
