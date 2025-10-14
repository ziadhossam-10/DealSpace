<?php

namespace App\Http\Controllers\Api\Deals;

use App\Http\Controllers\Controller;
use App\Http\Requests\Deals\StoreStageRequest;
use App\Http\Requests\Deals\UpdateStageRequest;
use App\Http\Requests\Deals\UpdateSortOrderRequest;
use App\Http\Resources\DealStageResource;
use App\Services\Deals\DealStageServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Models\DealStage;

class DealStagesController extends Controller
{
    protected $stageService;

    public function __construct(DealStageServiceInterface $stageService)
    {
        $this->stageService = $stageService;
    }

    /**
     * Get all stages.
     *
     * @return JsonResponse JSON response containing all stages.
     */
    public function index(Request $request): JsonResponse
    {
        $typeId = $request->input('type_id');
        $stages = $this->stageService->getAll($typeId);
        return successResponse(
            'Stages retrieved successfully',
            DealStageResource::collection($stages)
        );
    }

    /**
     * Get a specific stage by ID.
     *
     * @param int $id The ID of the stage to retrieve.
     * @return JsonResponse JSON response containing the stage.
     */
    public function show(int $id): JsonResponse
    {
        $stage = $this->stageService->findById($id);
        Gate::authorize('view', $stage);
        return successResponse(
            'Stage retrieved successfully',
            new DealStageResource($stage)
        );
    }

    /**
     * Create a new stage.
     *
     * @param StoreStageRequest $request The request instance containing the data to create a stage.
     * @return JsonResponse JSON response containing the created stage and a 201 status code.
     */
    public function store(StoreStageRequest $request): JsonResponse
    {
        Gate::authorize('create', DealStage::class);
        $stage = $this->stageService->create($request->validated());
        return successResponse(
            'Stage created successfully',
            new DealStageResource($stage),
            201
        );
    }

    /**
     * Update an existing stage.
     *
     * @param UpdateStageRequest $request The request instance containing the data to update.
     * @param int $id The ID of the stage to update.
     * @return JsonResponse JSON response containing the updated stage.
     */
    public function update(UpdateStageRequest $request, int $id): JsonResponse
    {
        $stage = $this->stageService->findById($id);
        Gate::authorize('update', $stage);
        $stage = $this->stageService->update($id, $request->validated());
        return successResponse(
            'Stage updated successfully',
            new DealStageResource($stage)
        );
    }

    /**
     * Delete a stage.
     *
     * @param int $id The ID of the stage to delete.
     * @return JsonResponse JSON response indicating the result of the deletion.
     */
    public function destroy(int $id): JsonResponse
    {
        $stage = $this->stageService->findById($id);
        Gate::authorize('delete', $stage);
        $this->stageService->delete($id);
        return successResponse(
            'Stage deleted successfully',
            null
        );
    }

    /**
     * Update the sort order of a deal stage.
     *
     * @param UpdateSortOrderRequest $request The request instance containing the new sort order.
     * @param int $id The ID of the stage to update.
     * @return JsonResponse JSON response indicating the result of the update.
     */
    public function updateSortOrder(UpdateSortOrderRequest $request, int $id): JsonResponse
    {
        $stage = $this->stageService->findById($id);
        Gate::authorize('update', $stage);
        $this->stageService->updateSortOrder($id, $request->validated()['sort_order']);
        return successResponse(
            'Stage sort order updated successfully',
            null
        );
    }
}
