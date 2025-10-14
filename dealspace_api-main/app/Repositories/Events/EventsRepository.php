<?php

namespace App\Repositories\Events;

use App\Models\Event;
use Carbon\Carbon;

class EventsRepository implements EventsRepositoryInterface
{
    protected $model;

    public function __construct(Event $model)
    {
        $this->model = $model;
    }

    /**
     * Get all events with pagination and optional search/filters.
     *
     * @param int $perPage Number of items per page
     * @param int $page Current page number
     * @param string|null $search Search term
     * @param array $filters Additional filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAll(int $perPage = 15, int $page = 1, string $search = null, array $filters = [])
    {
        $eventQuery = $this->model->query();

        // Apply search
        if ($search) {
            $eventQuery->where(function ($query) use ($search) {
                $query->where('type', 'like', "%{$search}%")
                    ->orWhere('source', 'like', "%{$search}%")
                    ->orWhere('system', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Apply filters
        if (!empty($filters['type'])) {
            $eventQuery->where('type', $filters['type']);
        }

        if (!empty($filters['source'])) {
            $eventQuery->where('source', $filters['source']);
        }

        if (!empty($filters['system'])) {
            $eventQuery->where('system', $filters['system']);
        }

        if (!empty($filters['date_from'])) {
            $eventQuery->whereDate('occurred_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $eventQuery->whereDate('occurred_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['person_id'])) {
            $eventQuery->where('person_id', $filters['person_id']);
        }

        return $eventQuery->orderBy('occurred_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Find an event by its ID.
     *
     * @param int $eventId The ID of the event to find.
     * @return Event|null The found event or null if not found.
     */
    public function findById(int $eventId): ?Event
    {
        return $this->model->find($eventId);
    }

    /**
     * Create a new event record.
     *
     * @param array $data The data for the new event
     * @return Event The newly created Event model instance.
     */
    public function create(array $data): Event
    {
        return $this->model->create($data);
    }

    /**
     * Update an existing event.
     *
     * @param Event $event The event to update.
     * @param array $data The updated event data
     * @return Event The updated Event model instance.
     */
    public function update(Event $event, array $data): Event
    {
        $event->update($data);
        return $event->fresh();
    }

    /**
     * Delete an event.
     *
     * @param Event $event The event to delete.
     * @return bool True if deletion was successful, false otherwise.
     */
    public function delete(Event $event): bool
    {
        return $event->delete();
    }

    /**
     * Delete all event records
     *
     * @return int Number of deleted records
     */
    public function deleteAll(): int
    {
        return $this->model->query()->delete();
    }

    /**
     * Delete all records except those with specified IDs
     *
     * @param array $ids IDs to exclude from deletion
     * @return int Number of deleted records
     */
    public function deleteAllExcept(array $ids): int
    {
        return $this->model->whereNotIn('id', $ids)->delete();
    }

    /**
     * Delete multiple records by their IDs
     *
     * @param array $ids IDs of records to delete
     * @return int Number of deleted records
     */
    public function deleteSome(array $ids): int
    {
        return $this->model->whereIn('id', $ids)->delete();
    }

    /**
     * Get events by type.
     *
     * @param string $type
     * @param int $perPage
     * @param int $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getByType(string $type, int $perPage = 15, int $page = 1)
    {
        return $this->model->where('type', $type)
            ->orderBy('occurred_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get events by date range.
     *
     * @param \Carbon\Carbon $startDate
     * @param \Carbon\Carbon $endDate
     * @param int $perPage
     * @param int $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getByDateRange(Carbon $startDate, Carbon $endDate, int $perPage = 15, int $page = 1)
    {
        return $this->model->whereBetween('occurred_at', [$startDate, $endDate])
            ->orderBy('occurred_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }
}
