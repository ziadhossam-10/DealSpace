<?php

namespace App\Repositories\People;

use App\Models\PersonFile;
use Illuminate\Support\Collection;

class FilesRepository implements FilesRepositoryInterface
{
    /**
     * Get all files for a specific person.
     *
     * @param int $personId The ID of the person.
     * @return Collection Collection of PersonFile objects.
     */
    public function all(int $personId): Collection
    {
        return PersonFile::where('person_id', $personId)->get();
    }

    /**
     * Find a file by its ID and the ID of the person it belongs to.
     *
     * @param int $fileId The ID of the file to find.
     * @param int $personId The ID of the person the file belongs to.
     *
     * @return PersonFile|null The found file or null if not found.
     */
    public function find(int $fileId, int $personId): ?PersonFile
    {
        return PersonFile::where('person_id', $personId)->find($fileId);
    }

    /**
     * Create a new file record for a specific person.
     *
     * @param int $personId The ID of the person to associate the file with.
     * @param array $data The data for the new file, including:
     * ['name'] (string) The file name.
     * ['path'] (string) The file path.
     * ['type'] (string) The type or category of the file.
     * ['size'] (int) The file size in bytes.
     * ['mime_type'] (string) The MIME type of the file.
     * ['is_primary'] (bool) Whether this is the primary file.
     * ['description'] (string) Optional description of the file.
     *
     * @return PersonFile The newly created PersonFile model instance.
     */
    public function create(int $personId, array $data): PersonFile
    {
        $data['person_id'] = $personId;
        return PersonFile::create($data);
    }

    /**
     * Update an existing file for a person.
     *
     * @param PersonFile $file The file to update.
     * @param array $data The updated file data including:
     * ['name'] (string) The file name.
     * ['path'] (string) The file path.
     * ['type'] (string) The type or category of the file.
     * ['size'] (int) The file size in bytes.
     * ['mime_type'] (string) The MIME type of the file.
     * ['is_primary'] (bool) Whether this is the primary file.
     * ['description'] (string) Optional description of the file.
     *
     * @return PersonFile The updated PersonFile model instance.
     */
    public function update(PersonFile $file, array $data): PersonFile
    {
        $file->update($data);
        return $file;
    }

    /**
     * Delete a file from a person.
     *
     * @param PersonFile $file The file to delete.
     * @return bool True if deletion was successful, false otherwise.
     */
    public function delete(PersonFile $file): bool
    {
        return $file->delete();
    }
}
