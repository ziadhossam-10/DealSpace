<?php

namespace App\Services\Calendar;

use App\Models\CalendarEvent;
use App\Repositories\Calendar\CalendarEventRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Carbon\Carbon;

class CalendarEventService implements CalendarEventServiceInterface
{
    protected $calendarEventRepository;

    public function __construct(
        CalendarEventRepositoryInterface $calendarEventRepository
    ) {
        $this->calendarEventRepository = $calendarEventRepository;
    }

    /**
     * Get all calendar events with optional filtering.
     *
     * @param array $filters Optional filters including:
     * - 'start_date' (Carbon|string|null) Start date to filter events from
     * - 'end_date' (Carbon|string|null) End date to filter events to
     * - 'calendar_account_id' (int) Filter by calendar account
     * - 'person_id' (int) Filter by person
     * - 'user_id' (int) Filter by user
     * - 'event_type' (string) Filter by event type ('event', 'appointment', 'task')
     * - 'status' (string) Filter by status ('confirmed', 'tentative', 'cancelled')
     * - 'sync_status' (string) Filter by sync status ('synced', 'pending', 'failed')
     * @return Collection Collection of CalendarEvent model instances
     */
    public function getAll(array $filters = []): Collection
    {
        return $this->calendarEventRepository->getAll($filters);
    }

    /**
     * Get a specific calendar event by ID.
     *
     * @param int $id The ID of the calendar event to retrieve
     * @return CalendarEvent The CalendarEvent model instance
     * @throws ModelNotFoundException
     */
    public function findById(int $id): CalendarEvent
    {
        $calendarEvent = $this->calendarEventRepository->findById($id);
        if (!$calendarEvent) {
            throw new ModelNotFoundException('Calendar event not found');
        }
        return $calendarEvent;
    }

    /**
     * Create a new calendar event.
     *
     * @param array $data The calendar event data including:
     * - 'calendar_account_id' (int) The ID of the calendar account
     * - 'person_id' (int|null) The ID of the associated person
     * - 'user_id' (int|null) The ID of the user who created the event
     * - 'tenant_id' (int|null) The tenant ID for multi-tenancy
     * - 'external_id' (string|null) External calendar service ID
     * - 'title' (string) The event title
     * - 'description' (string|null) The event description
     * - 'location' (string|null) The event location
     * - 'start_time' (datetime) The start time of the event
     * - 'end_time' (datetime) The end time of the event
     * - 'timezone' (string|null) The event timezone
     * - 'is_all_day' (bool) Whether the event is all-day
     * - 'status' (string) Event status ('confirmed', 'tentative', 'cancelled')
     * - 'visibility' (string) Event visibility ('default', 'public', 'private')
     * - 'attendees' (array|null) List of event attendees
     * - 'organizer_email' (string|null) Email of the event organizer
     * - 'meeting_link' (string|null) Link to online meeting
     * - 'reminders' (array|null) Event reminders configuration
     * - 'recurrence' (array|null) Recurrence rules
     * - 'sync_status' (string) Sync status ('synced', 'pending', 'failed')
     * - 'sync_direction' (string) Sync direction ('from_external', 'to_external', 'bidirectional')
     * - 'last_synced_at' (datetime|null) Last sync timestamp
     * - 'external_updated_at' (datetime|null) External update timestamp
     * - 'sync_error' (string|null) Sync error message
     * - 'crm_meeting_id' (int|null) CRM meeting ID if applicable
     * - 'syncable_type' (string|null) Type of linked model ('App\Models\Appointment', 'App\Models\Task')
     * - 'syncable_id' (int|null) ID of linked model
     * - 'event_type' (string) Event type ('event', 'appointment', 'task')
     * @return CalendarEvent The newly created CalendarEvent model instance
     */
    public function create(array $data): CalendarEvent
    {
        return $this->calendarEventRepository->create($data);
    }

    /**
     * Update an existing calendar event.
     *
     * @param int $id The ID of the calendar event to update
     * @param array $data The updated calendar event data including:
     * - ['calendar_account_id'] (int) The ID of the calendar account
     * - ['person_id'] (int|null) The ID of the associated person
     * - ['user_id'] (int|null) The ID of the user who created the event
     * - ['tenant_id'] (int|null) The tenant ID for multi-tenancy
     * - ['external_id'] (string|null) External calendar service ID
     * - ['title'] (string) The event title
     * - ['description'] (string|null) The event description
     * - ['location'] (string|null) The event location
     * - ['start_time'] (datetime) The start time of the event
     * - ['end_time'] (datetime) The end time of the event
     * - ['timezone'] (string|null) The event timezone
     * - ['is_all_day'] (bool) Whether the event is all-day
     * - ['status'] (string) Event status ('confirmed', 'tentative', 'cancelled')
     * - ['visibility'] (string) Event visibility ('default', 'public', 'private')
     * - ['attendees'] (array|null) List of event attendees
     * - ['organizer_email'] (string|null) Email of the event organizer
     * - ['meeting_link'] (string|null) Link to online meeting
     * - ['reminders'] (array|null) Event reminders configuration
     * - ['recurrence'] (array|null) Recurrence rules
     * - ['sync_status'] (string) Sync status ('synced', 'pending', 'failed')
     * - ['sync_direction'] (string) Sync direction ('from_external', 'to_external', 'bidirectional')
     * - ['last_synced_at'] (datetime|null) Last sync timestamp
     * - ['external_updated_at'] (datetime|null) External update timestamp
     * - ['sync_error'] (string|null) Sync error message
     * - ['crm_meeting_id'] (int|null) CRM meeting ID if applicable
     * - ['syncable_type'] (string|null) Type of linked model ('App\Models\Appointment', 'App\Models\Task')
     * - ['syncable_id'] (int|null) ID of linked model
     * - ['event_type'] (string) Event type ('event', 'appointment', 'task')
     * @return CalendarEvent The updated CalendarEvent model instance
     * @throws ModelNotFoundException
     */
    public function update(int $id, array $data): CalendarEvent
    {
        $calendarEvent = $this->calendarEventRepository->findById($id);
        if (!$calendarEvent) {
            throw new ModelNotFoundException('Calendar event not found');
        }
        return $this->calendarEventRepository->update($calendarEvent, $data);
    }

    /**
     * Delete a calendar event.
     *
     * @param int $id The ID of the calendar event to delete
     * @return bool True if the deletion was successful, false otherwise
     * @throws ModelNotFoundException
     */
    public function delete(int $id): bool
    {
        $calendarEvent = $this->calendarEventRepository->findById($id);
        if (!$calendarEvent) {
            throw new ModelNotFoundException('Calendar event not found');
        }
        return $this->calendarEventRepository->delete($calendarEvent);
    }
}
