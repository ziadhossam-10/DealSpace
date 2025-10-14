<?php

namespace App\Repositories\Calendar;

use App\Models\CalendarEvent;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

interface CalendarEventRepositoryInterface
{
    /**
     * Find a calendar event by its ID.
     *
     * @param int $id The ID of the calendar event to find
     * @return CalendarEvent|null The found calendar event or null if not found
     */
    public function findById(int $id): ?CalendarEvent;

    /**
     * Get all calendar events with optional filtering.
     *
     * @param array $filters Optional filters including start_date, end_date, and other criteria
     * @return Collection Collection of CalendarEvent model instances
     */
    public function getAll(array $filters = []): Collection;

    /**
     * Create a new calendar event record.
     *
     * @param array $data The calendar event data
     * @return CalendarEvent The newly created CalendarEvent model instance
     */
    public function create(array $data): CalendarEvent;

    /**
     * Update an existing calendar event record with new data.
     *
     * @param CalendarEvent $calendarEvent The calendar event instance to update
     * @param array $data The updated calendar event data
     * @return CalendarEvent The updated CalendarEvent model instance
     */
    public function update(CalendarEvent $calendarEvent, array $data): CalendarEvent;

    /**
     * Delete a calendar event record from the database.
     *
     * @param CalendarEvent $calendarEvent The calendar event instance to delete
     * @return bool True if the deletion was successful, false otherwise
     */
    public function delete(CalendarEvent $calendarEvent): bool;
}
