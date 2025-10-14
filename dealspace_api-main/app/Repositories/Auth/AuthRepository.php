<?php
namespace App\Repositories\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthRepository implements AuthRepositoryInterface
{
    protected $model;

    public function __construct(User $model)
    {
        $this->model = $model;
    }

    /**
     * Find user by email.
     *
     * @param string $email The email address to search for.
     * @return mixed
     */
    public function findByEmail(string $email)
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * Find a record by its ID.
     *
     * @param int $id The ID of the record to find.
     * @return mixed
     */
    public function findById(int $id)
    {
        return $this->model->find($id);
    }

    /**
     * Find user by social provider.
     *
     * @param string $provider The provider to search for.
     * @param string $providerId The provider ID to search for.
     * @return \App\Models\User|null
     */
    public function findByProvider(string $provider, string $providerId)
    {
        return $this->model->where('provider', $provider)
            ->where('provider_id', $providerId)
            ->first();
    }

    /**
     * Create a new user
     *
     * @param array $userData
     * @throws Some_Exception_Class description of exception
     * @return Some_Return_Value
     */
    public function create(array $userData)
    {
        if (isset($userData['password'])) {
            $userData['password'] = Hash::make($userData['password']);
        }

        return $this->model->create($userData);
    }

    /**
     * Update user data.
     *
     * @param int $userId The ID of the user to update.
     * @param array $userData The data to update for the user.
     * @return \App\Models\User The updated user object.
     */
    public function update(int $userId, array $userData)
    {
        $user = $this->findById($userId);

        if (!$user) {
            return false;
        }

        // If updating password, hash it
        if (isset($userData['password'])) {
            $userData['password'] = Hash::make($userData['password']);
        } else {
            unset($userData['password']);
        }

        $user->update($userData);

        return $user;
    }

    /**
     * Create authentication token for user
     *
     * @param \App\Models\User $user
     * @return string
     */
    public function createToken($user)
    {
        return $user->createToken('auth_token')->plainTextToken;
    }
}