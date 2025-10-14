<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Events\StoreEventRequest;
use App\Http\Requests\Events\UpdateEventRequest;
use App\Http\Resources\EventCollection;
use App\Http\Resources\EventResource;
use App\Services\Events\EventServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventsController extends Controller
{
    protected $eventService;

    public function __construct(EventServiceInterface $eventService)
    {
        $this->eventService = $eventService;
    }

    /**
     * Get all events.
     *
     * @return JsonResponse JSON response containing all events.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);
        $search = $request->input('search', null);

        // Build filters array from request
        $filters = [];
        if ($request->has('type')) {
            $filters['type'] = $request->input('type');
        }
        if ($request->has('source')) {
            $filters['source'] = $request->input('source');
        }
        if ($request->has('system')) {
            $filters['system'] = $request->input('system');
        }
        if ($request->has('date_from')) {
            $filters['date_from'] = $request->input('date_from');
        }
        if ($request->has('date_to')) {
            $filters['date_to'] = $request->input('date_to');
        }

        if ($request->has('person_id')) {
            $filters['person_id'] = $request->input('person_id');
        }

        $events = $this->eventService->getAll($perPage, $page, $search, $filters);

        return successResponse(
            'Events retrieved successfully',
            new EventCollection($events)
        );
    }

    /**
     * Get a specific event by ID.
     *
     * @param int $id The ID of the event to retrieve.
     * @return JsonResponse JSON response containing the event.
     */
    public function show(int $id): JsonResponse
    {
        $event = $this->eventService->findById($id);

        return successResponse(
            'Event retrieved successfully',
            new EventResource($event)
        );
    }

    /**
     * Create a new event.
     *
     * @param StoreEventRequest $request The request instance containing the data to create an event.
     * @return JsonResponse JSON response containing the created event and a 201 status code.
     */
    public function store(StoreEventRequest $request): JsonResponse
    {
        $event = $this->eventService->create($request->validated());

        return successResponse(
            'Event created successfully',
            new EventResource($event),
            201
        );
    }

    /**
     * Update an existing event.
     *
     * @param UpdateEventRequest $request The request instance containing the data to update.
     * @param int $id The ID of the event to update.
     * @return JsonResponse JSON response containing the updated event.
     */
    public function update(UpdateEventRequest $request, int $id): JsonResponse
    {
        $event = $this->eventService->update($id, $request->validated());

        return successResponse(
            'Event updated successfully',
            new EventResource($event)
        );
    }

    /**
     * Delete an event.
     *
     * @param int $id The ID of the event to delete.
     * @return JsonResponse JSON response indicating the result of the deletion.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->eventService->delete($id);

        return successResponse(
            'Event deleted successfully',
            null
        );
    }

    /**
     * Get events by type.
     *
     * @param Request $request
     * @param string $type
     * @return JsonResponse
     */
    public function getByType(Request $request, string $type): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);

        $events = $this->eventService->getByType($type, $perPage, $page);

        return successResponse(
            'Events retrieved successfully',
            new EventCollection($events)
        );
    }

    /**
     * Get events by date range.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getByDateRange(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);

        if (!$startDate || !$endDate) {
            return errorResponse('Both start_date and end_date are required', 400);
        }

        $events = $this->eventService->getByDateRange($startDate, $endDate, $perPage, $page);

        return successResponse(
            'Events retrieved successfully',
            new EventCollection($events)
        );
    }

    /**
     * Get available event types.
     *
     * @return JsonResponse
     */
    public function getEventTypes(): JsonResponse
    {
        return successResponse(
            'Event types retrieved successfully',
            \App\Models\Event::getTypes()
        );
    }
}
