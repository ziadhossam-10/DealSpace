<?php

namespace App\Services\Notifications;

use App\Jobs\SendNotificationJob;
use App\Models\Notification;
use App\Models\User;
use App\Repositories\Notifications\NotificationsRepositoryInterface;
use App\Repositories\Users\UsersRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class NotificationService implements NotificationServiceInterface
{
    protected $notificationRepository;
    protected $usersRepository;

    public function __construct(
        NotificationsRepositoryInterface $notificationRepository,
        UsersRepositoryInterface $usersRepository
    ) {
        $this->notificationRepository = $notificationRepository;
        $this->usersRepository = $usersRepository;
    }

    /**
     * Get all notifications.
     *
     * @param int $perPage
     * @param int $page
     * @param string|null $search
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAll(int $perPage = 15, int $page = 1, string $search = null)
    {
        return $this->notificationRepository->getAll($perPage, $page, $search);
    }

    /**
     * Get notifications for a specific user.
     *
     * @param int $userId
     * @param int $perPage
     * @param int $page
     * @param bool $unreadOnly
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getUserNotifications(int $userId, int $perPage = 15, int $page = 1, bool $unreadOnly = false)
    {
        return $this->notificationRepository->getByUserId($userId, $perPage, $page, $unreadOnly);
    }

    /**
     * Get a notification by ID.
     *
     * @param int $notificationId
     * @return Notification
     * @throws ModelNotFoundException
     */
    public function findById(int $notificationId): Notification
    {
        $notification = $this->notificationRepository->findById($notificationId);
        if (!$notification) {
            throw new ModelNotFoundException('Notification not found');
        }
        return $notification;
    }

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
     * @throws ModelNotFoundException
     */
    public function create(array $data): Notification
    {
        return DB::transaction(function () use ($data) {
            // Extract user-related arrays
            $userIds = $data['user_ids'] ?? [];

            // Remove user arrays from data to prevent SQL errors
            unset($data['user_ids']);

            // Verify that users exist before creating the notification
            if (!empty($userIds)) {
                $this->validateUsers($userIds);
            }

            // Create the notification
            $notification = $this->notificationRepository->create($data);

            // Add users to notification if user_ids array is provided
            if (!empty($userIds)) {
                $this->addUsersToNotification($notification->id, $userIds);
            }

            // Dispatch the notification job to send notifications
            foreach ($userIds as $userId) {
                SendNotificationJob::dispatch($notification->toArray(), $userId);
            }

            return $notification->fresh(['users', 'tenant']);
        });
    }

    /**
     * Update an existing notification and its users.
     *
     * @param int $notificationId
     * @param array $data The complete notification data including:
     * - Notification fields to update
     * - ['user_ids'] (array) Array of user IDs to add to the notification
     * - ['user_ids_to_remove'] (array) Array of user IDs to remove from the notification
     * @return Notification
     * @throws ModelNotFoundException
     */
    public function update(int $notificationId, array $data): Notification
    {
        return DB::transaction(function () use ($notificationId, $data) {
            $notification = $this->notificationRepository->findById($notificationId);
            if (!$notification) {
                throw new ModelNotFoundException('Notification not found');
            }

            // Extract user-related arrays
            $userIdsToAdd = $data['user_ids'] ?? [];
            $userIdsToRemove = $data['user_ids_to_remove'] ?? [];

            // Remove user arrays from data to prevent SQL errors
            unset($data['user_ids'], $data['user_ids_to_remove']);

            // Validate users if provided
            if (!empty($userIdsToAdd)) {
                $this->validateUsers($userIdsToAdd);
            }

            // Update the notification
            $updatedNotification = $this->notificationRepository->update($notification, $data);

            // Add users to notification
            if (!empty($userIdsToAdd)) {
                $this->addUsersToNotification($notificationId, $userIdsToAdd);
            }

            // Remove users from notification
            if (!empty($userIdsToRemove)) {
                $this->removeUsersFromNotification($notificationId, $userIdsToRemove);
            }

            return $updatedNotification;
        });
    }

    /**
     * Delete a notification.
     *
     * @param int $notificationId
     * @return bool
     * @throws ModelNotFoundException
     */
    public function delete(int $notificationId): bool
    {
        $notification = $this->notificationRepository->findById($notificationId);
        if (!$notification) {
            throw new ModelNotFoundException('Notification not found');
        }

        return $this->notificationRepository->delete($notification);
    }

    /**
     * Deletes multiple notifications in a single transaction.
     *
     * This method deletes multiple notifications at once, based on the parameters
     * provided. If all notifications are selected, then all notifications except those with
     * IDs in the exception_ids list are deleted. If specific IDs are provided,
     * those notifications are deleted.
     *
     * The deletion is wrapped in a database transaction to ensure data
     * integrity.
     *
     * @param array $params Parameters to control the deletion operation
     *     - is_all_selected (bool): Delete all notifications except those in exception_ids
     *     - exception_ids (array): IDs to exclude from deletion
     *     - ids (array): IDs of notifications to delete
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
                    // Delete all except those in exception_ids
                    return $this->notificationRepository->deleteAllExcept($exceptionIds);
                } else {
                    // Delete all
                    return $this->notificationRepository->deleteAll();
                }
            } else {
                if (!empty($ids)) {
                    // Delete specific ids
                    return $this->notificationRepository->deleteSome($ids);
                } else {
                    // No records to delete
                    return 0;
                }
            }
        });
    }

    /**
     * Add a user to a notification's recipients.
     *
     * @param int $notificationId
     * @param int $userId
     * @return void
     * @throws ModelNotFoundException
     */
    public function addUserToNotification(int $notificationId, int $userId): void
    {
        $notification = $this->notificationRepository->findById($notificationId);
        if (!$notification) {
            throw new ModelNotFoundException('Notification not found');
        }

        $user = $this->usersRepository->findById($userId);
        if (!$user) {
            throw new ModelNotFoundException('User not found');
        }

        $this->notificationRepository->attachUsers($notification, [$userId]);
    }

    /**
     * Remove a user from a notification's recipients.
     *
     * @param int $notificationId
     * @param int $userId
     * @return void
     * @throws ModelNotFoundException
     */
    public function removeUserFromNotification(int $notificationId, int $userId): void
    {
        $notification = $this->notificationRepository->findById($notificationId);
        if (!$notification) {
            throw new ModelNotFoundException('Notification not found');
        }

        $this->notificationRepository->detachUsers($notification, [$userId]);
    }

    /**
     * Mark a notification as read for a specific user.
     *
     * @param int $notificationId
     * @param int $userId
     * @return bool
     * @throws ModelNotFoundException
     */
    public function markAsRead(int $notificationId, int $userId): bool
    {
        $notification = $this->notificationRepository->findById($notificationId);
        if (!$notification) {
            throw new ModelNotFoundException('Notification not found');
        }

        return $this->notificationRepository->markAsRead($notification, $userId);
    }

    /**
     * Mark all notifications as read for a specific user.
     *
     * @param int $userId
     * @return int Number of notifications marked as read
     */
    public function markAllAsRead(int $userId): int
    {
        return $this->notificationRepository->markAllAsRead($userId);
    }

    /**
     * Get unread notifications count for a user.
     *
     * @param int $userId
     * @return int Count of unread notifications
     */
    public function getUnreadCount(int $userId): int
    {
        return $this->notificationRepository->getUnreadCount($userId);
    }

    /**
     * Add multiple users to a notification.
     *
     * @param int $notificationId
     * @param array $userIds
     * @return void
     * @throws ModelNotFoundException
     */
    protected function addUsersToNotification(int $notificationId, array $userIds): void
    {
        $notification = $this->notificationRepository->findById($notificationId);
        if (!$notification) {
            throw new ModelNotFoundException('Notification not found');
        }

        $this->notificationRepository->attachUsers($notification, $userIds);
    }

    /**
     * Remove multiple users from a notification.
     *
     * @param int $notificationId
     * @param array $userIds
     * @return void
     * @throws ModelNotFoundException
     */
    protected function removeUsersFromNotification(int $notificationId, array $userIds): void
    {
        $notification = $this->notificationRepository->findById($notificationId);
        if (!$notification) {
            throw new ModelNotFoundException('Notification not found');
        }

        $this->notificationRepository->detachUsers($notification, $userIds);
    }

    /**
     * Validate that all user IDs exist.
     *
     * @param array $userIds
     * @return void
     * @throws ModelNotFoundException
     */
    protected function validateUsers(array $userIds): void
    {
        $existingUserCount = User::whereIn('id', $userIds)->count();

        if ($existingUserCount !== count($userIds)) {
            throw new ModelNotFoundException('One or more users not found');
        }
    }
}
