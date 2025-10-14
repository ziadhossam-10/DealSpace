<?php

namespace App\Repositories\Tasks;

use App\Models\Task;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TasksRepositoryInterface
{
    /**
     * Get all tasks for a specific person with pagination.
     */
    public function getAll(int $perPage = 15, int $page = 1, int $personId): LengthAwarePaginator;

    /**
     * Get all tasks with optional filtering.
     */
    public function getAllWithOptionalFilters(int $perPage = 15, int $page = 1, ?int $personId = null, ?int $assignedUserId = null, ?bool $isCompleted = null): LengthAwarePaginator;

    /**
     * Find a task by its ID.
     */
    public function findById(int $taskId): ?Task;

    /**
     * Create a new task record.
     */
    public function create(array $data): Task;

    /**
     * Update an existing task record.
     */
    public function update(int $taskId, array $data): Task;

    /**
     * Delete a task record.
     */
    public function delete(int $taskId): bool;

    /**
     * Get tasks for a specific person.
     */
    public function getTasksForPerson(int $personId, int $perPage = 15, int $page = 1): LengthAwarePaginator;

    /**
     * Get tasks assigned to a specific user.
     */
    public function getTasksForUser(int $assignedUserId, int $perPage = 15, int $page = 1): LengthAwarePaginator;

    /**
     * Get tasks due today for a user.
     */
    public function getTodayTasks(?int $assignedUserId = null): array;

    /**
     * Get overdue tasks for a user.
     */
    public function getOverdueTasks(?int $assignedUserId = null): array;

    /**
     * Get future tasks for a user.
     */
    public function getFutureTasks(?int $assignedUserId = null): array;

    /**
     * Get tasks by type.
     */
    public function getTasksByType(string $type, ?int $assignedUserId = null, int $perPage = 15, int $page = 1): LengthAwarePaginator;

    /**
     * Get task statistics for a user.
     */
    public function getTaskStatistics(int $assignedUserId): array;
}
