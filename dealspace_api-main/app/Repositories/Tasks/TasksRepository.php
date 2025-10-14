<?php

namespace App\Repositories\Tasks;

use App\Models\Task;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TasksRepository implements TasksRepositoryInterface
{
    protected $model;

    public function __construct(Task $model)
    {
        $this->model = $model;
    }

    /**
     * Get all tasks for a specific person with pagination.
     *
     * @param int $perPage Number of items per page
     * @param int $page Current page number
     * @param int $personId The ID of the person associated with the tasks
     * @return LengthAwarePaginator Paginated task records
     */
    public function getAll(int $perPage = 15, int $page = 1, int $personId): LengthAwarePaginator
    {
        return $this->model->with(['person', 'assignedUser'])
            ->where('person_id', $personId)
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get all tasks with optional filtering.
     *
     * @param int $perPage Number of items per page
     * @param int $page Current page number
     * @param int|null $personId Optional person ID filter
     * @param int|null $assignedUserId Optional assigned user ID filter
     * @param bool|null $isCompleted Optional completion status filter
     * @return LengthAwarePaginator Paginated task records
     */
    public function getAllWithOptionalFilters(int $perPage = 15, int $page = 1, ?int $personId = null, ?int $assignedUserId = null, ?bool $isCompleted = null): LengthAwarePaginator
    {
        $query = $this->model->with(['person', 'assignedUser'])
            ->orderBy('due_date', 'asc')
            ->orderBy('created_at', 'desc');

        if ($personId) {
            $query->where('person_id', $personId);
        }

        if ($assignedUserId) {
            $query->where('assigned_user_id', $assignedUserId);
        }

        if ($isCompleted !== null) {
            $query->where('is_completed', $isCompleted);
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Find a task by its ID.
     *
     * @param int $taskId The ID of the task to find.
     * @return Task|null The found Task instance or null if not found.
     */
    public function findById(int $taskId): ?Task
    {
        return $this->model->with(['person', 'assignedUser'])->find($taskId);
    }

    /**
     * Create a new task record.
     *
     * @param array $data The data for the new task. Example fields:
     * - 'person_id' (int): Required. The person related to the task.
     * - 'assigned_user_id' (int): Required. The user assigned to the task.
     * - 'name' (string): Required. The name of the task.
     * - 'type' (string): Required. The type of task.
     * - 'is_completed' (bool): Optional. Whether the task is completed (defaults to false).
     * - 'due_date' (string): Optional. Due date in YYYY-MM-DD format.
     * - 'due_date_time' (string): Optional. Due date with time and timezone.
     * - 'remind_seconds_before' (int): Optional. Reminder seconds before due time.
     * @return Task The newly created Task model instance.
     */
    public function create(array $data): Task
    {
        return $this->model->create($data)->fresh(['person', 'assignedUser']);
    }

    /**
     * Update an existing task record.
     *
     * @param int $taskId The ID of the task to update
     * @param array $data The data to update
     * @return Task The updated Task model instance.
     */
    public function update(int $taskId, array $data): Task
    {
        $task = $this->model->findOrFail($taskId);
        $task->update($data);
        return $task->fresh(['person', 'assignedUser']);
    }

    /**
     * Delete a task record.
     *
     * @param int $taskId The ID of the task to delete
     * @return bool True if deletion was successful
     */
    public function delete(int $taskId): bool
    {
        $task = $this->model->findOrFail($taskId);
        return $task->delete();
    }

    /**
     * Get tasks for a specific person.
     *
     * @param int $personId The person ID
     * @param int $perPage Number of items per page
     * @param int $page Current page number
     * @return LengthAwarePaginator Paginated task records
     */
    public function getTasksForPerson(int $personId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->model->with(['person', 'assignedUser'])
            ->where('person_id', $personId)
            ->orderBy('due_date', 'asc')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get tasks assigned to a specific user.
     *
     * @param int $assignedUserId The assigned user ID
     * @param int $perPage Number of items per page
     * @param int $page Current page number
     * @return LengthAwarePaginator Paginated task records
     */
    public function getTasksForUser(int $assignedUserId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->model->with(['person', 'assignedUser'])
            ->where('assigned_user_id', $assignedUserId)
            ->orderBy('due_date', 'asc')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get tasks due today for a user.
     *
     * @param int|null $assignedUserId Optional user ID filter
     * @return array Collection of tasks due today
     */
    public function getTodayTasks(?int $assignedUserId = null): array
    {
        $query = $this->model->with(['person', 'assignedUser'])
            ->where('is_completed', false)
            ->where(function ($q) {
                $q->whereDate('due_date', Carbon::today())
                    ->orWhereDate('due_date_time', Carbon::today());
            })
            ->orderBy('due_date_time', 'asc')
            ->orderBy('due_date', 'asc');

        if ($assignedUserId) {
            $query->where('assigned_user_id', $assignedUserId);
        }

        return $query->get()->toArray();
    }

    /**
     * Get overdue tasks for a user.
     *
     * @param int|null $assignedUserId Optional user ID filter
     * @return array Collection of overdue tasks
     */
    public function getOverdueTasks(?int $assignedUserId = null): array
    {
        $query = $this->model->with(['person', 'assignedUser'])
            ->where('is_completed', false)
            ->where(function ($q) {
                $q->where(function ($subQuery) {
                    // Tasks with due_date_time that are past
                    $subQuery->whereNotNull('due_date_time')
                        ->where('due_date_time', '<', Carbon::now());
                })->orWhere(function ($subQuery) {
                    // Tasks with only due_date that are past (and no due_date_time)
                    $subQuery->whereNull('due_date_time')
                        ->whereNotNull('due_date')
                        ->where('due_date', '<', Carbon::today());
                });
            })
            ->orderBy('due_date_time', 'asc')
            ->orderBy('due_date', 'asc');

        if ($assignedUserId) {
            $query->where('assigned_user_id', $assignedUserId);
        }

        return $query->get()->toArray();
    }

    /**
     * Get future tasks for a user.
     *
     * @param int|null $assignedUserId Optional user ID filter
     * @return array Collection of future tasks
     */
    public function getFutureTasks(?int $assignedUserId = null): array
    {
        $query = $this->model->with(['person', 'assignedUser'])
            ->where('is_completed', false)
            ->where(function ($q) {
                $q->where(function ($subQuery) {
                    // Tasks with due_date_time that are in the future
                    $subQuery->whereNotNull('due_date_time')
                        ->where('due_date_time', '>', Carbon::now());
                })->orWhere(function ($subQuery) {
                    // Tasks with only due_date that are in the future (and no due_date_time)
                    $subQuery->whereNull('due_date_time')
                        ->whereNotNull('due_date')
                        ->where('due_date', '>', Carbon::today());
                });
            })
            ->orderBy('due_date_time', 'asc')
            ->orderBy('due_date', 'asc');

        if ($assignedUserId) {
            $query->where('assigned_user_id', $assignedUserId);
        }

        return $query->get()->toArray();
    }

    /**
     * Get tasks by type.
     *
     * @param string $type The task type
     * @param int|null $assignedUserId Optional user ID filter
     * @param int $perPage Number of items per page
     * @param int $page Current page number
     * @return LengthAwarePaginator Paginated task records
     */
    public function getTasksByType(string $type, ?int $assignedUserId = null, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        $query = $this->model->with(['person', 'assignedUser'])
            ->where('type', $type)
            ->orderBy('due_date', 'asc')
            ->orderBy('created_at', 'desc');

        if ($assignedUserId) {
            $query->where('assigned_user_id', $assignedUserId);
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get task statistics for a user.
     *
     * @param int $assignedUserId The assigned user ID
     * @return array Statistics array
     */
    public function getTaskStatistics(int $assignedUserId): array
    {
        $baseQuery = $this->model->where('assigned_user_id', $assignedUserId);

        return [
            'total_tasks' => $baseQuery->count(),
            'completed_tasks' => $baseQuery->where('is_completed', true)->count(),
            'pending_tasks' => $baseQuery->where('is_completed', false)->count(),
            'overdue_tasks' => $baseQuery->where('is_completed', false)
                ->where(function ($q) {
                    $q->where('due_date_time', '<', Carbon::now())
                        ->orWhere('due_date', '<', Carbon::now()->toDateString());
                })->count(),
            'due_today' => $baseQuery->where('is_completed', false)
                ->whereDate('due_date', Carbon::today())
                ->count(),
        ];
    }
}
