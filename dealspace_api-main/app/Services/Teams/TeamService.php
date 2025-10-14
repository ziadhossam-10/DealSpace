<?php

namespace App\Services\Teams;

use App\Models\Team;
use App\Models\User;
use App\Repositories\Teams\TeamsRepositoryInterface;
use App\Repositories\Users\UsersRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class TeamService implements TeamServiceInterface
{
    protected $teamsRepository;
    protected $usersRepository;

    public function __construct(
        TeamsRepositoryInterface $teamsRepository,
        UsersRepositoryInterface $usersRepository
    ) {
        $this->teamsRepository = $teamsRepository;
        $this->usersRepository = $usersRepository;
    }

    /**
     * Get all teams.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAll(int $perPage = 15, int $page = 1, string $search = null)
    {
        return $this->teamsRepository->getAll($perPage, $page, $search);
    }

    /**
     * Get a team by ID.
     *
     * @param int $teamId
     * @return Team
     * @throws ModelNotFoundException
     */
    public function findById(int $teamId): Team
    {
        $team = $this->teamsRepository->findById($teamId);
        if (!$team) {
            throw new ModelNotFoundException('Team not found');
        }
        return $team;
    }

    /**
     * Create a new team with users and leaders.
     *
     * @param array $data The complete team data including:
     * - 'name' (string) The name of the team
     * - 'userIds' (array) Array of user IDs to add to the team
     * - 'leaderIds' (array) Array of leader IDs for the team
     * @return Team
     * @throws ModelNotFoundException
     */
    public function create(array $data): Team
    {
        return DB::transaction(function () use ($data) {
            // Extract user-related arrays
            $userIds = $data['userIds'] ?? [];
            $leaderIds = $data['leaderIds'] ?? [];

            // Remove user arrays from data to prevent SQL errors
            unset($data['userIds'], $data['leaderIds']);

            // Create the team
            $team = $this->teamsRepository->create($data);

            // Add users to team if userIds array is provided
            if (!empty($userIds)) {
                $this->addUsersToTeam($team->id, $userIds);
            }

            // Add leaders to team if leaderIds array is provided
            if (!empty($leaderIds)) {
                $this->addLeadersToTeam($team->id, $leaderIds);
            }

            return $team;
        });
    }

    /**
     * Update an existing team and its users/leaders.
     *
     * @param int $teamId
     * @param array $data The complete team data including:
     * - Team fields to update
     * - ['userIds'] (array) Array of user IDs to add to the team
     * - ['leaderIds'] (array) Array of leader IDs for the team
     * - ['userIdsToDelete'] (array) Array of user IDs to remove from the team
     * - ['leaderIdsToDelete'] (array) Array of leader IDs to remove from the team
     * @return Team
     * @throws ModelNotFoundException
     */
    public function update(int $teamId, array $data): Team
    {
        return DB::transaction(function () use ($teamId, $data) {
            $team = $this->teamsRepository->findById($teamId);
            if (!$team) {
                throw new ModelNotFoundException('Team not found');
            }

            // Extract user-related arrays
            $userIdsToAdd = $data['userIds'] ?? [];
            $leaderIdsToAdd = $data['leaderIds'] ?? [];
            $userIdsToDelete = $data['userIdsToDelete'] ?? [];
            $leaderIdsToDelete = $data['leaderIdsToDelete'] ?? [];

            // Remove user arrays from data to prevent SQL errors
            unset($data['userIds'], $data['leaderIds'], $data['userIdsToDelete'], $data['leaderIdsToDelete']);

            // Update the team
            $updatedTeam = $this->teamsRepository->update($team, $data);

            // Add users to team
            if (!empty($userIdsToAdd)) {
                $this->addUsersToTeam($teamId, $userIdsToAdd);
            }

            // Add leaders to team
            if (!empty($leaderIdsToAdd)) {
                $this->addLeadersToTeam($teamId, $leaderIdsToAdd);
            }

            // Remove users from team
            if (!empty($userIdsToDelete)) {
                $this->removeUsersFromTeam($teamId, $userIdsToDelete);
            }

            // Remove leaders from team
            if (!empty($leaderIdsToDelete)) {
                $this->removeLeadersFromTeam($teamId, $leaderIdsToDelete);
            }

            return $updatedTeam;
        });
    }

    /**
     * Delete a team.
     *
     * @param int $teamId
     * @return bool
     * @throws ModelNotFoundException
     */
    public function delete(int $teamId): bool
    {
        $team = $this->teamsRepository->findById($teamId);
        if (!$team) {
            throw new ModelNotFoundException('Team not found');
        }

        return $this->teamsRepository->delete($team);
    }

    /**
     * Deletes multiple teams in a single transaction.
     *
     * @param array $params Parameters to control the deletion operation
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
                    return $this->teamsRepository->deleteAllExcept($exceptionIds);
                } else {
                    return $this->teamsRepository->deleteAll();
                }
            } else {
                if (!empty($ids)) {
                    return $this->teamsRepository->deleteSome($ids);
                } else {
                    return 0;
                }
            }
        });
    }

    /**
     * Add a user to a team's members.
     *
     * @param int $teamId
     * @param int $userId
     * @return void
     * @throws ModelNotFoundException
     */
    public function addUserToTeam(int $teamId, int $userId): void
    {
        $team = $this->teamsRepository->findById($teamId);
        if (!$team) {
            throw new ModelNotFoundException('Team not found');
        }

        $user = $this->usersRepository->findById($userId);
        if (!$user) {
            throw new ModelNotFoundException('User not found');
        }

        $this->teamsRepository->addUser($team, $userId);
    }

    /**
     * Remove a user from a team's members.
     *
     * @param int $teamId
     * @param int $userId
     * @return void
     * @throws ModelNotFoundException
     */
    public function removeUserFromTeam(int $teamId, int $userId): void
    {
        $team = $this->teamsRepository->findById($teamId);
        if (!$team) {
            throw new ModelNotFoundException('Team not found');
        }

        $this->teamsRepository->removeUser($team, $userId);
    }

    /**
     * Add a leader to a team.
     *
     * @param int $teamId
     * @param int $userId
     * @return void
     * @throws ModelNotFoundException
     */
    public function addLeaderToTeam(int $teamId, int $userId): void
    {
        $team = $this->teamsRepository->findById($teamId);
        if (!$team) {
            throw new ModelNotFoundException('Team not found');
        }

        $user = $this->usersRepository->findById($userId);
        if (!$user) {
            throw new ModelNotFoundException('User not found');
        }

        $this->teamsRepository->addLeader($team, $userId);
    }

    /**
     * Remove a leader from a team.
     *
     * @param int $teamId
     * @param int $userId
     * @return void
     * @throws ModelNotFoundException
     */
    public function removeLeaderFromTeam(int $teamId, int $userId): void
    {
        $team = $this->teamsRepository->findById($teamId);
        if (!$team) {
            throw new ModelNotFoundException('Team not found');
        }

        $this->teamsRepository->removeLeader($team, $userId);
    }

    /**
     * Add multiple users to a team.
     *
     * @param int $teamId
     * @param array $userIds
     * @return void
     * @throws ModelNotFoundException
     */
    protected function addUsersToTeam(int $teamId, array $userIds): void
    {
        foreach ($userIds as $userId) {
            $this->addUserToTeam($teamId, $userId);
        }
    }

    /**
     * Remove multiple users from a team.
     *
     * @param int $teamId
     * @param array $userIds
     * @return void
     * @throws ModelNotFoundException
     */
    protected function removeUsersFromTeam(int $teamId, array $userIds): void
    {
        foreach ($userIds as $userId) {
            $this->removeUserFromTeam($teamId, $userId);
        }
    }

    /**
     * Add multiple leaders to a team.
     *
     * @param int $teamId
     * @param array $leaderIds
     * @return void
     * @throws ModelNotFoundException
     */
    protected function addLeadersToTeam(int $teamId, array $leaderIds): void
    {
        foreach ($leaderIds as $leaderId) {
            $this->addLeaderToTeam($teamId, $leaderId);
        }
    }

    /**
     * Remove multiple leaders from a team.
     *
     * @param int $teamId
     * @param array $leaderIds
     * @return void
     * @throws ModelNotFoundException
     */
    protected function removeLeadersFromTeam(int $teamId, array $leaderIds): void
    {
        foreach ($leaderIds as $leaderId) {
            $this->removeLeaderFromTeam($teamId, $leaderId);
        }
    }
}
