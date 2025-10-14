<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Activities\ActivityService;
use App\Http\Resources\ActivityLogCollection;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ActivitiesController extends Controller
{
    protected $activityService;

    public function __construct(ActivityService $activityService)
    {
        $this->activityService = $activityService;
    }

    /**
     * Get all activities for a person in chronological order
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);
        $personId = $request->input('person_id');
        $type = $request->input('type'); // Optional filter by type

        if ($type) {
            $activities = $this->activityService->getPersonActivitiesByType($personId, $type, $perPage, $page);
        } else {
            $activities = $this->activityService->getPersonActivities($personId, $perPage, $page);
        }

        return successResponse(
            'Activities retrieved successfully',
            new ActivityLogCollection($activities)
        );
    }
}
