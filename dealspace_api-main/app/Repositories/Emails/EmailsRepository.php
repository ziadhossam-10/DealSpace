<?php

namespace App\Repositories\Emails;

use App\Models\Email;

class EmailsRepository implements EmailsRepositoryInterface
{
    protected $model;

    public function __construct(Email $model)
    {
        $this->model = $model;
    }

    /**
     * Get all emails for a specific person with pagination.
     *
     * @param int $perPage Number of items per page
     * @param int $page Current page number
     * @param int $personId The ID of the person associated with the emails
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator Paginated email records
     */
    public function getAll(int $personId, int $perPage = 15, int $page = 1): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->model->where('person_id', $personId)
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Find an email by its ID.
     *
     * @param int $emailId The ID of the email to find.
     * @return Email|null The found Email instance or null if not found.
     */
    public function findById(int $emailId): ?Email
    {
        return $this->model->find($emailId);
    }

    /**
     * Create a new email record.
     *
     * @param array $data The data for the new email. Example fields:
     * - 'person_id' (int): Required. The person related to the email.
     * - 'subject' (string): Required. The subject line of the email.
     * - 'body' (string): Required. The body content of the email.
     * - 'to_email' (string): Required. The email address the email was sent to.
     * - 'from_email' (string): Required. The email address the email was sent from.
     * - 'is_incoming' (bool): Optional. True if the email is incoming (defaults to false).
     * - 'external_label' (string): Optional. Descriptive text for the timeline.
     * - 'external_url' (string): Optional. Link for the timeline.
     * - 'user_id' (int): Optional. The user who sent or received the email.
     * @return Email The newly created Email model instance.
     */
    public function create(array $data): Email
    {
        return $this->model->create($data)->fresh();
    }
}
