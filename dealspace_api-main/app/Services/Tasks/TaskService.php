<?php

namespace App\Services\Tasks;

use App\Models\Person;
use App\Models\Task;
use App\Models\User;
use App\Repositories\Tasks\TasksRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TaskService implements TaskServiceInterface
{
    protected $taskRepository;

    public function __construct(TasksRepositoryInterface $taskRepository)
    {
        $this->taskRepository = $taskRepository;
    }

    /**
     * Get all tasks with optional filtering.
     */
    public function getAll(int $perPage = 15, int $page = 1, $personId = null, $assignedUserId = null, $isCompleted = null)
    {
        return $this->taskRepository->getAllWithOptionalFilters($perPage, $page, $personId, $assignedUserId, $isCompleted);
    }

    /**
     * Get a task by ID.
     */
    public function findById(int $taskId): Task
    {
        $task = $this->taskRepository->findById($taskId);

        if (!$task) {
            throw new Exception('Task not found');
        }

        return $task;
    }

    /**
     * Create a new task.
     */
    public function create(array $data): Task
    {
        return DB::transaction(function () use ($data) {
            try {
                // Validate the task data
                $this->validateTaskData($data);

                // Set default values
                $data['is_completed'] = $data['is_completed'] ?? false;

                // Create the task record
                $task = $this->taskRepository->create($data);

                Log::info('Task created successfully', [
                    'task_id' => $task->id,
                    'person_id' => $task->person_id,
                    'assigned_user_id' => $task->assigned_user_id
                ]);

                return $task->load(['person', 'assignedUser']);
            } catch (Exception $e) {
                Log::error('Failed to create task', [
                    'error' => $e->getMessage(),
                    'data' => $data
                ]);

                throw $e;
            }
        });
    }

    /**
     * Update an existing task.
     */
    public function update(int $taskId, array $data): Task
    {
        return DB::transaction(function () use ($taskId, $data) {
            try {
                $task = $this->findById($taskId);

                // Validate the task data if provided
                if (isset($data['person_id']) || isset($data['assigned_user_id']) || isset($data['name']) || isset($data['type'])) {
                    $this->validateTaskData($data, false);
                }

                // Update the task
                $updatedTask = $this->taskRepository->update($taskId, $data);

                Log::info('Task updated successfully', [
                    'task_id' => $taskId,
                    'updated_fields' => array_keys($data)
                ]);

                return $updatedTask;
            } catch (Exception $e) {
                Log::error('Failed to update task', [
                    'task_id' => $taskId,
                    'error' => $e->getMessage(),
                    'data' => $data
                ]);

                throw $e;
            }
        });
    }

    /**
     * Mark a task as completed.
     */
    public function markAsCompleted(int $taskId): Task
    {
        return $this->update($taskId, ['is_completed' => true]);
    }

    /**
     * Mark a task as incomplete.
     */
    public function markAsIncomplete(int $taskId): Task
    {
        return $this->update($taskId, ['is_completed' => false]);
    }

    /**
     * Delete a task.
     */
    public function delete(int $taskId): bool
    {
        return DB::transaction(function () use ($taskId) {
            try {
                $task = $this->findById($taskId);

                $deleted = $this->taskRepository->delete($taskId);

                Log::info('Task deleted successfully', [
                    'task_id' => $taskId
                ]);

                return $deleted;
            } catch (Exception $e) {
                Log::error('Failed to delete task', [
                    'task_id' => $taskId,
                    'error' => $e->getMessage()
                ]);

                throw $e;
            }
        });
    }

    /**
     * Get tasks due today for a user.
     */
    public function getTodayTasks(int $assignedUserId = null): array
    {
        return $this->taskRepository->getTodayTasks($assignedUserId);
    }

    /**
     * Get overdue tasks for a user.
     */
    public function getOverdueTasks(int $assignedUserId = null): array
    {
        return $this->taskRepository->getOverdueTasks($assignedUserId);
    }

    /**
     * Get future tasks for a user.
     */
    public function getFutureTasks(int $assignedUserId = null): array
    {
        return $this->taskRepository->getFutureTasks($assignedUserId);
    }

    /**
     * Get tasks for a specific person.
     */
    public function getTasksForPerson(int $personId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->taskRepository->getTasksForPerson($personId, $perPage, $page);
    }

    /**
     * Get tasks assigned to a specific user.
     */
    public function getTasksForUser(int $assignedUserId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->taskRepository->getTasksForUser($assignedUserId, $perPage, $page);
    }

    /**
     * Validate task data.
     */
    protected function validateTaskData(array $data, bool $isCreating = true): void
    {
        if ($isCreating) {
            // Required fields for creation
            if (empty($data['person_id'])) {
                throw new \InvalidArgumentException('Person ID is required');
            }

            if (empty($data['assigned_user_id'])) {
                throw new \InvalidArgumentException('Assigned user ID is required');
            }

            if (empty($data['name'])) {
                throw new \InvalidArgumentException('Task name is required');
            }

            if (empty($data['type'])) {
                throw new \InvalidArgumentException('Task type is required');
            }
        }

        // Validate person exists
        if (isset($data['person_id']) && !Person::find($data['person_id'])) {
            throw new \InvalidArgumentException('Person not found');
        }

        // Validate assigned user exists
        if (isset($data['assigned_user_id']) && !User::find($data['assigned_user_id'])) {
            throw new \InvalidArgumentException('Assigned user not found');
        }

        // Validate task type
        if (isset($data['type'])) {
            $validTypes = [
                'Follow Up',
                'Call',
                'Text',
                'Email',
                'Appointment',
                'Showing',
                'Closing',
                'Open House',
                'Thank You'
            ];

            if (!in_array($data['type'], $validTypes)) {
                throw new \InvalidArgumentException('Invalid task type. Must be one of: ' . implode(', ', $validTypes));
            }
        }

        // Validate due date format
        if (isset($data['due_date']) && !empty($data['due_date'])) {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['due_date'])) {
                throw new \InvalidArgumentException('Due date must be in YYYY-MM-DD format');
            }
        }

        // Validate due date time format
        if (isset($data['due_date_time']) && !empty($data['due_date_time'])) {
            if (!strtotime($data['due_date_time'])) {
                throw new \InvalidArgumentException('Due date time must be a valid date/time format');
            }
        }

        // Validate remind seconds before
        if (isset($data['remind_seconds_before']) && !empty($data['remind_seconds_before'])) {
            if (!is_numeric($data['remind_seconds_before']) || $data['remind_seconds_before'] < 0) {
                throw new \InvalidArgumentException('Remind seconds before must be a positive integer');
            }

            // Check if due_date_time is set when reminder is requested
            if (empty($data['due_date_time'])) {
                throw new \InvalidArgumentException('Due date time is required when setting a reminder');
            }
        }
    }
}
