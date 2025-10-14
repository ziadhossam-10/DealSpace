<?php

namespace App\Repositories\EmailTemplates;

use App\Models\EmailTemplate;

class EmailTemplatesRepository implements EmailTemplatesRepositoryInterface
{
    protected $model;

    public function __construct(EmailTemplate $model)
    {
        $this->model = $model;
    }

    /**
     * Get all email templates with pagination.
     *
     * @param int $perPage Number of items per page
     * @param int $page Current page number
     * @param string|null $search Search term for filtering
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator Paginated email template records with relationships loaded.
     */
    public function getAll(int $userId, int $perPage = 15, int $page = 1, string $search = null)
    {
        $templateQuery = $this->model->query();

        if ($search) {
            $templateQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        $templateQuery->where('user_id', $userId)->orWhere('is_shared', true)
            ->latest();

        return $templateQuery->with(['user'])
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Find an email template by its ID.
     *
     * @param int $emailTemplateId The ID of the email template to find.
     * @return EmailTemplate|null The found email template or null if not found.
     */
    public function findById(int $emailTemplateId): ?EmailTemplate
    {
        return $this->model->with(['user'])->find($emailTemplateId);
    }

    /**
     * Create a new email template record.
     *
     * @param array $data The data for the new email template, including:
     * - 'name' (string) The name of the email template.
     * - 'subject' (string) The email subject.
     * - 'body' (string) The HTML body of the email template.
     * - 'is_shared' (boolean) Whether the template is shared.
     * - 'user_id' (int) The ID of the user who owns the template.
     * @return EmailTemplate The newly created EmailTemplate model instance.
     */
    public function create(array $data): EmailTemplate
    {
        return $this->model->create($data);
    }

    /**
     * Update an existing email template.
     *
     * @param EmailTemplate $emailTemplate The email template to update.
     * @param array $data The updated email template data including:
     * - ['name'] (string) The updated name of the email template.
     * - ['subject'] (string) The updated subject.
     * - ['body'] (string) The updated HTML body.
     * - ['is_shared'] (boolean) The updated sharing status.
     * - ['user_id'] (int) The updated ID of the user who owns the template.
     * @return EmailTemplate The updated EmailTemplate model instance with fresh relationships.
     */
    public function update(EmailTemplate $emailTemplate, array $data): EmailTemplate
    {
        $emailTemplate->update($data);
        return $emailTemplate->fresh(['user']);
    }

    /**
     * Delete an email template.
     *
     * @param EmailTemplate $emailTemplate The email template to delete.
     * @return bool True if deletion was successful, false otherwise.
     */
    public function delete(EmailTemplate $emailTemplate): bool
    {
        return $emailTemplate->delete();
    }

    /**
     * Get email templates by user ID with pagination.
     *
     * @param int $userId The ID of the user.
     * @param int $perPage Number of items per page
     * @param int $page Current page number
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator Paginated email templates owned by the user.
     */
    public function getByUserId(int $userId, int $perPage = 15, int $page = 1)
    {
        return $this->model->with(['user'])
            ->where('user_id', $userId)
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get shared email templates with pagination.
     *
     * @param int $perPage Number of items per page
     * @param int $page Current page number
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator Paginated shared email templates.
     */
    public function getSharedTemplates(int $perPage = 15, int $page = 1)
    {
        return $this->model->with(['user'])
            ->where('is_shared', true)
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Delete all email template records
     *
     * @return int Number of deleted records
     */
    public function deleteAll(): int
    {
        return $this->model->query()->delete();
    }

    /**
     * Delete all records except those with specified IDs
     *
     * @param array $ids IDs to exclude from deletion
     * @return int Number of deleted records
     */
    public function deleteAllExcept(array $ids): int
    {
        return $this->model->whereNotIn('id', $ids)->delete();
    }

    /**
     * Delete multiple records by their IDs
     *
     * @param array $ids IDs of records to delete
     * @return int Number of deleted records
     */
    public function deleteSome(array $ids): int
    {
        return $this->model->whereIn('id', $ids)->delete();
    }
}
