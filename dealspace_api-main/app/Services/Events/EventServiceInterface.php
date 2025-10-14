<?php

namespace App\Services\Events;

use App\Models\Event;

interface EventServiceInterface
{
    /**
     * Get all events.
     *
     * @param int $perPage
     * @param int $page
     * @param string|null $search
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAll(int $perPage = 15, int $page = 1, string $search = null, array $filters = []);

    /**
     * Get an event by ID.
     *
     * @param int $eventId
     * @return Event
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findById(int $eventId): Event;

    /**
     * Create a new event.
     *
     * @param array $data
     * @return Event
     */
    public function create(array $data): Event;

    /**
     * Update an existing event.
     *
     * @param int $eventId
     * @param array $data
     * @return Event
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function update(int $eventId, array $data): Event;

    /**
     * Delete an event.
     *
     * @param int $eventId
     * @return bool
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function delete(int $eventId): bool;

    /**
     * Bulk delete events.
     *
     * @param array $params
     * @return int
     */
    public function bulkDelete(array $params): int;

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
     * @param string $startDate
     * @param string $endDate
     * @param int $perPage
     * @param int $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getByDateRange(string $startDate, string $endDate, int $perPage = 15, int $page = 1);
}
