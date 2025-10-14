<?php

namespace App\Http\Controllers\Api\Appointments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Appointments\StoreOutcomeRequest;
use App\Http\Requests\Appointments\UpdateOutcomeRequest;
use App\Http\Requests\Appointments\UpdateSortOrderRequest;
use App\Http\Resources\AppointmentOutcomeResource;
use App\Services\Appointments\AppointmentOutcomeServiceInterface;
use Illuminate\Http\JsonResponse;

class AppointmentOutcomesController extends Controller
{
    protected $outcomeService;

    public function __construct(AppointmentOutcomeServiceInterface $outcomeService)
    {
        $this->outcomeService = $outcomeService;
    }

    /**
     * Get all outcomes.
     *
     * @return JsonResponse JSON response containing all outcomes.
     */
    public function index(): JsonResponse
    {
        $outcomes = $this->outcomeService->getAll();
        return successResponse(
            'Outcomes retrieved successfully',
            AppointmentOutcomeResource::collection($outcomes)
        );
    }

    /**
     * Get a specific outcome by ID.
     *
     * @param int $id The ID of the outcome to retrieve.
     * @return JsonResponse JSON response containing the outcome.
     */
    public function show(int $id): JsonResponse
    {
        $outcome = $this->outcomeService->findById($id);
        return successResponse(
            'Outcome retrieved successfully',
            new AppointmentOutcomeResource($outcome)
        );
    }

    /**
     * Create a new outcome.
     *
     * @param StoreOutcomeRequest $request The request instance containing the data to create a outcome.
     * @return JsonResponse JSON response containing the created outcome and a 201 status code.
     */
    public function store(StoreOutcomeRequest $request): JsonResponse
    {
        $outcome = $this->outcomeService->create($request->validated());
        return successResponse(
            'Outcome created successfully',
            new AppointmentOutcomeResource($outcome),
            201
        );
    }

    /**
     * Update an existing outcome.
     *
     * @param UpdateOutcomeRequest $request The request instance containing the data to update.
     * @param int $id The ID of the outcome to update.
     * @return JsonResponse JSON response containing the updated outcome.
     */
    public function update(UpdateOutcomeRequest $request, int $id): JsonResponse
    {
        $outcome = $this->outcomeService->update($id, $request->validated());
        return successResponse(
            'Outcome updated successfully',
            new AppointmentOutcomeResource($outcome)
        );
    }

    /**
     * Delete a outcome.
     *
     * @param int $id The ID of the outcome to delete.
     * @return JsonResponse JSON response indicating the result of the deletion.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->outcomeService->delete($id);
        return successResponse(
            'Outcome deleted successfully',
            null
        );
    }

    /**
     * Update the sort order of a appointment outcome.
     *
     * @param UpdateSortOrderRequest $request The request instance containing the new sort order.
     * @param int $id The ID of the outcome to update.
     * @return JsonResponse JSON response indicating the result of the update.
     */
    public function updateSortOrder(UpdateSortOrderRequest $request, int $id): JsonResponse
    {
        $this->outcomeService->updateSortOrder($id, $request->validated()['sort_order']);
        return successResponse(
            'Outcome sort order updated successfully',
            null
        );
    }
}
