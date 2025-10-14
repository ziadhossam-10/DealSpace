<?php
namespace App\Services\People;

use App\Models\PersonTag;
use App\Repositories\People\PeopleRepositoryInterface;
use App\Repositories\People\TagsRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

class TagService implements TagServiceInterface
{
    protected $peopleRepository;
    protected $tagRepository;

    public function __construct(
        PeopleRepositoryInterface $peopleRepository,
        TagsRepositoryInterface $tagRepository
    ) {
        $this->peopleRepository = $peopleRepository;
        $this->tagRepository = $tagRepository;
    }

    /**
     * Get all tags for a specific person.
     *
     * @param int $personId The ID of the person
     * @return Collection Collection of tags
     * @throws ModelNotFoundException
     */
    public function getAll(int $personId): Collection
    {
        // Verify the person exists
        $this->peopleRepository->findById($personId);

        // Get all tags for this person
        return $this->tagRepository->all($personId);
    }

    /**
     * Get a specific tag for a person.
     *
     * @param int $personId The ID of the person
     * @param int $tagId The ID of the tag
     * @return PersonTag
     * @throws ModelNotFoundException
     */
    public function findById(int $personId, int $tagId): PersonTag
    {
        $tag = $this->tagRepository->find($tagId, $personId);

        if (!$tag) {
            throw new ModelNotFoundException('Tag not found for this person');
        }

        return $tag;
    }

    /**
     * Add a new tag to a person.
     *
     * @param int $personId The ID of the person
     * @param array $data The tag data including:
     * - 'street_tag' (string)
     * - 'city' (string)
     * - 'state' (string)
     * - 'postal_code' (string)
     * - ['country'] (string)
     * - ['type'] (string)
     * - ['is_primary'] (bool)
     * @return PersonTag
     */
    public function create(int $personId, array $data): PersonTag
    {
        $person = $this->peopleRepository->findById($personId);

        if (!$person) {
            throw new ModelNotFoundException('Person not found');
        }

        return $this->tagRepository->create($personId, $data);
    }

    /**
     * Update an existing tag for a person.
     *
     * @param int $personId
     * @param int $tagId
     * @param array $data
     * @return PersonTag
     * @throws ModelNotFoundException
     */
    public function update(int $personId, int $tagId, array $data): PersonTag
    {
        $tag = $this->tagRepository->find($tagId, $personId);
        if (!$tag) {
            throw new ModelNotFoundException('Tag not found for this person');
        }

        return $this->tagRepository->update($tag, $data);
    }

    /**
     * Delete an tag for a person.
     *
     * @param int $personId
     * @param int $tagId
     * @return bool
     * @throws ModelNotFoundException
     */
    public function delete(int $personId, int $tagId): bool
    {
        $tag = $this->tagRepository->find($tagId, $personId);
        if (!$tag) {
            throw new ModelNotFoundException('Tag not found for this person');
        }
        return $this->tagRepository->delete($tag);
    }
}
