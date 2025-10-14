<?php

namespace App\Services\Deals;

use App\Models\DealAttachment;
use App\Repositories\Deals\DealsRepositoryInterface;
use App\Repositories\Deals\DealAttachmentsRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

class DealAttachmentService implements DealAttachmentServiceInterface
{
    protected $dealRepository;
    protected $attachmentRepository;

    public function __construct(
        DealsRepositoryInterface $dealRepository,
        DealAttachmentsRepositoryInterface $attachmentRepository
    ) {
        $this->dealRepository = $dealRepository;
        $this->attachmentRepository = $attachmentRepository;
    }

    /**
     * Get all attachments for a specific deal.
     *
     * @param int $dealId The ID of the deal
     * @return Collection Collection of attachments
     * @throws ModelNotFoundException
     */
    public function getAll(int $dealId): Collection
    {
        // Verify the deal exists
        $this->dealRepository->findById($dealId);

        // Get all attachments for this deal
        return $this->attachmentRepository->all($dealId);
    }

    /**
     * Get a specific attachment for a deal.
     *
     * @param int $dealId The ID of the deal
     * @param int $attachmentId The ID of the attachment
     * @return DealAttachment
     * @throws ModelNotFoundException
     */
    public function findById(int $dealId, int $attachmentId): DealAttachment
    {
        $attachment = $this->attachmentRepository->find($attachmentId, $dealId);

        if (!$attachment) {
            throw new ModelNotFoundException('Attachment not found for this deal');
        }

        return $attachment;
    }

    /**
     * Add a new attachment to a deal.
     *
     * @param int $dealId The ID of the deal
     * @param array $data The attachment data including:
     * - 'name' (string) The attachment name
     * - 'path' (string) The attachment path
     * - ['type'] (string) The attachment type or category
     * - ['size'] (int) The attachment size in bytes
     * - ['mime_type'] (string) The MIME type
     * - ['is_primary'] (bool) Whether this is the primary attachment
     * - ['description'] (string) Optional description
     * @return DealAttachment
     */
    public function create(int $dealId, array $data): DealAttachment
    {
        $deal = $this->dealRepository->findById($dealId);

        if (!$deal) {
            throw new ModelNotFoundException('Deal not found');
        }

        return $this->attachmentRepository->create($dealId, $data);
    }

    /**
     * Update an existing attachment for a deal.
     *
     * @param int $dealId
     * @param int $attachmentId
     * @param array $data
     * @return DealAttachment
     * @throws ModelNotFoundException
     */
    public function update(int $dealId, int $attachmentId, array $data): DealAttachment
    {
        $attachment = $this->attachmentRepository->find($attachmentId, $dealId);

        if (!$attachment) {
            throw new ModelNotFoundException('Attachment not found for this deal');
        }

        return $this->attachmentRepository->update($attachment, $data);
    }

    /**
     * Delete a attachment for a deal.
     *
     * @param int $dealId
     * @param int $attachmentId
     * @return bool
     * @throws ModelNotFoundException
     */
    public function delete(int $dealId, int $attachmentId): bool
    {
        $attachment = $this->attachmentRepository->find($attachmentId, $dealId);

        if (!$attachment) {
            throw new ModelNotFoundException('Attachment not found for this deal');
        }

        return $this->attachmentRepository->delete($attachment);
    }
}
