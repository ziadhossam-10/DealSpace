<?php

namespace App\Services\Events;

use App\Models\Event;
use App\Repositories\Events\EventsRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EventService implements EventServiceInterface
{
    protected $eventsRepository;

    public function __construct(EventsRepositoryInterface $eventsRepository)
    {
        $this->eventsRepository = $eventsRepository;
    }

    /**
     * Get all events.
     *
     * @param int $perPage
     * @param int $page
     * @param string|null $search
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAll(int $perPage = 15, int $page = 1, string $search = null, array $filters = [])
    {
        return $this->eventsRepository->getAll($perPage, $page, $search, $filters);
    }

    /**
     * Get an event by ID.
     *
     * @param int $eventId
     * @return Event
     * @throws ModelNotFoundException
     */
    public function findById(int $eventId): Event
    {
        $event = $this->eventsRepository->findById($eventId);
        if (!$event) {
            throw new ModelNotFoundException('Event not found');
        }
        return $event;
    }

    /**
     * Create a new event.
     *
     * @param array $data
     * @return Event
     */
    public function create(array $data): Event
    {
        return DB::transaction(function () use ($data) {
            // Set occurred_at to current time if not provided
            if (empty($data['occurred_at'])) {
                $data['occurred_at'] = now();
            } else {
                $data['occurred_at'] = Carbon::parse($data['occurred_at']);
            }

            return $this->eventsRepository->create($data);
        });
    }

    /**
     * Update an existing event.
     *
     * @param int $eventId
     * @param array $data
     * @return Event
     * @throws ModelNotFoundException
     */
    public function update(int $eventId, array $data): Event
    {
        return DB::transaction(function () use ($eventId, $data) {
            $event = $this->eventsRepository->findById($eventId);
            if (!$event) {
                throw new ModelNotFoundException('Event not found');
            }

            // Parse occurred_at if provided
            if (!empty($data['occurred_at'])) {
                $data['occurred_at'] = Carbon::parse($data['occurred_at']);
            }

            return $this->eventsRepository->update($event, $data);
        });
    }

    /**
     * Delete an event.
     *
     * @param int $eventId
     * @return bool
     * @throws ModelNotFoundException
     */
    public function delete(int $eventId): bool
    {
        $event = $this->eventsRepository->findById($eventId);
        if (!$event) {
            throw new ModelNotFoundException('Event not found');
        }

        return $this->eventsRepository->delete($event);
    }

    /**
     * Deletes multiple events in a single transaction.
     *
     * @param array $params Parameters to control the deletion operation
     * @return int Number of deleted records
     */
    public function bulkDelete(array $params): int
    {
        return DB::transaction(function () use ($params) {
            $isAllSelected = $params['is_all_selected'] ?? false;
            $exceptionIds = $params['exception_ids'] ?? [];
            $ids = $params['ids'] ?? [];

            if ($isAllSelected) {
                if (!empty($exceptionIds)) {
                    return $this->eventsRepository->deleteAllExcept($exceptionIds);
                } else {
                    return $this->eventsRepository->deleteAll();
                }
            } else {
                if (!empty($ids)) {
                    return $this->eventsRepository->deleteSome($ids);
                } else {
                    return 0;
                }
            }
        });
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
        return $this->eventsRepository->getByType($type, $perPage, $page);
    }

    /**
     * Get events by date range.
     *
     * @param string $startDate
     * @param string $endDate
     * @param int $perPage
     * @param int $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getByDateRange(string $startDate, string $endDate, int $perPage = 15, int $page = 1)
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        return $this->eventsRepository->getByDateRange($start, $end, $perPage, $page);
    }
}
