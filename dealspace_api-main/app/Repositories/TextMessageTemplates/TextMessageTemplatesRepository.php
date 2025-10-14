<?php

namespace App\Repositories\TextMessageTemplates;

use App\Models\TextMessageTemplate;

class TextMessageTemplatesRepository implements TextMessageTemplatesRepositoryInterface
{
    protected $model;

    public function __construct(TextMessageTemplate $model)
    {
        $this->model = $model;
    }

    /**
     * Get all textMessage templates with pagination.
     *
     * @param int $perPage Number of items per page
     * @param int $page Current page number
     * @param string|null $search Search term for filtering
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator Paginated textMessage template records with relationships loaded.
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
     * Find an textMessage template by its ID.
     *
     * @param int $textMessageTemplateId The ID of the textMessage template to find.
     * @return TextMessageTemplate|null The found textMessage template or null if not found.
     */
    public function findById(int $textMessageTemplateId): ?TextMessageTemplate
    {
        return $this->model->with(['user'])->find($textMessageTemplateId);
    }

    /**
     * Create a new textMessage template record.
     *
     * @param array $data The data for the new textMessage template, including:
     * - 'name' (string) The name of the textMessage template.
     * - 'subject' (string) The textMessage subject.
     * - 'body' (string) The HTML body of the textMessage template.
     * - 'is_shared' (boolean) Whether the template is shared.
     * - 'user_id' (int) The ID of the user who owns the template.
     * @return TextMessageTemplate The newly created TextMessageTemplate model instance.
     */
    public function create(array $data): TextMessageTemplate
    {
        return $this->model->create($data);
    }

    /**
     * Update an existing textMessage template.
     *
     * @param TextMessageTemplate $textMessageTemplate The textMessage template to update.
     * @param array $data The updated textMessage template data including:
     * - ['name'] (string) The updated name of the textMessage template.
     * - ['subject'] (string) The updated subject.
     * - ['body'] (string) The updated HTML body.
     * - ['is_shared'] (boolean) The updated sharing status.
     * - ['user_id'] (int) The updated ID of the user who owns the template.
     * @return TextMessageTemplate The updated TextMessageTemplate model instance with fresh relationships.
     */
    public function update(TextMessageTemplate $textMessageTemplate, array $data): TextMessageTemplate
    {
        $textMessageTemplate->update($data);
        return $textMessageTemplate->fresh(['user']);
    }

    /**
     * Delete an textMessage template.
     *
     * @param TextMessageTemplate $textMessageTemplate The textMessage template to delete.
     * @return bool True if deletion was successful, false otherwise.
     */
    public function delete(TextMessageTemplate $textMessageTemplate): bool
    {
        return $textMessageTemplate->delete();
    }

    /**
     * Get textMessage templates by user ID with pagination.
     *
     * @param int $userId The ID of the user.
     * @param int $perPage Number of items per page
     * @param int $page Current page number
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator Paginated textMessage templates owned by the user.
     */
    public function getByUserId(int $userId, int $perPage = 15, int $page = 1)
    {
        return $this->model->with(['user'])
            ->where('user_id', $userId)
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get shared textMessage templates with pagination.
     *
     * @param int $perPage Number of items per page
     * @param int $page Current page number
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator Paginated shared textMessage templates.
     */
    public function getSharedTemplates(int $perPage = 15, int $page = 1)
    {
        return $this->model->with(['user'])
            ->where('is_shared', true)
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Delete all textMessage template records
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
