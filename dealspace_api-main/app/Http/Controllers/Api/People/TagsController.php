<?php

namespace App\Http\Controllers\Api\People;

use App\Http\Controllers\Controller;
use App\Http\Requests\People\StorePersonTagRequest;
use App\Http\Requests\People\UpdatePersonTagRequest;
use App\Services\People\TagServiceInterface;
use Illuminate\Http\JsonResponse;

class TagsController extends Controller
{
    protected $tagService;

    public function __construct(TagServiceInterface $tagService)
    {
        $this->tagService = $tagService;
    }

    /**
     * Display a listing of tags for the person.
     *
     * @param int $personId The ID of the person to get tags for.
     * @return JsonResponse JSON response containing the list of tags.
     */
    public function index(int $personId): JsonResponse
    {
        $tags = $this->tagService->getAll($personId);

        return successResponse(
            'Tags retrieved successfully',
            $tags
        );
    }

    /**
     * Store a newly created tag for the person.
     *
     * @param StorePersonTagRequest $request The request instance containing the data to create.
     * @param int $personId The ID of the person to add the tag to.
     * @return JsonResponse JSON response containing the added tag and a 201 status code.
     */
    public function store(StorePersonTagRequest $request, int $personId): JsonResponse
    {
        $tag = $this->tagService->create($personId, $request->validated());

        return successResponse(
            'Tag created successfully',
            $tag,
            201
        );
    }

    /**
     * Display the specified tag of the person.
     *
     * @param int $personId The ID of the person.
     * @param int $tagId The ID of the tag to show.
     * @return JsonResponse JSON response containing the tag.
     */
    public function show(int $personId, int $tagId): JsonResponse
    {
        $tag = $this->tagService->findById($personId, $tagId);

        return successResponse(
            'Tag retrieved successfully',
            $tag
        );
    }

    /**
     * Update the specified tag of the person.
     *
     * @param UpdatePersonTagRequest $request The request instance containing the data to update.
     * @param int $personId The ID of the person.
     * @param int $tagId The ID of the tag to update.
     * @return JsonResponse JSON response containing the updated tag.
     */
    public function update(UpdatePersonTagRequest $request, int $personId, int $tagId): JsonResponse
    {
        $tag = $this->tagService->update($personId, $tagId, $request->validated());

        return successResponse(
            'Tag updated successfully',
            $tag
        );
    }

    /**
     * Remove the specified tag from the person.
     *
     * @param int $personId The ID of the person.
     * @param int $tagId The ID of the tag to delete.
     * @return JsonResponse JSON response indicating the result of the deletion.
     */
    public function destroy(int $personId, int $tagId): JsonResponse
    {
        $this->tagService->delete($personId, $tagId);

        return successResponse(
            'Tag deleted successfully',
            null
        );
    }

}
