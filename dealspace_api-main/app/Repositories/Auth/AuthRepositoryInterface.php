<?php
namespace App\Repositories\Auth;

interface AuthRepositoryInterface
{
    /**
     * Find user by email
     *
     * @param string $email
     * @return \App\Models\User|null
     */
    public function findByEmail(string $email);

    /**
     * Find user by ID
     *
     * @param int $id
     * @return \App\Models\User|null
     */
    public function findById(int $id);

    /**
     * Find user by social provider
     *
     * @param string $provider
     * @param string $providerId
     * @return \App\Models\User|null
     */
    public function findByProvider(string $provider, string $providerId);

    /**
     * Create a new user
     *
     * @param array $userData
     * @return \App\Models\User
     */
    public function create(array $userData);

    /**
     * Update user data
     *
     * @param int $userId
     * @param array $userData
     * @return \App\Models\User|bool
     */
    public function update(int $userId, array $userData);

    /**
     * Create authentication token for user
     *
     * @param \App\Models\User $user
     * @return string
     */
    public function createToken($user);
}