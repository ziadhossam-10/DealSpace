<?php

namespace App\Services\TextMessages;

use App\Models\TextMessage;

interface TextMessageServiceInterface
{
    /**
     * Get all text messages for a person.
     *
     * @param int $perPage
     * @param int $page
     * @param int $personId
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAll(int $perPage = 15, int $page = 1, int $personId);

    /**
     * Get a text message by ID.
     *
     * @param int $textMessageId
     * @return TextMessage
     */
    public function findById(int $textMessageId): TextMessage;

    /**
     * Create a new text message.
     *
     * @param array $data
     * @return TextMessage
     */
    public function create(array $data): TextMessage;

    public function processIncomingMessage(array $webhookData): TextMessage;
}
