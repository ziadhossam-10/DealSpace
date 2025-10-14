<?php
namespace App\Repositories\People;

use App\Models\PersonTag;
use Illuminate\Support\Collection;

interface TagsRepositoryInterface
{
    /**
     * Get all tags for a specific person.
     *
     * @param int $personId The ID of the person.
     * @return Collection Collection of PersonTag objects.
     */
    public function all(int $personId): Collection;

    /**
     * Find an tag by its ID and the ID of the person it belongs to.
     *
     * @param int $tagId The ID of the tag to find.
     * @param int $personId The ID of the person the tag belongs to.
     * @return PersonTag|null The found tag or null if not found.
     */
    public function find(int $tagId, int $personId): ?PersonTag;

    /**
     * Create a new tag record for a specific person.
     *
     * @param int $personId The ID of the person to associate the tag with.
     * @param array $data The data for the new tag.
     * @return PersonTag The newly created PersonTag model instance.
     */
    public function create(int $personId, array $data): PersonTag;

    /**
     * Update an existing tag for a person.
     *
     * @param PersonTag $tag The tag to update.
     * @param array $data The updated tag data.
     * @return PersonTag The updated PersonTag model instance.
     */
    public function update(PersonTag $tag, array $data): PersonTag;

    /**
     * Delete an tag from a person.
     *
     * @param PersonTag $tag The tag to delete.
     * @return bool True if deletion was successful, false otherwise.
     */
    public function delete(PersonTag $tag): bool;
}