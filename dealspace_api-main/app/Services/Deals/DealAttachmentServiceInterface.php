<?php

namespace App\Services\Deals;

use App\Models\DealAttachment;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

interface DealAttachmentServiceInterface
{
    /**
     * Get all attachments for a specific deal.
     *
     * @param int $dealId The ID of the deal
     * @return Collection Collection of attachments
     * @throws ModelNotFoundException
     */
    public function getAll(int $dealId): Collection;

    /**
     * Get a specific attachment for a deal.
     *
     * @param int $dealId The ID of the deal
     * @param int $attachmentId The ID of the attachment
     * @return DealAttachment
     * @throws ModelNotFoundException
     */
    public function findById(int $dealId, int $attachmentId): DealAttachment;

    /**
     * Add a new attachment to a deal.
     *
     * @param int $dealId The ID of the deal
     * @param array $data The attachment data
     * @return DealAttachment
     * @throws ModelNotFoundException
     */
    public function create(int $dealId, array $data): DealAttachment;

    /**
     * Update an existing attachment for a deal.
     *
     * @param int $dealId
     * @param int $attachmentId
     * @param array $data
     * @return DealAttachment
     * @throws ModelNotFoundException
     */
    public function update(int $dealId, int $attachmentId, array $data): DealAttachment;

    /**
     * Delete a attachment for a deal.
     *
     * @param int $dealId
     * @param int $attachmentId
     * @return bool
     * @throws ModelNotFoundException
     */
    public function delete(int $dealId, int $attachmentId): bool;
}
