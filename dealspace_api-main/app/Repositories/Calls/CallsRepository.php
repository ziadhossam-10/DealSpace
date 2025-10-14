<?php

namespace App\Repositories\Calls;

use App\Models\Call;

class CallsRepository implements CallsRepositoryInterface
{
    protected $model;

    public function __construct(Call $model)
    {
        $this->model = $model;
    }

    /**
     * Get all calls for a specific person with pagination.
     *
     * @param int $perPage Number of items per page
     * @param int $page Current page number
     * @param int $personId The ID of the person associated with the calls
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator Paginated call records
     */
    public function getAll(int $perPage = 15, int $page = 1, int $personId): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->model->where('person_id', $personId)
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Find a call by its ID.
     *
     * @param int $callId The ID of the call to find.
     * @return Call|null The found Call instance or null if not found.
     */
    public function findById(int $callId): ?Call
    {
        return $this->model->find($callId);
    }

    /**
     * Create a new call record.
     *
     * @param array $data The data for the new call. Example fields:
     * - 'person_id' (int): Required. The person related to the call.
     * - 'phone' (string): Required. The phone number involved.
     * - 'is_incoming' (bool): Required. True if the call is incoming.
     * - 'to_number' (string): The number the call was made to.
     * - 'from_number' (string): The number the call was made from.
     * - 'outcome' (int): Optional. Outcome enum value.
     * - 'note' (string): Optional. Notes about the call.
     * - 'duration' (int): Optional. Duration of the call in seconds.
     * - 'user_id' (int): Optional. The user who made or received the call.
     * - 'recording_url' (string): Optional. URL to the call recording.
     * @return Call The newly created Call model instance.
     */
    public function create(array $data): Call
    {
        return $this->model->create($data)->fresh();
    }

    /**
     * Update an existing call record.
     *
     * @param Call $call The call instance to update.
     * @param array $data The fields to update (same structure as `create()`).
     * @return Call The updated Call model instance.
     */
    public function update(Call $call, array $data): Call
    {
        $call->update($data);

        return $call->fresh();
    }

    /**
     * Delete a call record.
     *
     * @param Call $call The call instance to delete.
     * @return bool True if deleted, false otherwise.
     */
    public function delete(Call $call): bool
    {
        return $call->delete();
    }
}
