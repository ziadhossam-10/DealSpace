<?php

namespace App\Repositories\Ponds;

use App\Models\Pond;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;


class PondsRepository implements PondsRepositoryInterface
{
    protected $model;

    public function __construct(Pond $model)
    {
        $this->model = $model;
    }

    /**
     * Get all ponds with pagination.
     *
     * @param int $perPage Number of items per page
     * @param int $page Current page number
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator Paginated pond records with relationships loaded.
     */
    public function getAll(int $perPage = 15, int $page = 1, string $search = null)
    {
        $pondQuery = $this->model->query();
        $user = Auth::user();

        if ($user) {
            $pondQuery->visibleTo($user);
        }

        if ($search) {
            $pondQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            });
        }


        return $pondQuery->with(['user'])
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Find a pond by its ID.
     *
     * @param int $pondId The ID of the pond to find.
     * @return Pond|null The found pond or null if not found.
     */
    public function findById(int $pondId): ?Pond
    {
        return $this->model->with(['user', 'users'])->find($pondId);
    }

    /**
     * Create a new pond record.
     *
     * @param array $data The data for the new pond, including:
     * - 'name' (string) The name of the pond.
     * - 'user_id' (int) The ID of the user who owns the pond.
     * @return Pond The newly created Pond model instance.
     */
    public function create(array $data): Pond
    {
        return $this->model->create($data);
    }

    /**
     * Update an existing pond.
     *
     * @param Pond $pond The pond to update.
     * @param array $data The updated pond data including:
     * - ['name'] (string) The updated name of the pond.
     * - ['user_id'] (int) The updated ID of the user who owns the pond.
     * @return Pond The updated Pond model instance with fresh relationships.
     */
    public function update(Pond $pond, array $data): Pond
    {
        $pond->update($data);
        return $pond->fresh(['user', 'users']);
    }

    /**
     * Delete a pond.
     *
     * @param Pond $pond The pond to delete.
     * @return bool True if deletion was successful, false otherwise.
     */
    public function delete(Pond $pond): bool
    {
        return $pond->delete();
    }

    /**
     * Get ponds by user ID with pagination.
     *
     * @param int $userId The ID of the user.
     * @param int $perPage Number of items per page
     * @param int $page Current page number
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator Paginated ponds owned by the user.
     */
    public function getByUserId(int $userId, int $perPage = 15, int $page = 1)
    {
        return $this->model->with(['user', 'users'])
            ->where('user_id', $userId)
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Add a user to a pond's shared users.
     *
     * @param Pond $pond The pond to add the user to.
     * @param int $userId The ID of the user to add.
     * @return void
     */
    public function addUser(Pond $pond, int $userId): void
    {
        if (!$pond->users()->where('user_id', $userId)->exists()) {
            $pond->users()->attach($userId);
        }
    }

    /**
     * Remove a user from a pond's shared users.
     *
     * @param Pond $pond The pond to remove the user from.
     * @param int $userId The ID of the user to remove.
     * @return void
     */
    public function removeUser(Pond $pond, int $userId): void
    {
        $pond->users()->detach($userId);
    }

    /**
     * Delete all pond records
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
