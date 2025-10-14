<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ponds\StorePondRequest;
use App\Http\Requests\Ponds\UpdatePondRequest;
use App\Http\Requests\Ponds\BulkDeletePondRequest;
use App\Http\Resources\PondCollection;
use App\Http\Resources\PondResource;
use App\Services\Ponds\PondServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Models\Pond;

class PondsController extends Controller
{
    protected $pondService;

    public function __construct(PondServiceInterface $pondService)
    {
        $this->pondService = $pondService;
    }

    /**
     * Get all ponds.
     *
     * @return JsonResponse JSON response containing all ponds.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);
        $search = $request->input('search', null);

        $ponds = $this->pondService->getAll($perPage, $page, $search);
        foreach ($ponds as $pond) {
            Gate::authorize('viewAny', $pond);
        }
        return successResponse(
            'Ponds retrieved successfully',
            new PondCollection($ponds)
        );
    }

    /**
     * Get a specific pond by ID.
     *
     * @param int $id The ID of the pond to retrieve.
     * @return JsonResponse JSON response containing the pond.
     */
    public function show(int $id): JsonResponse
    {
        $pond = $this->pondService->findById($id);
        Gate::authorize('view', $pond);
        return successResponse(
            'Pond retrieved successfully',
            new PondResource($pond)
        );
    }

    /**
     * Create a new pond.
     *
     * @param StorePondRequest $request The request instance containing the data to create a pond.
     * @return JsonResponse JSON response containing the created pond and a 201 status code.
     */
    public function store(StorePondRequest $request): JsonResponse
    {
        Gate::authorize('create', Pond::class);
        $pond = $this->pondService->create($request->validated());
        return successResponse(
            'Pond created successfully',
            new PondResource($pond),
            201
        );
    }

    /**
     * Update an existing pond.
     *
     * @param UpdatePondRequest $request The request instance containing the data to update.
     * @param int $id The ID of the pond to update.
     * @return JsonResponse JSON response containing the updated pond.
     */
    public function update(UpdatePondRequest $request, int $id): JsonResponse
    
    {
        $pond = $this->pondService->findById($id);
        Gate::authorize('update', $pond);
        $this->pondService->update($id, $request->validated());
        return successResponse(
            'Pond updated successfully',
            new PondResource($pond)
        );
    }

    /**
     * Delete a pond.
     *
     * @param int $id The ID of the pond to delete.
     * @return JsonResponse JSON response indicating the result of the deletion.
     */
    public function destroy(int $id): JsonResponse
    {
        $pond = $this->pondService->findById($id);
        Gate::authorize('delete', $pond);
        $this->pondService->delete($id);
        return successResponse(
            'Pond deleted successfully',
            null
        );
    }

    /**
     * Bulk delete ponds based on provided parameters
     *
     * @param BulkDeletePondRequest $request
     * @return JsonResponse
     */
    public function bulkDelete(BulkDeletePondRequest $request): JsonResponse
    {
        foreach ($request->ids as $id) {
            $pond = $this->pondService->findById($id);
            Gate::authorize('delete', $pond);
        }
        $deletedCount = $this->pondService->bulkDelete($request->validated());

        return successResponse(
            'Ponds deleted successfully',
            ['count' => $deletedCount]
        );
    }
}
