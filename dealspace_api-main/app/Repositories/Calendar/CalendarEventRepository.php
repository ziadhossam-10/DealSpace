<?php

namespace App\Repositories\Calendar;

use App\Models\CalendarEvent;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

class CalendarEventRepository implements CalendarEventRepositoryInterface
{
    /**
     * Find a calendar event by its ID.
     *
     * @param int $id The ID of the calendar event to find
     * @return CalendarEvent|null The found calendar event or null if not found
     */
    public function findById(int $id): ?CalendarEvent
    {
        return CalendarEvent::find($id);
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
        $query = CalendarEvent::query();

        // Apply date range filtering if both start_date and end_date are provided
        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $startDate = $filters['start_date'] instanceof Carbon ? $filters['start_date'] : Carbon::parse($filters['start_date']);
            $endDate = $filters['end_date'] instanceof Carbon ? $filters['end_date'] : Carbon::parse($filters['end_date']);

            // Filter by date range - events that overlap with the requested period
            $query->where(function ($q) use ($startDate, $endDate) {
                $q->where(function ($dateQuery) use ($startDate, $endDate) {
                    // Event starts within the range
                    $dateQuery->whereBetween('start_time', [$startDate, $endDate]);
                })->orWhere(function ($dateQuery) use ($startDate, $endDate) {
                    // Event ends within the range
                    $dateQuery->whereBetween('end_time', [$startDate, $endDate]);
                })->orWhere(function ($dateQuery) use ($startDate, $endDate) {
                    // Event spans the entire range
                    $dateQuery->where('start_time', '<=', $startDate)
                        ->where('end_time', '>=', $endDate);
                });
            });
        }

        // Apply additional filters
        if (isset($filters['calendar_account_id'])) {
            $query->where('calendar_account_id', $filters['calendar_account_id']);
        }

        if (isset($filters['person_id'])) {
            $query->where('person_id', $filters['person_id']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['event_type'])) {
            $query->where('event_type', $filters['event_type']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['sync_status'])) {
            $query->where('sync_status', $filters['sync_status']);
        }

        // Order by start time if date filtering is applied, otherwise use default ordering
        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->orderBy('start_time', 'asc');
        } else {
            $query->orderBy('id', 'desc');
        }

        return $query->get();
    }

    /**
     * Create a new calendar event record.
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
     * - 'syncable_type' (string|null) Type of linked model
     * - 'syncable_id' (int|null) ID of linked model
     * - 'event_type' (string) Event type ('event', 'appointment', 'task')
     * @return CalendarEvent The newly created CalendarEvent model instance
     */
    public function create(array $data): CalendarEvent
    {
        return CalendarEvent::create($data);
    }

    /**
     * Update an existing calendar event record with new data.
     *
     * @param CalendarEvent $calendarEvent The calendar event instance to update
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
     * - ['syncable_type'] (string|null) Type of linked model
     * - ['syncable_id'] (int|null) ID of linked model
     * - ['event_type'] (string) Event type ('event', 'appointment', 'task')
     * @return CalendarEvent The updated CalendarEvent model instance
     */
    public function update(CalendarEvent $calendarEvent, array $data): CalendarEvent
    {
        $calendarEvent->update($data);
        return $calendarEvent;
    }

    /**
     * Delete a calendar event record from the database.
     *
     * @param CalendarEvent $calendarEvent The calendar event instance to delete
     * @return bool True if the deletion was successful, false otherwise
     */
    public function delete(CalendarEvent $calendarEvent): bool
    {
        return $calendarEvent->delete();
    }
}
