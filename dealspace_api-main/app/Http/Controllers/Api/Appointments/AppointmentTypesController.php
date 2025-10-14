<?php

namespace App\Http\Controllers\Api\Appointments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Appointments\StoreTypeRequest;
use App\Http\Requests\Appointments\UpdateTypeRequest;
use App\Http\Requests\Appointments\UpdateSortOrderRequest;
use App\Http\Resources\AppointmentTypeResource;
use App\Services\Appointments\AppointmentTypeServiceInterface;
use Illuminate\Http\JsonResponse;

class AppointmentTypesController extends Controller
{
    protected $typeService;

    public function __construct(AppointmentTypeServiceInterface $typeService)
    {
        $this->typeService = $typeService;
    }

    /**
     * Get all types.
     *
     * @return JsonResponse JSON response containing all types.
     */
    public function index(): JsonResponse
    {
        $types = $this->typeService->getAll();
        return successResponse(
            'Types retrieved successfully',
            AppointmentTypeResource::collection($types)
        );
    }

    /**
     * Get a specific type by ID.
     *
     * @param int $id The ID of the type to retrieve.
     * @return JsonResponse JSON response containing the type.
     */
    public function show(int $id): JsonResponse
    {
        $type = $this->typeService->findById($id);
        return successResponse(
            'Type retrieved successfully',
            new AppointmentTypeResource($type)
        );
    }

    /**
     * Create a new type.
     *
     * @param StoreTypeRequest $request The request instance containing the data to create a type.
     * @return JsonResponse JSON response containing the created type and a 201 status code.
     */
    public function store(StoreTypeRequest $request): JsonResponse
    {
        $type = $this->typeService->create($request->validated());
        return successResponse(
            'Type created successfully',
            new AppointmentTypeResource($type),
            201
        );
    }

    /**
     * Update an existing type.
     *
     * @param UpdateTypeRequest $request The request instance containing the data to update.
     * @param int $id The ID of the type to update.
     * @return JsonResponse JSON response containing the updated type.
     */
    public function update(UpdateTypeRequest $request, int $id): JsonResponse
    {
        $type = $this->typeService->update($id, $request->validated());
        return successResponse(
            'Type updated successfully',
            new AppointmentTypeResource($type)
        );
    }

    /**
     * Delete a type.
     *
     * @param int $id The ID of the type to delete.
     * @return JsonResponse JSON response indicating the result of the deletion.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->typeService->delete($id);
        return successResponse(
            'Type deleted successfully',
            null
        );
    }

    /**
     * Update the sort order of a appointment type.
     *
     * @param UpdateSortOrderRequest $request The request instance containing the new sort order.
     * @param int $id The ID of the type to update.
     * @return JsonResponse JSON response indicating the result of the update.
     */
    public function updateSortOrder(UpdateSortOrderRequest $request, int $id): JsonResponse
    {
        $this->typeService->updateSortOrder($id, $request->validated()['sort_order']);
        return successResponse(
            'Type sort order updated successfully',
            null
        );
    }
}
