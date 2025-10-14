<?php

namespace App\Services\Notifications;

use App\Models\Notification;

interface NotificationServiceInterface
{
    /**
     * Get all notifications.
     *
     * @param int $perPage
     * @param int $page
     * @param string|null $search
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAll(int $perPage = 15, int $page = 1, string $search = null);

    /**
     * Get notifications for a specific user.
     *
     * @param int $userId
     * @param int $perPage
     * @param int $page
     * @param bool $unreadOnly
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getUserNotifications(int $userId, int $perPage = 15, int $page = 1, bool $unreadOnly = false);

    /**
     * Get a notification by ID.
     *
     * @param int $notificationId
     * @return Notification
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findById(int $notificationId): Notification;

    /**
     * Create a new notification with users.
     *
     * @param array $data The complete notification data including:
     * - 'title' (string) The title of the notification
     * - 'message' (string) The message content
     * - 'action' (string|null) Optional action
     * - 'image' (string|null) Optional image
     * - 'tenant_id' (string|null) Optional tenant ID
     * - ['user_ids'] (array) Array of user IDs to send notification to
     * @return Notification
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function create(array $data): Notification;

    /**
     * Update an existing notification and its users.
     *
     * @param int $notificationId
     * @param array $data The complete notification data including:
     * - Notification fields to update
     * - ['user_ids'] (array) Array of user IDs to add to the notification
     * - ['user_ids_to_remove'] (array) Array of user IDs to remove from the notification
     * @return Notification
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function update(int $notificationId, array $data): Notification;

    /**
     * Delete a notification.
     *
     * @param int $notificationId
     * @return bool
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function delete(int $notificationId): bool;

    /**
     * Deletes multiple notifications in a single transaction.
     *
     * @param array $params Parameters to control the deletion operation
     *     - is_all_selected (bool): Delete all notifications except those in exception_ids
     *     - exception_ids (array): IDs to exclude from deletion
     *     - ids (array): IDs of notifications to delete
     * @return int Number of deleted records
     */
    public function bulkDelete(array $params): int;

    /**
     * Add a user to a notification's recipients.
     *
     * @param int $notificationId
     * @param int $userId
     * @return void
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function addUserToNotification(int $notificationId, int $userId): void;

    /**
     * Remove a user from a notification's recipients.
     *
     * @param int $notificationId
     * @param int $userId
     * @return void
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function removeUserFromNotification(int $notificationId, int $userId): void;

    /**
     * Mark a notification as read for a specific user.
     *
     * @param int $notificationId
     * @param int $userId
     * @return bool
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function markAsRead(int $notificationId, int $userId): bool;

    /**
     * Mark all notifications as read for a specific user.
     *
     * @param int $userId
     * @return int Number of notifications marked as read
     */
    public function markAllAsRead(int $userId): int;

    /**
     * Get unread notifications count for a user.
     *
     * @param int $userId
     * @return int Count of unread notifications
     */
    public function getUnreadCount(int $userId): int;
}
