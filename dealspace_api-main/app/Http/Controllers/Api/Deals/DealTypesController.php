<?php
// DealTypesController.php
namespace App\Http\Controllers\Api\Deals;

use App\Http\Controllers\Controller;
use App\Http\Requests\Deals\StoreDealTypeRequest;
use App\Http\Requests\Deals\UpdateDealTypeRequest;
use App\Http\Requests\Deals\UpdateSortOrderRequest;
use App\Http\Resources\DealTypeResource;
use App\Services\Deals\DealTypeServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use App\Models\DealType;

class DealTypesController extends Controller
{
    protected $dealTypeService;

    public function __construct(DealTypeServiceInterface $dealTypeService)
    {
        $this->dealTypeService = $dealTypeService;
    }

    /**
     * Get all deal types.
     *
     * @return JsonResponse JSON response containing all deal types.
     */
    public function index(): JsonResponse
    {
        Gate::authorize('viewAny', DealType::class);
        $dealTypes = $this->dealTypeService->getAll();
        return successResponse(
            'Deal types retrieved successfully',
            DealTypeResource::collection($dealTypes)
        );
    }

    /**
     * Get a specific deal type by ID.
     *
     * @param int $id The ID of the deal type to retrieve.
     * @return JsonResponse JSON response containing the deal type.
     */
    public function show(int $id): JsonResponse
    {
        $dealType = $this->dealTypeService->findById($id);
        Gate::authorize('view', $dealType);
        return successResponse(
            'Deal type retrieved successfully',
            new DealTypeResource($dealType)
        );
    }

    /**
     * Create a new deal type.
     *
     * @param StoreDealTypeRequest $request The request instance containing the data to create a deal type.
     * @return JsonResponse JSON response containing the created deal type and a 201 status code.
     */
    public function store(StoreDealTypeRequest $request): JsonResponse
    {
        Gate::authorize('create', DealType::class);
        $dealType = $this->dealTypeService->create($request->validated());
        return successResponse(
            'Deal type created successfully',
            new DealTypeResource($dealType),
            201
        );
    }

    /**
     * Update an existing deal type.
     *
     * @param UpdateDealTypeRequest $request The request instance containing the data to update.
     * @param int $id The ID of the deal type to update.
     * @return JsonResponse JSON response containing the updated deal type.
     */
    public function update(UpdateDealTypeRequest $request, int $id): JsonResponse
    {
        $dealType = $this->dealTypeService->findById($id);
        Gate::authorize('update', $dealType);
        $dealType = $this->dealTypeService->update($id, $request->validated());
        return successResponse(
            'Deal type updated successfully',
            new DealTypeResource($dealType)
        );
    }

    /**
     * Delete a deal type.
     *
     * @param int $id The ID of the deal type to delete.
     * @return JsonResponse JSON response indicating the result of the deletion.
     */
    public function destroy(int $id): JsonResponse
    {
        $dealType = $this->dealTypeService->findById($id);
        Gate::authorize('delete', $dealType);
        $this->dealTypeService->delete($id);
        return successResponse(
            'Deal type deleted successfully',
            null
        );
    }

    /**
     * Update the sort order of a deal type.
     *
     * @param UpdateSortOrderRequest $request The request instance containing the new sort order.
     * @param int $id The ID of the deal type to update.
     * @return JsonResponse JSON response indicating the result of the update.
     */
    public function updateSortOrder(UpdateSortOrderRequest $request, int $id): JsonResponse
    {
        $dealType = $this->dealTypeService->findById($id);
        Gate::authorize('update', $dealType);
        $this->dealTypeService->updateSortOrder($id, $request->validated()['sort_order']);
        return successResponse(
            'Deal type sort order updated successfully',
            null
        );
    }
}
