<?php

namespace App\Repositories\TextMessages;

use App\Models\TextMessage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TextMessagesRepositoryInterface
{
    /**
     * Get all text messages for a specific person with pagination.
     *
     * @param int $perPage Number of items per page
     * @param int $page Current page number
     * @param int $personId The ID of the person associated with the text messages
     * @return LengthAwarePaginator Paginated text message records
     */
    public function getAll(int $perPage = 15, int $page = 1, int $personId): LengthAwarePaginator;

    /**
     * Get all text messages with optional person filtering.
     *
     * @param int $perPage Number of items per page
     * @param int $page Current page number
     * @param int|null $personId Optional person ID filter
     * @return LengthAwarePaginator Paginated text message records
     */
    public function getAllWithOptionalPersonFilter(int $perPage = 15, int $page = 1, ?int $personId = null): LengthAwarePaginator;

    /**
     * Find a text message by its ID.
     *
     * @param int $textMessageId The ID of the text message to find.
     * @return TextMessage|null The found TextMessage instance or null if not found.
     */
    public function findById(int $textMessageId): ?TextMessage;

    /**
     * Create a new text message record.
     *
     * @param array $data The data for the new text message
     * @return TextMessage The newly created TextMessage model instance.
     */
    public function create(array $data): TextMessage;

    /**
     * Update an existing text message record.
     *
     * @param int $textMessageId The ID of the text message to update
     * @param array $data The data to update
     * @return TextMessage The updated TextMessage model instance.
     */
    public function update(int $textMessageId, array $data): TextMessage;

    /**
     * Delete a text message record.
     *
     * @param int $textMessageId The ID of the text message to delete
     * @return bool True if deletion was successful
     */
    public function delete(int $textMessageId): bool;
}
