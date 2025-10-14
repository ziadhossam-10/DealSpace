<?php

namespace App\Repositories\Deals;

use App\Models\DealAttachment;
use Illuminate\Support\Collection;

class DealAttachmentsRepository implements DealAttachmentsRepositoryInterface
{
    protected $model;

    public function __construct(DealAttachment $model)
    {
        $this->model = $model;
    }

    /**
     * Get all attachments for a specific deal.
     *
     * @param int $dealId The ID of the deal.
     * @return Collection Collection of DealAttachment objects.
     */
    public function all(int $dealId): Collection
    {
        return $this->model->where('deal_id', $dealId)->get();
    }

    /**
     * Find a attachment by its ID and the ID of the deal it belongs to.
     *
     * @param int $attachmentId The ID of the attachment to find.
     * @param int $dealId The ID of the deal the attachment belongs to.
     *
     * @return DealAttachment|null The found attachment or null if not found.
     */
    public function find(int $attachmentId, int $dealId): ?DealAttachment
    {
        return $this->model->where('deal_id', $dealId)->find($attachmentId);
    }

    /**
     * Create a new attachment record for a specific deal.
     *
     * @param int $dealId The ID of the deal to associate the attachment with.
     * @param array $data The data for the new attachment, including:
     * ['name'] (string) The attachment name.
     * ['path'] (string) The attachment path.
     * ['type'] (string) The type or category of the attachment.
     * ['size'] (int) The attachment size in bytes.
     * ['mime_type'] (string) The MIME type of the attachment.
     * ['is_primary'] (bool) Whether this is the primary attachment.
     * ['description'] (string) Optional description of the attachment.
     *
     * @return DealAttachment The newly created DealAttachment model instance.
     */
    public function create(int $dealId, array $data): DealAttachment
    {
        $data['deal_id'] = $dealId;
        return $this->model->create($data);
    }

    /**
     * Update an existing attachment for a deal.
     *
     * @param DealAttachment $attachment The attachment to update.
     * @param array $data The updated attachment data including:
     * ['name'] (string) The attachment name.
     * ['path'] (string) The attachment path.
     * ['type'] (string) The type or category of the attachment.
     * ['size'] (int) The attachment size in bytes.
     * ['mime_type'] (string) The MIME type of the attachment.
     * ['is_primary'] (bool) Whether this is the primary attachment.
     * ['description'] (string) Optional description of the attachment.
     *
     * @return DealAttachment The updated DealAttachment model instance.
     */
    public function update(DealAttachment $attachment, array $data): DealAttachment
    {
        $attachment->update($data);
        return $attachment;
    }

    /**
     * Delete a attachment from a deal.
     *
     * @param DealAttachment $attachment The attachment to delete.
     * @return bool True if deletion was successful, false otherwise.
     */
    public function delete(DealAttachment $attachment): bool
    {
        return $attachment->delete();
    }
}
