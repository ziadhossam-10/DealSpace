<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teams\StoreTeamRequest;
use App\Http\Requests\Teams\UpdateTeamRequest;
use App\Http\Requests\Teams\BulkDeleteTeamRequest;
use App\Http\Resources\TeamCollection;
use App\Http\Resources\TeamResource;
use App\Services\Teams\TeamServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Models\Team;

class TeamsController extends Controller
{
    protected $teamService;

    public function __construct(TeamServiceInterface $teamService)
    {
        $this->teamService = $teamService;
    }

    /**
     * Get all teams.
     *
     * @return JsonResponse JSON response containing all teams.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);
        $search = $request->input('search', null);

        $teams = $this->teamService->getAll($perPage, $page, $search);
        foreach ($teams as $team) {
            Gate::authorize('viewAny', $team);
        }
        return successResponse(
            'Teams retrieved successfully',
            new TeamCollection($teams)
        );
    }

    /**
     * Get a specific team by ID.
     *
     * @param int $id The ID of the team to retrieve.
     * @return JsonResponse JSON response containing the team.
     */
    public function show(int $id): JsonResponse
    {
        $team = $this->teamService->findById($id);
        Gate::authorize('view', $team);

        return successResponse(
            'Team retrieved successfully',
            new TeamResource($team)
        );
    }

    /**
     * Create a new team.
     *
     * @param StoreTeamRequest $request The request instance containing the data to create a team.
     * @return JsonResponse JSON response containing the created team and a 201 status code.
     */
    public function store(StoreTeamRequest $request): JsonResponse
    {
        Gate::authorize('create', Team::class);
        $team = $this->teamService->create($request->validated());

        return successResponse(
            'Team created successfully',
            new TeamResource($team),
            201
        );
    }

    /**
     * Update an existing team.
     *
     * @param UpdateTeamRequest $request The request instance containing the data to update.
     * @param int $id The ID of the team to update.
     * @return JsonResponse JSON response containing the updated team.
     */
    public function update(UpdateTeamRequest $request, int $id): JsonResponse
    {
        $team = $this->teamService->findById($id);
        Gate::authorize('update', $team);
        $team = $this->teamService->update($id, $request->validated());

        return successResponse(
            'Team updated successfully',
            new TeamResource($team)
        );
    }

    /**
     * Delete a team.
     *
     * @param int $id The ID of the team to delete.
     * @return JsonResponse JSON response indicating the result of the deletion.
     */
    public function destroy(int $id): JsonResponse
    {
        $team = $this->teamService->findById($id);
        Gate::authorize('delete', $team);
        $this->teamService->delete($id);

        return successResponse(
            'Team deleted successfully',
            null
        );
    }

    /**
     * Bulk delete teams based on provided parameters
     *
     * @param BulkDeleteTeamRequest $request
     * @return JsonResponse
     */
    public function bulkDelete(BulkDeleteTeamRequest $request): JsonResponse
    {
        Gate::authorize('delete', Team::class);
        $deletedCount = $this->teamService->bulkDelete($request->validated());

        return successResponse(
            'Teams deleted successfully',
            ['count' => $deletedCount]
        );
    }
}
