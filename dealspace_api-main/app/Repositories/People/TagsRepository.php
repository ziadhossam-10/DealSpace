<?php
namespace App\Repositories\People;

use App\Models\PersonTag;
use Illuminate\Support\Collection;

class TagsRepository implements TagsRepositoryInterface
{
    /**
     * Get all tags for a specific person.
     *
     * @param int $personId The ID of the person.
     * @return Collection Collection of PersonTag objects.
     */
    public function all(int $personId): Collection
    {
        return PersonTag::where('person_id', $personId)->get();
    }

    /**
     * Find an tag by its ID and the ID of the person it belongs to.
     *
     * @param int $tagId The ID of the tag to find.
     * @param int $personId The ID of the person the tag belongs to.
     *
     * @return PersonTag|null The found tag or null if not found.
     */
    public function find(int $tagId, int $personId): ?PersonTag
    {
        return PersonTag::where('person_id', $personId)->find($tagId);
    }

    /**
     * Create a new tag record for a specific person.
     *
     * @param int $personId The ID of the person to associate the tag with.
     * @param array $data The data for the new tag, including:
     * ['name'] (string) The name of the tag.
     * ['description'] (string) The description of the tag.
     * ['color'] (string) The color associated with the tag.
     * @return PersonTag The newly created PersonTag model instance.
     */
    public function create(int $personId, array $data): PersonTag
    {
        $data['person_id'] = $personId;
        return PersonTag::create($data);
    }

    /**
     * Update an existing tag for a person.
     *
     * @param PersonTag $tag The tag to update.
     * @param array $data The updated tag data including:
     * ['name'] (string) The name of the tag.
     * ['description'] (string) The description of the tag.
     * ['color'] (string) The color associated with the tag.
     *
     * @return PersonTag The updated PersonTag model instance.
     */
    public function update(PersonTag $tag, array $data): PersonTag
    {
        $tag->update($data);
        return $tag;
    }

    /**
     * Delete an tag from a person.
     *
     * @param PersonTag $tag The tag to delete.
     * @return bool True if deletion was successful, false otherwise.
     */
    public function delete(PersonTag $tag): bool
    {
        return $tag->delete();
    }

}
