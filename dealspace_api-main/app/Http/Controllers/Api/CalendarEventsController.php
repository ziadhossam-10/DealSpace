<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Calendar\StoreCalendarEventRequest;
use App\Http\Requests\Calendar\UpdateCalendarEventRequest;
use App\Http\Resources\CalendarEventResource;
use App\Services\Calendar\CalendarEventServiceInterface;
use Illuminate\Http\JsonResponse;

class CalendarEventsController extends Controller
{
    protected $calendarEventService;

    public function __construct(CalendarEventServiceInterface $calendarEventService)
    {
        $this->calendarEventService = $calendarEventService;
    }

    /**
     * Get all calendar events with optional filters.
     *
     * @return JsonResponse JSON response containing a list of filtered calendar events.
     */
    public function index(): JsonResponse
    {
        $filters = request()->only([
            'start_date',
            'end_date',
            'calendar_account_id',
            'person_id',
            'user_id',
            'event_type',
            'status',
            'sync_status'
        ]);

        $calendarEvents = $this->calendarEventService->getAll($filters);

        return successResponse(
            'Calendar events retrieved successfully',
            CalendarEventResource::collection($calendarEvents)
        );
    }

    /**
     * Create a new calendar event.
     *
     * @param StoreCalendarEventRequest $request The request instance containing the calendar event data.
     * @return JsonResponse JSON response containing the newly created calendar event and a 201 status code.
     */
    public function store(StoreCalendarEventRequest $request): JsonResponse
    {
        $calendarEvent = $this->calendarEventService->create($request->validated());
        return successResponse(
            'Calendar event created successfully',
            new CalendarEventResource($calendarEvent),
            201
        );
    }

    /**
     * Get a specific calendar event by ID.
     *
     * @param int $id The ID of the calendar event to retrieve.
     * @return JsonResponse JSON response containing the requested calendar event.
     */
    public function show(int $id): JsonResponse
    {
        $calendarEvent = $this->calendarEventService->findById($id);
        return successResponse(
            'Calendar event retrieved successfully',
            new CalendarEventResource($calendarEvent)
        );
    }

    /**
     * Update an existing calendar event.
     *
     * @param UpdateCalendarEventRequest $request The request instance containing the updated calendar event data.
     * @param int $id The ID of the calendar event to update.
     * @return JsonResponse JSON response containing the updated calendar event.
     */
    public function update(UpdateCalendarEventRequest $request, int $id): JsonResponse
    {
        $calendarEvent = $this->calendarEventService->update($id, $request->validated());
        return successResponse(
            'Calendar event updated successfully',
            new CalendarEventResource($calendarEvent)
        );
    }

    /**
     * Delete a calendar event.
     *
     * @param int $id The ID of the calendar event to delete.
     * @return JsonResponse JSON response containing a success message and a 200 status code.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->calendarEventService->delete($id);
        return successResponse(
            'Calendar event deleted successfully',
            null
        );
    }
}
