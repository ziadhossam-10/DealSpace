<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tasks\GetTasksRequest;
use App\Http\Requests\Tasks\StoreTaskRequest;
use App\Http\Requests\Tasks\UpdateTaskRequest;
use App\Http\Resources\TaskCollection;
use App\Http\Resources\TaskResource;
use App\Services\Tasks\TaskServiceInterface;
use Illuminate\Http\JsonResponse;

class TaskController extends Controller
{
    protected $taskService;

    public function __construct(TaskServiceInterface $taskService)
    {
        $this->taskService = $taskService;
    }

    /**
     * Get all tasks with optional filtering.
     *
     * @param GetTasksRequest $request
     * @return JsonResponse JSON response containing all tasks.
     */
    public function index(GetTasksRequest $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);
        $personId = $request->input('person_id', null);
        $assignedUserId = $request->input('assigned_user_id', null);
        $isCompleted = $request->input('is_completed', null);

        $tasks = $this->taskService->getAll($perPage, $page, $personId, $assignedUserId, $isCompleted);

        return successResponse(
            'Tasks retrieved successfully',
            new TaskCollection($tasks)
        );
    }

    /**
     * Get a specific task by ID.
     *
     * @param int $id The ID of the task to retrieve.
     * @return JsonResponse JSON response containing the task.
     */
    public function show(int $id): JsonResponse
    {
        $task = $this->taskService->findById($id);

        return successResponse(
            'Task retrieved successfully',
            new TaskResource($task)
        );
    }

    /**
     * Create a new task.
     *
     * @param StoreTaskRequest $request The request instance containing the data to create a task.
     * @return JsonResponse JSON response containing the created task and a 201 status code.
     */
    public function store(StoreTaskRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['assigned_user_id'] = $data['assigned_user_id'] ?? $request->user()->id;

        $task = $this->taskService->create($data);

        return successResponse(
            'Task created successfully',
            new TaskResource($task),
            201
        );
    }

    /**
     * Update an existing task.
     *
     * @param UpdateTaskRequest $request The request instance containing the data to update the task.
     * @param int $id The ID of the task to update.
     * @return JsonResponse JSON response containing the updated task.
     */
    public function update(UpdateTaskRequest $request, int $id): JsonResponse
    {
        $data = $request->validated();
        $task = $this->taskService->update($id, $data);

        return successResponse(
            'Task updated successfully',
            new TaskResource($task)
        );
    }

    /**
     * Mark a task as completed.
     *
     * @param int $id The ID of the task to mark as completed.
     * @return JsonResponse JSON response containing the updated task.
     */
    public function markAsCompleted(int $id): JsonResponse
    {
        $task = $this->taskService->markAsCompleted($id);

        return successResponse(
            'Task marked as completed successfully',
            new TaskResource($task)
        );
    }

    /**
     * Mark a task as incomplete.
     *
     * @param int $id The ID of the task to mark as incomplete.
     * @return JsonResponse JSON response containing the updated task.
     */
    public function markAsIncomplete(int $id): JsonResponse
    {
        $task = $this->taskService->markAsIncomplete($id);

        return successResponse(
            'Task marked as incomplete successfully',
            new TaskResource($task)
        );
    }

    /**
     * Delete a task.
     *
     * @param int $id The ID of the task to delete.
     * @return JsonResponse JSON response confirming deletion.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->taskService->delete($id);

        return successResponse(
            'Task deleted successfully',
            null,
            204
        );
    }

    /**
     * Get tasks due today.
     *
     * @param GetTasksRequest $request
     * @return JsonResponse JSON response containing today's tasks.
     */
    public function todayTasks(GetTasksRequest $request): JsonResponse
    {
        $assignedUserId = $request->input('assigned_user_id', null);
        $tasks = $this->taskService->getTodayTasks($assignedUserId);

        return successResponse(
            'Today\'s tasks retrieved successfully',
            TaskResource::collection($tasks)
        );
    }

    /**
     * Get overdue tasks.
     *
     * @param GetTasksRequest $request
     * @return JsonResponse JSON response containing overdue tasks.
     */
    public function overdueTasks(GetTasksRequest $request): JsonResponse
    {
        $assignedUserId = $request->input('assigned_user_id', null);
        $tasks = $this->taskService->getOverdueTasks($assignedUserId);

        return successResponse(
            'Overdue tasks retrieved successfully',
            TaskResource::collection($tasks)
        );
    }

    /**
     * Get future tasks.
     *
     * @param GetTasksRequest $request
     * @return JsonResponse JSON response containing future tasks.
     */
    public function futureTasks(GetTasksRequest $request): JsonResponse
    {
        $assignedUserId = $request->input('assigned_user_id', null);
        $tasks = $this->taskService->getFutureTasks($assignedUserId);

        return successResponse(
            'Future tasks retrieved successfully',
            TaskResource::collection($tasks)
        );
    }

    /**
     * Get tasks for a specific person.
     *
     * @param GetTasksRequest $request
     * @param int $personId The ID of the person.
     * @return JsonResponse JSON response containing tasks for the person.
     */
    public function tasksForPerson(GetTasksRequest $request, int $personId): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);

        $tasks = $this->taskService->getTasksForPerson($personId, $perPage, $page);

        return successResponse(
            'Tasks for person retrieved successfully',
            new TaskCollection($tasks)
        );
    }

    /**
     * Get tasks assigned to a specific user.
     *
     * @param GetTasksRequest $request
     * @param int $userId The ID of the user.
     * @return JsonResponse JSON response containing tasks assigned to the user.
     */
    public function tasksForUser(GetTasksRequest $request, int $userId): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);

        $tasks = $this->taskService->getTasksForUser($userId, $perPage, $page);

        return successResponse(
            'Tasks for user retrieved successfully',
            new TaskCollection($tasks)
        );
    }
}
