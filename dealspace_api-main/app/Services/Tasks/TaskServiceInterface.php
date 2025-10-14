<?php

namespace App\Services\Tasks;

use App\Models\Task;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TaskServiceInterface
{
    /**
     * Get all tasks with optional filtering.
     */
    public function getAll(int $perPage = 15, int $page = 1, $personId = null, $assignedUserId = null, $isCompleted = null);

    /**
     * Get a task by ID.
     */
    public function findById(int $taskId): Task;

    /**
     * Create a new task.
     */
    public function create(array $data): Task;

    /**
     * Update an existing task.
     */
    public function update(int $taskId, array $data): Task;

    /**
     * Mark a task as completed.
     */
    public function markAsCompleted(int $taskId): Task;

    /**
     * Mark a task as incomplete.
     */
    public function markAsIncomplete(int $taskId): Task;

    /**
     * Delete a task.
     */
    public function delete(int $taskId): bool;

    /**
     * Get tasks due soon (within next 24 hours).
     */
    /**
     * Get tasks due today for a user.
     */
    public function getTodayTasks(int $assignedUserId = null): array;

    /**
     * Get overdue tasks for a user.
     */
    public function getOverdueTasks(int $assignedUserId = null): array;

    /**
     * Get future tasks for a user.
     */
    public function getFutureTasks(int $assignedUserId = null): array;

    /**
     * Get tasks for a specific person.
     */
    public function getTasksForPerson(int $personId, int $perPage = 15, int $page = 1): LengthAwarePaginator;

    /**
     * Get tasks assigned to a specific user.
     */
    public function getTasksForUser(int $assignedUserId, int $perPage = 15, int $page = 1): LengthAwarePaginator;
}
