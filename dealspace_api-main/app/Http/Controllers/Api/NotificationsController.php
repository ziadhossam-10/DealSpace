<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notifications\StoreNotificationRequest;
use App\Http\Requests\Notifications\UpdateNotificationRequest;
use App\Http\Requests\Notifications\BulkDeleteNotificationRequest;
use App\Http\Requests\Notifications\SendToTenantRequest;
use App\Http\Requests\Notifications\SendToRoleRequest;
use App\Http\Resources\NotificationCollection;
use App\Http\Resources\NotificationResource;
use App\Services\Notifications\NotificationServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationsController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationServiceInterface $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get all notifications.
     *
     * @return JsonResponse JSON response containing all notifications.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);
        $search = $request->input('search', null);

        $notifications = $this->notificationService->getAll($perPage, $page, $search);

        return successResponse(
            'Notifications retrieved successfully',
            new NotificationCollection($notifications)
        );
    }

    /**
     * Get notifications for the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse JSON response containing user notifications.
     */
    public function getUserNotifications(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);
        $unreadOnly = $request->boolean('unread_only', false);

        $notifications = $this->notificationService->getUserNotifications($userId, $perPage, $page, $unreadOnly);

        return successResponse(
            'User notifications retrieved successfully',
            new NotificationCollection($notifications)
        );
    }

    /**
     * Get a specific notification by ID.
     *
     * @param int $id The ID of the notification to retrieve.
     * @return JsonResponse JSON response containing the notification.
     */
    public function show(int $id): JsonResponse
    {
        $notification = $this->notificationService->findById($id);

        return successResponse(
            'Notification retrieved successfully',
            new NotificationResource($notification)
        );
    }

    /**
     * Create a new notification.
     *
     * @param StoreNotificationRequest $request The request instance containing the data to create a notification.
     * @return JsonResponse JSON response containing the created notification and a 201 status code.
     */
    public function store(StoreNotificationRequest $request): JsonResponse
    {
        $notification = $this->notificationService->create($request->validated());

        return successResponse(
            'Notification created successfully',
            new NotificationResource($notification),
            201
        );
    }

    /**
     * Update an existing notification.
     *
     * @param UpdateNotificationRequest $request The request instance containing the data to update.
     * @param int $id The ID of the notification to update.
     * @return JsonResponse JSON response containing the updated notification.
     */
    public function update(UpdateNotificationRequest $request, int $id): JsonResponse
    {
        $notification = $this->notificationService->update($id, $request->validated());

        return successResponse(
            'Notification updated successfully',
            new NotificationResource($notification)
        );
    }

    /**
     * Delete a notification.
     *
     * @param int $id The ID of the notification to delete.
     * @return JsonResponse JSON response indicating the result of the deletion.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->notificationService->delete($id);

        return successResponse(
            'Notification deleted successfully',
            null
        );
    }

    /**
     * Bulk delete notifications based on provided parameters.
     *
     * @param BulkDeleteNotificationRequest $request
     * @return JsonResponse
     */
    public function bulkDelete(BulkDeleteNotificationRequest $request): JsonResponse
    {
        $deletedCount = $this->notificationService->bulkDelete($request->validated());

        return successResponse(
            'Notifications deleted successfully',
            ['count' => $deletedCount]
        );
    }

    /**
     * Add the authenticated user to a notification's recipients.
     *
     * @param Request $request
     * @param int $notificationId
     * @return JsonResponse
     */
    public function addUser(Request $request, int $notificationId): JsonResponse
    {
        $userId = $request->user()->id;
        $this->notificationService->addUserToNotification($notificationId, $userId);

        return successResponse(
            'User added to notification successfully',
            null
        );
    }

    /**
     * Remove the authenticated user from a notification's recipients.
     *
     * @param Request $request
     * @param int $notificationId
     * @return JsonResponse
     */
    public function removeUser(Request $request, int $notificationId): JsonResponse
    {
        $userId = $request->user()->id;
        $this->notificationService->removeUserFromNotification($notificationId, $userId);

        return successResponse(
            'User removed from notification successfully',
            null
        );
    }

    /**
     * Mark a notification as read for the authenticated user.
     *
     * @param Request $request
     * @param int $notificationId
     * @return JsonResponse
     */
    public function markAsRead(Request $request, int $notificationId): JsonResponse
    {
        $userId = $request->user()->id;
        $this->notificationService->markAsRead($notificationId, $userId);

        return successResponse(
            'Notification marked as read successfully',
            null
        );
    }

    /**
     * Mark all notifications as read for the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $count = $this->notificationService->markAllAsRead($userId);

        return successResponse(
            'All notifications marked as read successfully',
            ['count' => $count]
        );
    }

    /**
     * Get unread notifications count for the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getUnreadCount(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $count = $this->notificationService->getUnreadCount($userId);

        return successResponse(
            'Unread notifications count retrieved successfully',
            ['count' => $count]
        );
    }
}
