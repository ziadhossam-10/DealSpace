<?php

namespace App\Repositories\Deals;

use App\Models\DealAttachment;
use Illuminate\Support\Collection;

interface DealAttachmentsRepositoryInterface
{
    /**
     * Get all attachments for a specific deal.
     *
     * @param int $dealId The ID of the deal.
     * @return Collection Collection of DealAttachment objects.
     */
    public function all(int $dealId): Collection;

    /**
     * Find a attachment by its ID and the ID of the deal it belongs to.
     *
     * @param int $attachmentId The ID of the attachment to find.
     * @param int $dealId The ID of the deal the attachment belongs to.
     * @return DealAttachment|null The found attachment or null if not found.
     */
    public function find(int $attachmentId, int $dealId): ?DealAttachment;

    /**
     * Create a new attachment record for a specific deal.
     *
     * @param int $dealId The ID of the deal to associate the attachment with.
     * @param array $data The data for the new attachment.
     * @return DealAttachment The newly created DealAttachment model instance.
     */
    public function create(int $dealId, array $data): DealAttachment;

    /**
     * Update an existing attachment for a deal.
     *
     * @param DealAttachment $attachment The attachment to update.
     * @param array $data The updated attachment data.
     * @return DealAttachment The updated DealAttachment model instance.
     */
    public function update(DealAttachment $attachment, array $data): DealAttachment;

    /**
     * Delete a attachment from a deal.
     *
     * @param DealAttachment $attachment The attachment to delete.
     * @return bool True if deletion was successful, false otherwise.
     */
    public function delete(DealAttachment $attachment): bool;
}
