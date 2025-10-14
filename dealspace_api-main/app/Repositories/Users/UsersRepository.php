<?php

namespace App\Repositories\Users;

use App\Models\User;
use App\Enums\RoleEnum;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UsersRepository implements UsersRepositoryInterface
{
    protected $model;


    public function __construct(User $model)
    {
        $this->model = $model;
    }

    /**
     * Retrieves all records with pagination.
     *
     * @param int $perPage The number of items per page
     * @param int $page The page number to retrieve
     * @return LengthAwarePaginator Paginated list of records
     */
    public function getAll(int $perPage = 15, int $page = 1, string $role = null, string $search = null)
    {
        $userQuery = $this->model->query();
        
        if ($role) {
            $userQuery->where('role', $role);
        }
        
        $authUser = Auth::user();
        // Apply team-based visibility for non-admin/owner users
        if (!in_array($authUser->role, [RoleEnum::ADMIN, RoleEnum::OWNER])) {
            $userQuery->assignedUserTeamMembers($authUser);
        }

        if ($search) {
            $userQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Exclude the current authenticated user
        //$userQuery->where('id', '!=', Auth::user()->id);

        // Return paginated results
        return $userQuery->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Find a record by its ID.
     *
     * @param int $id The ID of the record to find.
     * @return mixed
     */
    public function findById(int $id)
    {
        return $this->model->findOrFail($id);
    }

    /**
     * Creates a new record with the given data.
     *
     * @param array $data The data to use when creating the record
     * @return User The newly created record
     */
    public function create(array $data)
    {
        return $this->model->create($data);
    }

    /**
     * Update a record with the given ID.
     *
     * @param int $id The ID of the record to update
     * @param array $data The data to update the record with
     * @return User The updated record
     */
    public function update(int $id, array $data)
    {
        $user = $this->findById($id);
        $user->update($data);
        return $user;
    }

    /**
     * Deletes a record with the given ID.
     *
     * @param int $id The ID of the record to delete
     * @return mixed The result of the deletion operation
     */
    public function delete(int $id)
    {
        return $this->model->delete($id);
    }

    /**
     * Finds a user by email address.
     *
     * @param string $email The email address to search for
     * @return User|null The user with the given email address, or null if none found
     */
    public function findByEmail(string $email)
    {
        return User::whereHas('emails', function ($query) use ($email) {
            $query->where('value', $email);
        })->first();
    }

    /**
     * Delete all records from the users table
     *
     * @return int Number of deleted records
     */
    public function deleteAll()
    {
        return $this->model->query()->delete();
    }

    /**
     * Delete all records except those with specified IDs
     *
     * @param array $ids IDs to exclude from deletion
     * @return int Number of deleted records
     */
    public function deleteAllExcept(array $ids)
    {
        return $this->model->whereNotIn('id', $ids)->delete();
    }

    /**
     * Delete multiple records by their IDs
     *
     * @param array $ids IDs of records to delete
     * @return int Number of deleted records
     */
    public function deleteSome(array $ids)
    {
        return $this->model->whereIn('id', $ids)->delete();
    }

    /**
     * Import multiple users records at once
     *
     * @param array $users Array of users data to import
     * @return Collection Collection of created User models
     */
    public function import(array $users)
    {
        $created = new Collection();

        DB::transaction(function () use ($users, &$created) {
            foreach ($users as $userData) {
                $created->push($this->model->create($userData));
            }
        });

        return $created;
    }
}
