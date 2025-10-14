<?php

namespace App\Http\Controllers\Api\People;

use App\Http\Controllers\Controller;
use App\Http\Requests\People\StoreStageRequest;
use App\Http\Requests\People\UpdateStageRequest;
use App\Http\Resources\StageResource;
use App\Services\People\StageServiceInterface;
use Illuminate\Http\JsonResponse;

class StagesController extends Controller
{
    protected $stageService;

    public function __construct(StageServiceInterface $stageService)
    {
        $this->stageService = $stageService;
    }


    /**
     * Get all stages.
     *
     * @return JsonResponse JSON response containing a list of all stages.
     */
    public function index(): JsonResponse
    {
        $stages = $this->stageService->getAll();

        return successResponse(
            'Stages retrieved successfully',
            StageResource::collection($stages)
        );
    }

    /**
     * Create a new stage.
     *
     * @param StoreStageRequest $request The request instance containing the stage data.
     * @return JsonResponse JSON response containing the newly created stage and a 201 status code.
     */
    public function store(StoreStageRequest $request): JsonResponse
    {
        $stage = $this->stageService->create($request->validated());

        return successResponse(
            'Stage created successfully',
            new StageResource($stage),
            201
        );
    }


    /**
     * Get a specific stage by ID.
     *
     * @param int $id The ID of the stage to retrieve.
     * @return JsonResponse JSON response containing the requested stage.
     */
    public function show(int $id): JsonResponse
    {
        $stage = $this->stageService->findById($id);

        return successResponse(
            'Stage retrieved successfully',
            new StageResource($stage)
        );
    }

    /**
     * Update an existing stage.
     *
     * @param UpdateStageRequest $request The request instance containing the updated stage data.
     * @param int $id The ID of the stage to update.
     * @return JsonResponse JSON response containing the updated stage.
     */
    public function update(UpdateStageRequest $request, int $id): JsonResponse
    {
        $stage = $this->stageService->update($id, $request->validated());

        return successResponse(
            'Stage updated successfully',
            new StageResource($stage)
        );
    }

    /**
     * Delete a stage.
     *
     * @param int $id The ID of the stage to delete.
     * @return JsonResponse JSON response containing a success message and a 200 status code.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->stageService->delete($id);

        return successResponse(
            'Stage deleted successfully',
            null
        );
    }
}