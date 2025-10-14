<?php

namespace App\Repositories\Events;

use App\Models\Event;

interface EventsRepositoryInterface
{
    /**
     * Get all events with pagination and optional search.
     *
     * @param int $perPage
     * @param int $page
     * @param string|null $search
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAll(int $perPage = 15, int $page = 1, string $search = null, array $filters = []);

    /**
     * Find an event by its ID.
     *
     * @param int $eventId
     * @return Event|null
     */
    public function findById(int $eventId): ?Event;

    /**
     * Create a new event record.
     *
     * @param array $data
     * @return Event
     */
    public function create(array $data): Event;

    /**
     * Update an existing event.
     *
     * @param Event $event
     * @param array $data
     * @return Event
     */
    public function update(Event $event, array $data): Event;

    /**
     * Delete an event.
     *
     * @param Event $event
     * @return bool
     */
    public function delete(Event $event): bool;

    /**
     * Delete all event records.
     *
     * @return int
     */
    public function deleteAll(): int;

    /**
     * Delete all records except those with specified IDs.
     *
     * @param array $ids
     * @return int
     */
    public function deleteAllExcept(array $ids): int;

    /**
     * Delete multiple records by their IDs.
     *
     * @param array $ids
     * @return int
     */
    public function deleteSome(array $ids): int;

    /**
     * Get events by type.
     *
     * @param string $type
     * @param int $perPage
     * @param int $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getByType(string $type, int $perPage = 15, int $page = 1);

    /**
     * Get events by date range.
     *
     * @param \Carbon\Carbon $startDate
     * @param \Carbon\Carbon $endDate
     * @param int $perPage
     * @param int $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getByDateRange(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate, int $perPage = 15, int $page = 1);
}
