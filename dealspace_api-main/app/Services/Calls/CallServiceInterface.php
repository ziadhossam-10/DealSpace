<?php

namespace App\Services\Calls;

use App\Models\Call;

interface CallServiceInterface
{
    /**
     * Get all calls for a person.
     *
     * @param int $perPage
     * @param int $page
     * @param int $personId
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAll(int $perPage = 15, int $page = 1, int $personId);

    /**
     * Get a call by ID.
     *
     * @param int $callId
     * @return Call
     */
    public function findById(int $callId): Call;

    /**
     * Create a new call with mentions.
     *
     * @param array $data
     * @return Call
     */
    public function create(array $data): Call;

    /**
     * Update an existing call and its mentions.
     *
     * @param int $callId
     * @param array $data
     * @return Call
     */
    public function update(int $callId, array $data): Call;

    /**
     * Delete a call.
     *
     * @param int $callId
     * @return bool
     */
    public function delete(int $callId): bool;
}
