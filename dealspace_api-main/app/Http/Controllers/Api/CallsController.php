<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Calls\StoreCallRequest;
use App\Http\Requests\Calls\UpdateCallRequest;
use App\Http\Requests\Calls\GetCallsRequest;
use App\Http\Resources\CallCollection;
use App\Http\Resources\CallResource;
use App\Services\Calls\CallServiceInterface;
use Illuminate\Http\JsonResponse;

class CallsController extends Controller
{
    protected $callService;

    public function __construct(CallServiceInterface $callService)
    {
        $this->callService = $callService;
    }

    /**
     * Get all calls.
     *
     * @return JsonResponse JSON response containing all calls.
     */
    public function index(GetCallsRequest $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);
        $personId = $request->input('person_id', null);

        $calls = $this->callService->getAll($perPage, $page, $personId);

        return successResponse(
            'Calls retrieved successfully',
            new CallCollection($calls)
        );
    }

    /**
     * Get a specific call by ID.
     *
     * @param int $id The ID of the call to retrieve.
     * @return JsonResponse JSON response containing the call.
     */
    public function show(int $id): JsonResponse
    {
        $call = $this->callService->findById($id);
        return successResponse(
            'Call retrieved successfully',
            new CallResource($call)
        );
    }

    /**
     * Create a new call.
     *
     * @param StoreCallRequest $request The request instance containing the data to create a call.
     * @return JsonResponse JSON response containing the created call and a 201 status code.
     */
    public function store(StoreCallRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;

        $call = $this->callService->create($data);
        return successResponse(
            'Call created successfully',
            new CallResource($call),
            201
        );
    }

    /**
     * Update an existing call.
     *
     * @param UpdateCallRequest $request The request instance containing the data to update.
     * @param int $id The ID of the call to update.
     * @return JsonResponse JSON response containing the updated call.
     */
    public function update(UpdateCallRequest $request, int $id): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;

        $call = $this->callService->update($id, $data);
        return successResponse(
            'Call updated successfully',
            new CallResource($call)
        );
    }

    /**
     * Delete a call.
     *
     * @param int $id The ID of the call to delete.
     * @return JsonResponse JSON response indicating the result of the deletion.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->callService->delete($id);
        return successResponse(
            'Call deleted successfully',
            null
        );
    }
}
