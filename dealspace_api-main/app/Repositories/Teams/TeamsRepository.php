<?php

namespace App\Repositories\Teams;

use App\Models\Team;
use Illuminate\Support\Facades\Auth;


class TeamsRepository implements TeamsRepositoryInterface
{
    protected $model;

    public function __construct(Team $model)
    {
        $this->model = $model;
    }

    /**
     * Get all teams with pagination.
     *
     * @param int $perPage Number of items per page
     * @param int $page Current page number
     * @param string|null $search Search term
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAll(int $perPage = 15, int $page = 1, string $search = null)
    {
        $teamQuery = $this->model->query();
        $user = Auth::user();
        if ($user) {
            $teamQuery = $teamQuery->visibleTo($user);
        }
        if ($search) {
            $teamQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            });
        }

        return $teamQuery->with(['users', 'leaders'])
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Find a team by its ID.
     *
     * @param int $teamId The ID of the team to find.
     * @return Team|null The found team or null if not found.
     */
    public function findById(int $teamId): ?Team
    {
        return $this->model->with(['users', 'leaders'])->find($teamId);
    }

    /**
     * Create a new team record.
     *
     * @param array $data The data for the new team
     * @return Team The newly created Team model instance.
     */
    public function create(array $data): Team
    {
        return $this->model->create($data);
    }

    /**
     * Update an existing team.
     *
     * @param Team $team The team to update.
     * @param array $data The updated team data
     * @return Team The updated Team model instance with fresh relationships.
     */
    public function update(Team $team, array $data): Team
    {
        $team->update($data);
        return $team->fresh(['users', 'leaders']);
    }

    /**
     * Delete a team.
     *
     * @param Team $team The team to delete.
     * @return bool True if deletion was successful, false otherwise.
     */
    public function delete(Team $team): bool
    {
        return $team->delete();
    }

    /**
     * Add a user to a team's members.
     *
     * @param Team $team The team to add the user to.
     * @param int $userId The ID of the user to add.
     * @return void
     */
    public function addUser(Team $team, int $userId): void
    {
        if (!$team->users()->where('user_id', $userId)->exists()) {
            $team->users()->attach($userId);
        }
    }

    /**
     * Remove a user from a team's members.
     *
     * @param Team $team The team to remove the user from.
     * @param int $userId The ID of the user to remove.
     * @return void
     */
    public function removeUser(Team $team, int $userId): void
    {
        $team->users()->detach($userId);
    }

    /**
     * Add a leader to a team.
     *
     * @param Team $team The team to add the leader to.
     * @param int $userId The ID of the user to add as leader.
     * @return void
     */
    public function addLeader(Team $team, int $userId): void
    {
        if (!$team->leaders()->where('user_id', $userId)->exists()) {
            $team->leaders()->attach($userId);
        }
    }

    /**
     * Remove a leader from a team.
     *
     * @param Team $team The team to remove the leader from.
     * @param int $userId The ID of the user to remove as leader.
     * @return void
     */
    public function removeLeader(Team $team, int $userId): void
    {
        $team->leaders()->detach($userId);
    }

    /**
     * Delete all team records
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
