<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Groups\StoreGroupRequest;
use App\Http\Requests\Groups\UpdateGroupRequest;
use App\Http\Resources\GroupCollection;
use App\Http\Resources\GroupResource;
use App\Http\Resources\UserCollection;
use App\Services\Groups\GroupServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Models\Group;

class GroupsController extends Controller
{
    protected $groupService;

    public function __construct(GroupServiceInterface $groupService)
    {
        $this->groupService = $groupService;
    }

    /**
     * Get all groups.
     *
     * @return JsonResponse JSON response containing all groups.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);
        $search = $request->input('search', null);

        Gate::authorize('viewAny', Group::class);
        $groups = $this->groupService->getAll($perPage, $page, $search);

        return successResponse(
            'Groups retrieved successfully',
            new GroupCollection($groups)
        );
    }

    /**
     * Get a specific group by ID.
     *
     * @param int $id The ID of the group to retrieve.
     * @return JsonResponse JSON response containing the group.
     */
    public function show(int $id): JsonResponse
    {
        $group = $this->groupService->findById($id);
        Gate::authorize('view', $group);
        return successResponse(
            'Group retrieved successfully',
            new GroupResource($group)
        );
    }

    /**
     * Create a new group.
     *
     * @param StoreGroupRequest $request The request instance containing the data to create a group.
     * @return JsonResponse JSON response containing the created group and a 201 status code.
     */
    public function store(StoreGroupRequest $request): JsonResponse
    {
        Gate::authorize('create', Group::class);
        $group = $this->groupService->create($request->validated());
        return successResponse(
            'Group created successfully',
            new GroupResource($group),
            201
        );
    }

    /**
     * Update an existing group.
     *
     * @param UpdateGroupRequest $request The request instance containing the data to update.
     * @param int $id The ID of the group to update.
     * @return JsonResponse JSON response containing the updated group.
     */
    public function update(UpdateGroupRequest $request, int $id): JsonResponse
    {
        $group = $this->groupService->findById($id);
        Gate::authorize('update', $group);
        $group = $this->groupService->update($id, $request->validated());
        return successResponse(
            'Group updated successfully',
            new GroupResource($group)
        );
    }

    /**
     * Delete a group.
     *
     * @param int $id The ID of the group to delete.
     * @return JsonResponse JSON response indicating the result of the deletion.
     */
    public function destroy(int $id): JsonResponse
    {
        $group = $this->groupService->findById($id);
        Gate::authorize('delete', $group);
        $this->groupService->delete($id);
        return successResponse(
            'Group deleted successfully',
            null
        );
    }

    /**
     * Get all groups of a specific type.
     *
     * @param string $type The type of groups to retrieve (Lender or Agent).
     * @return JsonResponse JSON response containing the groups.
     */
    public function getAllByType(string $type): JsonResponse
    {
        $groups = $this->groupService->getAllByType($type);
        Gate::authorize('viewAny', Group::class);
        return successResponse(
            "$type groups retrieved successfully",
            new GroupCollection($groups)
        );
    }

    /**
     * Get all users in a group.
     *
     * @param int $id The ID of the group.
     * @return JsonResponse JSON response containing the users.
     */
    public function getGroupUsers(int $id): JsonResponse
    {
        $group = $this->groupService->findById($id);
        Gate::authorize('view', $group);
        $users = $this->groupService->getGroupUsers($id);
        return successResponse(
            'Group users retrieved successfully',
            new UserCollection($users)
        );
    }

    /**
     * Get the primary group.
     *
     * @return JsonResponse JSON response containing the primary group.
     */
    public function getPrimary(): JsonResponse
    {
        $group = $this->groupService->getPrimary();
        Gate::authorize('view', $group);
        return successResponse(
            $group ? 'Primary group retrieved successfully' : 'No primary group found',
            new GroupResource($group)
        );
    }

    /**
     * Update a user's sort order within a group.
     *
     * @param int $groupId The ID of the group.
     * @param int $userId The ID of the user.
     * @param int $sortOrder The new sort order.
     * @return JsonResponse JSON response indicating the result of the update.
     */
    public function updateUserSortOrder(int $groupId, int $userId, int $sortOrder): JsonResponse
    {
        $this->groupService->updateUserSortOrder($groupId, $userId, $sortOrder);
        Gate::authorize('update', $this->groupService->findById($groupId));
        return successResponse(
            'User sort order updated successfully',
            null
        );
    }
}
