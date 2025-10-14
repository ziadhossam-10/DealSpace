<?php

namespace App\Repositories\Notifications;

use App\Models\Notification;
use Illuminate\Support\Facades\DB;

class NotificationsRepository implements NotificationsRepositoryInterface
{
    protected $model;

    public function __construct(Notification $model)
    {
        $this->model = $model;
    }

    /**
     * Get all notifications with pagination.
     *
     * @param int $perPage Number of items per page
     * @param int $page Current page number
     * @param string|null $search Search term
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator Paginated notification records with relationships loaded.
     */
    public function getAll(int $perPage = 15, int $page = 1, string $search = null)
    {
        $notificationQuery = $this->model->query();

        if ($search) {
            $notificationQuery->where(function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        return $notificationQuery->with(['users', 'tenant'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get notifications for a specific user with pagination.
     *
     * @param int $userId The ID of the user.
     * @param int $perPage Number of items per page
     * @param int $page Current page number
     * @param bool $unreadOnly Whether to get only unread notifications
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator Paginated notifications for the user.
     */
    public function getByUserId(int $userId, int $perPage = 15, int $page = 1, bool $unreadOnly = false)
    {
        $query = $this->model->whereHas('users', function ($q) use ($userId, $unreadOnly) {
            $q->where('user_id', $userId);
            if ($unreadOnly) {
                $q->whereNull('read_at');
            }
        })->with(['users' => function ($q) use ($userId) {
            $q->where('user_id', $userId);
        }, 'tenant']);

        return $query->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Find a notification by its ID.
     *
     * @param int $notificationId The ID of the notification to find.
     * @return Notification|null The found notification or null if not found.
     */
    public function findById(int $notificationId): ?Notification
    {
        return $this->model->with(['users', 'tenant'])->find($notificationId);
    }

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
    public function create(array $data): Notification
    {
        return $this->model->create($data);
    }

    /**
     * Update an existing notification.
     *
     * @param Notification $notification The notification to update.
     * @param array $data The updated notification data.
     * @return Notification The updated Notification model instance with fresh relationships.
     */
    public function update(Notification $notification, array $data): Notification
    {
        $notification->update($data);
        return $notification->fresh(['users', 'tenant']);
    }

    /**
     * Delete a notification.
     *
     * @param Notification $notification The notification to delete.
     * @return bool True if deletion was successful, false otherwise.
     */
    public function delete(Notification $notification): bool
    {
        return $notification->delete();
    }

    /**
     * Attach users to a notification.
     *
     * @param Notification $notification The notification to attach users to.
     * @param array $userIds Array of user IDs to attach.
     * @return void
     */
    public function attachUsers(Notification $notification, array $userIds): void
    {
        // Get existing user IDs to avoid duplicates
        $existingUserIds = $notification->users()->pluck('user_id')->toArray();
        $newUserIds = array_diff($userIds, $existingUserIds);

        if (!empty($newUserIds)) {
            $notification->users()->attach($newUserIds);
        }
    }

    /**
     * Detach users from a notification.
     *
     * @param Notification $notification The notification to detach users from.
     * @param array $userIds Array of user IDs to detach.
     * @return void
     */
    public function detachUsers(Notification $notification, array $userIds): void
    {
        $notification->users()->detach($userIds);
    }

    /**
     * Mark a notification as read for a specific user.
     *
     * @param Notification $notification The notification to mark as read.
     * @param int $userId The ID of the user.
     * @return bool True if successful, false otherwise.
     */
    public function markAsRead(Notification $notification, int $userId): bool
    {
        $pivotRecord = $notification->users()->where('user_id', $userId)->first();

        if ($pivotRecord && is_null($pivotRecord->pivot->read_at)) {
            $notification->users()->updateExistingPivot($userId, [
                'read_at' => now()
            ]);
            return true;
        }

        return false;
    }

    /**
     * Mark all notifications as read for a specific user.
     *
     * @param int $userId The ID of the user.
     * @return int Number of notifications marked as read.
     */
    public function markAllAsRead(int $userId): int
    {
        return DB::table('notification_user')
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Get unread notifications count for a user.
     *
     * @param int $userId The ID of the user.
     * @return int Count of unread notifications.
     */
    public function getUnreadCount(int $userId): int
    {
        return $this->model->whereHas('users', function ($q) use ($userId) {
            $q->where('user_id', $userId)
                ->whereNull('read_at');
        })->count();
    }

    /**
     * Delete all notification records
     *
     * @return int Number of deleted records
     */
    public function deleteAll(): int
    {
        return $this->model->query()->delete();
    }

    /**
     * Delete all records except those with specified IDs
     *
     * @param array $ids IDs to exclude from deletion
     * @return int Number of deleted records
     */
    public function deleteAllExcept(array $ids): int
    {
        return $this->model->whereNotIn('id', $ids)->delete();
    }

    /**
     * Delete multiple records by their IDs
     *
     * @param array $ids IDs of records to delete
     * @return int Number of deleted records
     */
    public function deleteSome(array $ids): int
    {
        return $this->model->whereIn('id', $ids)->delete();
    }
}
