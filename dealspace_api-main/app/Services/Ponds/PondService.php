<?php

namespace App\Services\Ponds;

use App\Models\Pond;
use App\Models\User;
use App\Repositories\Ponds\PondsRepositoryInterface;
use App\Repositories\Users\UsersRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class PondService implements PondServiceInterface
{
    protected $pondsRepository;
    protected $usersRepository;

    public function __construct(
        PondsRepositoryInterface $pondsRepository,
        UsersRepositoryInterface $usersRepository
    ) {
        $this->pondsRepository = $pondsRepository;
        $this->usersRepository = $usersRepository;
    }

    /**
     * Get all ponds.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAll(int $perPage = 15, int $page = 1, string $search = null)
    {
        return $this->pondsRepository->getAll($perPage, $page, $search);
    }

    /**
     * Get a pond by ID.
     *
     * @param int $pondId
     * @return Pond
     * @throws ModelNotFoundException
     */
    public function findById(int $pondId): Pond
    {
        $pond = $this->pondsRepository->findById($pondId);
        if (!$pond) {
            throw new ModelNotFoundException('Pond not found');
        }
        return $pond;
    }

    /**
     * Create a new pond with users.
     *
     * @param array $data The complete pond data including:
     * - 'name' (string) The name of the pond
     * - 'user_id' (int) The ID of the user who owns the pond
     * - ['user_ids'] (array) Array of user IDs to add to the pond
     * @return Pond
     * @throws ModelNotFoundException
     */
    public function create(array $data): Pond
    {
        return DB::transaction(function () use ($data) {
            // Extract user-related arrays
            $userIds = $data['user_ids'] ?? [];

            // Remove user arrays from data to prevent SQL errors
            unset($data['user_ids']);

            // Verify that the user exists before creating the pond
            $user = $this->usersRepository->findById($data['user_id']);
            if (!$user) {
                throw new ModelNotFoundException('User not found');
            }

            // Create the pond
            $pond = $this->pondsRepository->create($data);

            // Add users to pond if user_ids array is provided
            if (!empty($userIds)) {
                $this->addUsersToPond($pond->id, $userIds);
            }

            return $pond;
        });
    }

    /**
     * Update an existing pond and its users.
     *
     * @param int $pondId
     * @param array $data The complete pond data including:
     * - Pond fields to update
     * - ['user_ids'] (array) Array of user IDs to add to the pond
     * - ['user_ids_to_delete'] (array) Array of user IDs to remove from the pond
     * @return Pond
     * @throws ModelNotFoundException
     */
    public function update(int $pondId, array $data): Pond
    {
        return DB::transaction(function () use ($pondId, $data) {
            $pond = $this->pondsRepository->findById($pondId);
            if (!$pond) {
                throw new ModelNotFoundException('Pond not found');
            }

            // Extract user-related arrays
            $userIdsToAdd = $data['user_ids'] ?? [];
            $userIdsToDelete = $data['user_ids_to_delete'] ?? [];

            // Remove user arrays from data to prevent SQL errors
            unset($data['user_ids'], $data['user_ids_to_delete']);

            // If changing owner, verify that the new user exists
            if (isset($data['user_id'])) {
                $user = $this->usersRepository->findById($data['user_id']);
                if (!$user) {
                    throw new ModelNotFoundException('New owner user not found');
                }
            }

            // Update the pond
            $updatedPond = $this->pondsRepository->update($pond, $data);

            // Add users to pond
            if (!empty($userIdsToAdd)) {
                $this->addUsersToPond($pondId, $userIdsToAdd);
            }

            // Remove users from pond
            if (!empty($userIdsToDelete)) {
                $this->removeUsersFromPond($pondId, $userIdsToDelete);
            }

            return $updatedPond;
        });
    }

    /**
     * Delete a pond.
     *
     * @param int $pondId
     * @return bool
     * @throws ModelNotFoundException
     */
    public function delete(int $pondId): bool
    {
        $pond = $this->pondsRepository->findById($pondId);
        if (!$pond) {
            throw new ModelNotFoundException('Pond not found');
        }

        return $this->pondsRepository->delete($pond);
    }

    /**
     * Deletes multiple ponds in a single transaction.
     *
     * This method deletes multiple ponds at once, based on the parameters
     * provided. If all ponds are selected, then all ponds except those with
     * IDs in the exception_ids list are deleted. If specific IDs are provided,
     * those ponds are deleted.
     *
     * The deletion is wrapped in a database transaction to ensure data
     * integrity.
     *
     * @param array $params Parameters to control the deletion operation
     *     - is_all_selected (bool): Delete all ponds except those in exception_ids
     *     - exception_ids (array): IDs to exclude from deletion
     *     - ids (array): IDs of ponds to delete
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
                    return $this->pondsRepository->deleteAllExcept($exceptionIds);
                } else {
                    // Delete all
                    return $this->pondsRepository->deleteAll();
                }
            } else {
                if (!empty($ids)) {
                    // Delete specific ids
                    return $this->pondsRepository->deleteSome($ids);
                } else {
                    // No records to delete
                    return 0;
                }
            }
        });
    }

    /**
     * Add a user to a pond's shared users.
     *
     * @param int $pondId
     * @param int $userId
     * @return void
     * @throws ModelNotFoundException
     */
    public function addUserToPond(int $pondId, int $userId): void
    {
        $pond = $this->pondsRepository->findById($pondId);
        if (!$pond) {
            throw new ModelNotFoundException('Pond not found');
        }

        $user = $this->usersRepository->findById($userId);
        if (!$user) {
            throw new ModelNotFoundException('User not found');
        }

        $this->pondsRepository->addUser($pond, $userId);
    }

    /**
     * Remove a user from a pond's shared users.
     *
     * @param int $pondId
     * @param int $userId
     * @return void
     * @throws ModelNotFoundException
     */
    public function removeUserFromPond(int $pondId, int $userId): void
    {
        $pond = $this->pondsRepository->findById($pondId);
        if (!$pond) {
            throw new ModelNotFoundException('Pond not found');
        }

        $this->pondsRepository->removeUser($pond, $userId);
    }

    /**
     * Add multiple users to a pond.
     *
     * @param int $pondId
     * @param array $userIds
     * @return void
     * @throws ModelNotFoundException
     */
    protected function addUsersToPond(int $pondId, array $userIds): void
    {
        foreach ($userIds as $userId) {
            $this->addUserToPond($pondId, $userId);
        }
    }

    /**
     * Remove multiple users from a pond.
     *
     * @param int $pondId
     * @param array $userIds
     * @return void
     * @throws ModelNotFoundException
     */
    protected function removeUsersFromPond(int $pondId, array $userIds): void
    {
        foreach ($userIds as $userId) {
            $this->removeUserFromPond($pondId, $userId);
        }
    }
}
