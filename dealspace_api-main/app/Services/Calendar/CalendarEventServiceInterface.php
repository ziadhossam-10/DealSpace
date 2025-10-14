<?php

namespace App\Services\Calendar;

use App\Models\CalendarEvent;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

interface CalendarEventServiceInterface
{
    /**
     * Get all calendar events with optional filtering.
     *
     * @param array $filters Optional filters including start_date, end_date, and other criteria
     * @return Collection Collection of CalendarEvent model instances
     */
    public function getAll(array $filters = []): Collection;

    /**
     * Get a specific calendar event by ID.
     *
     * @param int $id The ID of the calendar event to retrieve
     * @return CalendarEvent The CalendarEvent model instance
     */
    public function findById(int $id): CalendarEvent;

    /**
     * Create a new calendar event.
     *
     * @param array $data The calendar event data
     * @return CalendarEvent The newly created CalendarEvent model instance
     */
    public function create(array $data): CalendarEvent;

    /**
     * Update an existing calendar event.
     *
     * @param int $id The ID of the calendar event to update
     * @param array $data The updated calendar event data
     * @return CalendarEvent The updated CalendarEvent model instance
     */
    public function update(int $id, array $data): CalendarEvent;

    /**
     * Delete a calendar event.
     *
     * @param int $id The ID of the calendar event to delete
     * @return bool True if the deletion was successful, false otherwise
     */
    public function delete(int $id): bool;
}
