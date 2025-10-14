<?php
namespace App\Services\People;

use App\Models\PersonTag;
use Illuminate\Support\Collection;

interface TagServiceInterface
{
    /**
     * Get all tags for a specific person.
     *
     * @param int $personId The ID of the person
     * @return Collection Collection of tags
     */
    public function getAll(int $personId): Collection;

    /**
     * Get a specific tag for a person.
     *
     * @param int $personId The ID of the person
     * @param int $tagId The ID of the tag
     * @return PersonTag
     */
    public function findById(int $personId, int $tagId): PersonTag;

    /**
     * Add a new tag to a person.
     *
     * @param int $personId The ID of the person
     * @param array $data The tag data
     * @return PersonTag
     */
    public function create(int $personId, array $data): PersonTag;

    /**
     * Update an existing tag for a person.
     *
     * @param int $personId The ID of the person
     * @param int $tagId The ID of the tag
     * @param array $data The updated tag data
     * @return PersonTag
     */
    public function update(int $personId, int $tagId, array $data): PersonTag;

    /**
     * Delete an tag for a person.
     *
     * @param int $personId The ID of the person
     * @param int $tagId The ID of the tag
     * @return bool
     */
    public function delete(int $personId, int $tagId): bool;
}
