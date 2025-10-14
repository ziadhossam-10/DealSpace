<?php

namespace App\Services\Calls;

use App\Models\Call;
use App\Repositories\Calls\CallsRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CallService implements CallServiceInterface
{
    protected $CallRepository;

    public function __construct(
        CallsRepositoryInterface $CallRepository
    ) {
        $this->CallRepository = $CallRepository;
    }

    /**
     * Get all Calls.
     *
     * @return Collection Collection of Call model instances
     */
    public function getAll(int $perPage = 15, int $page = 1, int $personId)
    {
        return $this->CallRepository->getAll($perPage, $page, $personId);
    }

    /**
     * Get a specific Call by ID.
     *
     * @param int $id The ID of the Call to retrieve
     * @return Call The Call model instance
     * @throws ModelNotFoundException
     */
    public function findById(int $id): Call
    {
        $Call = $this->CallRepository->findById($id);

        if (!$Call) {
            throw new ModelNotFoundException('Call not found');
        }

        return $Call;
    }

    /**
     * Create a new Call.
     *
     * @param array $data The Call data including:
     * - 'name' (string) The name of the Call
     * - 'description' (string) The description of the Call
     * @return Call The newly created Call model instance
     */
    public function create(array $data): Call
    {
        return $this->CallRepository->create($data);
    }

    /**
     * Update an existing Call.
     *
     * @param int $id The ID of the Call to update
     * @param array $data The updated Call data including:
     * - ['name'] (string) The updated name of the Call
     * - ['description'] (string) The updated description of the Call
     * @return Call The updated Call model instance
     * @throws ModelNotFoundException
     */
    public function update(int $id, array $data): Call
    {
        $Call = $this->CallRepository->findById($id);

        if (!$Call) {
            throw new ModelNotFoundException('Call not found');
        }

        return $this->CallRepository->update($Call, $data);
    }

    /**
     * Delete a Call.
     *
     * @param int $id The ID of the Call to delete
     * @return bool True if the deletion was successful, false otherwise
     * @throws ModelNotFoundException
     */
    public function delete(int $id): bool
    {
        $Call = $this->CallRepository->findById($id);

        if (!$Call) {
            throw new ModelNotFoundException('Call not found');
        }

        return $this->CallRepository->delete($Call);
    }
}
