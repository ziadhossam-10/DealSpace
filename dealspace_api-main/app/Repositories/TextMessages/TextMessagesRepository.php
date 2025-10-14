<?php

namespace App\Repositories\TextMessages;

use App\Models\TextMessage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TextMessagesRepository implements TextMessagesRepositoryInterface
{
    protected $model;

    public function __construct(TextMessage $model)
    {
        $this->model = $model;
    }

    /**
     * Get all text messages for a specific person with pagination.
     *
     * @param int $perPage Number of items per page
     * @param int $page Current page number
     * @param int $personId The ID of the person associated with the text messages
     * @return LengthAwarePaginator Paginated text message records
     */
    public function getAll(int $perPage = 15, int $page = 1, int $personId): LengthAwarePaginator
    {
        return $this->model->with(['person', 'user'])
            ->where('person_id', $personId)
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get all text messages with optional person filtering.
     *
     * @param int $perPage Number of items per page
     * @param int $page Current page number
     * @param int|null $personId Optional person ID filter
     * @return LengthAwarePaginator Paginated text message records
     */
    public function getAllWithOptionalPersonFilter(int $perPage = 15, int $page = 1, ?int $personId = null): LengthAwarePaginator
    {
        $query = $this->model->with(['person', 'user'])
            ->orderBy('created_at', 'desc');

        if ($personId) {
            $query->where('person_id', $personId);
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Find a text message by its ID.
     *
     * @param int $textMessageId The ID of the text message to find.
     * @return TextMessage|null The found TextMessage instance or null if not found.
     */
    public function findById(int $textMessageId): ?TextMessage
    {
        return $this->model->with(['person', 'user'])->find($textMessageId);
    }

    /**
     * Create a new text message record.
     *
     * @param array $data The data for the new text message. Example fields:
     * - 'person_id' (int): Required. The person related to the text message.
     * - 'message' (string): Required. The body of the text message.
     * - 'to_number' (string): Required. The phone number the text message was sent to.
     * - 'from_number' (string): Required. The phone number the text message was sent from.
     * - 'is_incoming' (bool): Optional. True if the text message is incoming (defaults to false).
     * - 'external_label' (string): Optional. Descriptive text for the timeline.
     * - 'external_url' (string): Optional. Link for the timeline.
     * - 'user_id' (int): Optional. The user who sent or received the text message.
     * @return TextMessage The newly created TextMessage model instance.
     */
    public function create(array $data): TextMessage
    {
        return $this->model->create($data)->fresh(['person', 'user']);
    }

    /**
     * Update an existing text message record.
     *
     * @param int $textMessageId The ID of the text message to update
     * @param array $data The data to update
     * @return TextMessage The updated TextMessage model instance.
     */
    public function update(int $textMessageId, array $data): TextMessage
    {
        $textMessage = $this->model->findOrFail($textMessageId);
        $textMessage->update($data);
        return $textMessage->fresh(['person', 'user']);
    }

    /**
     * Delete a text message record.
     *
     * @param int $textMessageId The ID of the text message to delete
     * @return bool True if deletion was successful
     */
    public function delete(int $textMessageId): bool
    {
        $textMessage = $this->model->findOrFail($textMessageId);
        return $textMessage->delete();
    }
}
