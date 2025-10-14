<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomFields\StoreCustomFieldRequest;
use App\Http\Requests\CustomFields\UpdateCustomFieldRequest;
use App\Http\Resources\CustomFieldResource;
use App\Services\CustomFields\CustomFieldServiceInterface;
use Illuminate\Http\JsonResponse;

class CustomFieldsController extends Controller
{
    protected $customFieldService;

    public function __construct(CustomFieldServiceInterface $customFieldService)
    {
        $this->customFieldService = $customFieldService;
    }
    /**
     * Get all custom fields.
     *
     * @return JsonResponse JSON response containing all custom fields.
     */
    public function index(): JsonResponse
    {
        $fields = $this->customFieldService->getAll();

        return successResponse(
            'Custom fields retrieved successfully',
            CustomFieldResource::collection($fields)
        );
    }

    /**
     * Get a specific custom field by ID.
     *
     * @param int $id The ID of the custom field to retrieve.
     * @return JsonResponse JSON response containing the custom field.
     */
    public function show(int $id): JsonResponse
    {
        $field = $this->customFieldService->findById($id);
        return successResponse(
            'Custom field retrieved successfully',
            new CustomFieldResource($field)
        );
    }

    /**
     * Create a new custom field.
     *
     * @param StoreCustomFieldRequest $request The request instance containing the data to create a custom field.
     * @return JsonResponse JSON response containing the created custom field and a 201 status code.
     */
    public function store(StoreCustomFieldRequest $request): JsonResponse
    {
        $field = $this->customFieldService->create($request->validated());
        return successResponse(
            'Custom field created successfully',
            new CustomFieldResource($field),
            201
        );
    }

    /**
     * Update an existing custom field.
     *
     * @param UpdateCustomFieldRequest $request The request instance containing the data to update.
     * @param int $id The ID of the custom field to update.
     * @return JsonResponse JSON response containing the updated custom field.
     */
    public function update(UpdateCustomFieldRequest $request, int $id): JsonResponse
    {
        $field = $this->customFieldService->update($id, $request->validated());
        return successResponse(
            'Custom field updated successfully',
            new CustomFieldResource($field)
        );
    }

    /**
     * Delete a custom field.
     *
     * @param int $id The ID of the custom field to delete.
     * @return JsonResponse JSON response indicating the result of the deletion.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->customFieldService->delete($id);
        return successResponse(
            'Custom field deleted successfully',
            null
        );
    }
}
