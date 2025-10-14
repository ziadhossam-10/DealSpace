<?php

namespace App\Repositories\Notifications;

use App\Models\Notification;

interface NotificationsRepositoryInterface
{
    /**
     * Get all notifications with pagination.
     *
     * @param int $perPage Number of items per page
     * @param int $page Current page number
     * @param string|null $search Search term
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator Paginated notification records with relationships loaded.
     */
    public function getAll(int $perPage = 15, int $page = 1, string $search = null);

    /**
     * Get notifications for a specific user with pagination.
     *
     * @param int $userId The ID of the user.
     * @param int $perPage Number of items per page
     * @param int $page Current page number
     * @param bool $unreadOnly Whether to get only unread notifications
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator Paginated notifications for the user.
     */
    public function getByUserId(int $userId, int $perPage = 15, int $page = 1, bool $unreadOnly = false);

    /**
     * Find a notification by its ID.
     *
     * @param int $notificationId The ID of the notification to find.
     * @return Notification|null The found notification or null if not found.
     */
    public function findById(int $notificationId): ?Notification;

    /**
     * Create a new notification record.
     *
     * @param array $data The data for the new notification, including:
     * - 'title' (string) The title of the notification.
     * - 'message' (string) The message content.
     * - 'action' (string|null) Optional action.
     * - 'image' (string|null) Optional image.
     * - 'tenant_id' (string|null) Optional tenant ID.
     * @return Notification The newly created Notification model instance.
     */
    public function create(array $data): Notification;

    /**
     * Update an existing notification.
     *
     * @param Notification $notification The notification to update.
     * @param array $data The updated notification data.
     * @return Notification The updated Notification model instance with fresh relationships.
     */
    public function update(Notification $notification, array $data): Notification;

    /**
     * Delete a notification.
     *
     * @param Notification $notification The notification to delete.
     * @return bool True if deletion was successful, false otherwise.
     */
    public function delete(Notification $notification): bool;

    /**
     * Attach users to a notification.
     *
     * @param Notification $notification The notification to attach users to.
     * @param array $userIds Array of user IDs to attach.
     * @return void
     */
    public function attachUsers(Notification $notification, array $userIds): void;

    /**
     * Detach users from a notification.
     *
     * @param Notification $notification The notification to detach users from.
     * @param array $userIds Array of user IDs to detach.
     * @return void
     */
    public function detachUsers(Notification $notification, array $userIds): void;

    /**
     * Mark a notification as read for a specific user.
     *
     * @param Notification $notification The notification to mark as read.
     * @param int $userId The ID of the user.
     * @return bool True if successful, false otherwise.
     */
    public function markAsRead(Notification $notification, int $userId): bool;

    /**
     * Mark all notifications as read for a specific user.
     *
     * @param int $userId The ID of the user.
     * @return int Number of notifications marked as read.
     */
    public function markAllAsRead(int $userId): int;

    /**
     * Get unread notifications count for a user.
     *
     * @param int $userId The ID of the user.
     * @return int Count of unread notifications.
     */
    public function getUnreadCount(int $userId): int;

    /**
     * Delete all notification records
     *
     * @return int Number of deleted records
     */
    public function deleteAll(): int;

    /**
     * Delete all records except those with specified IDs
     *
     * @param array $ids IDs to exclude from deletion
     * @return int Number of deleted records
     */
    public function deleteAllExcept(array $ids): int;

    /**
     * Delete multiple records by their IDs
     *
     * @param array $ids IDs of records to delete
     * @return int Number of deleted records
     */
    public function deleteSome(array $ids): int;
}
