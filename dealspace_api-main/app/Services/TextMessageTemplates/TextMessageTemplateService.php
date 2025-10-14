<?php

namespace App\Services\TextMessageTemplates;

use App\Models\TextMessageTemplate;
use App\Repositories\TextMessageTemplates\TextMessageTemplatesRepositoryInterface;
use App\Repositories\Users\UsersRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class TextMessageTemplateService implements TextMessageTemplateServiceInterface
{
    protected $textMessageTemplatesRepository;
    protected $usersRepository;

    public function __construct(
        TextMessageTemplatesRepositoryInterface $textMessageTemplatesRepository,
        UsersRepositoryInterface $usersRepository
    ) {
        $this->textMessageTemplatesRepository = $textMessageTemplatesRepository;
        $this->usersRepository = $usersRepository;
    }

    /**
     * Get all textMessage templates.
     *
     * @param int $perPage
     * @param int $page
     * @param string|null $search
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAll(int $userId, int $perPage = 15, int $page = 1, string $search = null)
    {
        return $this->textMessageTemplatesRepository->getAll($userId, $perPage, $page, $search);
    }

    /**
     * Get an textMessage template by ID.
     *
     * @param int $textMessageTemplateId
     * @return TextMessageTemplate
     * @throws ModelNotFoundException
     */
    public function findById(int $textMessageTemplateId): TextMessageTemplate
    {
        $textMessageTemplate = $this->textMessageTemplatesRepository->findById($textMessageTemplateId);
        if (!$textMessageTemplate) {
            throw new ModelNotFoundException('TextMessage template not found');
        }
        return $textMessageTemplate;
    }

    /**
     * Create a new textMessage template.
     *
     * @param array $data The complete textMessage template data including:
     * - 'name' (string) The name of the textMessage template
     * - 'subject' (string) The textMessage subject
     * - 'body' (string) The HTML body of the textMessage template
     * - 'is_shared' (boolean) Whether the template is shared
     * - 'user_id' (int) The ID of the user who owns the template
     * @return TextMessageTemplate
     * @throws ModelNotFoundException
     */
    public function create(array $data): TextMessageTemplate
    {
        return DB::transaction(function () use ($data) {
            // Verify that the user exists before creating the textMessage template
            $user = $this->usersRepository->findById($data['user_id']);
            if (!$user) {
                throw new ModelNotFoundException('User not found');
            }

            // Create the textMessage template
            return $this->textMessageTemplatesRepository->create($data);
        });
    }

    /**
     * Update an existing textMessage template.
     *
     * @param int $textMessageTemplateId
     * @param array $data The textMessage template data to update
     * @return TextMessageTemplate
     * @throws ModelNotFoundException
     */
    public function update(int $textMessageTemplateId, array $data): TextMessageTemplate
    {
        return DB::transaction(function () use ($textMessageTemplateId, $data) {
            $textMessageTemplate = $this->textMessageTemplatesRepository->findById($textMessageTemplateId);
            if (!$textMessageTemplate) {
                throw new ModelNotFoundException('TextMessage template not found');
            }

            // If changing owner, verify that the new user exists
            if (isset($data['user_id'])) {
                $user = $this->usersRepository->findById($data['user_id']);
                if (!$user) {
                    throw new ModelNotFoundException('New owner user not found');
                }
            }

            // Update the textMessage template
            return $this->textMessageTemplatesRepository->update($textMessageTemplate, $data);
        });
    }

    /**
     * Delete an textMessage template.
     *
     * @param int $textMessageTemplateId
     * @return bool
     * @throws ModelNotFoundException
     */
    public function delete(int $textMessageTemplateId): bool
    {
        $textMessageTemplate = $this->textMessageTemplatesRepository->findById($textMessageTemplateId);
        if (!$textMessageTemplate) {
            throw new ModelNotFoundException('TextMessage template not found');
        }

        return $this->textMessageTemplatesRepository->delete($textMessageTemplate);
    }

    /**
     * Deletes multiple textMessage templates in a single transaction.
     *
     * This method deletes multiple textMessage templates at once, based on the parameters
     * provided. If all templates are selected, then all templates except those with
     * IDs in the exception_ids list are deleted. If specific IDs are provided,
     * those templates are deleted.
     *
     * The deletion is wrapped in a database transaction to ensure data
     * integrity.
     *
     * @param array $params Parameters to control the deletion operation
     *     - is_all_selected (bool): Delete all templates except those in exception_ids
     *     - exception_ids (array): IDs to exclude from deletion
     *     - ids (array): IDs of templates to delete
     * @return int Number of deleted records
     */
    public function bulkDelete(array $params): int
    {
        return DB::transaction(function () use ($params) {
            $isAllSelected = $params['is_all_selected'] ?? false;
            $exceptionIds = $params['exception_ids'] ?? [];
            $ids = $params['ids'] ?? [];

            if ($isAllSelected) {
                if (!empty($exceptionIds)) {
                    // Delete all except those in exception_ids
                    return $this->textMessageTemplatesRepository->deleteAllExcept($exceptionIds);
                } else {
                    // Delete all
                    return $this->textMessageTemplatesRepository->deleteAll();
                }
            } else {
                if (!empty($ids)) {
                    // Delete specific ids
                    return $this->textMessageTemplatesRepository->deleteSome($ids);
                } else {
                    // No records to delete
                    return 0;
                }
            }
        });
    }
}
