<?php

namespace App\Services\EmailTemplates;

use App\Models\EmailTemplate;
use App\Repositories\EmailTemplates\EmailTemplatesRepositoryInterface;
use App\Repositories\Users\UsersRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class EmailTemplateService implements EmailTemplateServiceInterface
{
    protected $emailTemplatesRepository;
    protected $usersRepository;

    public function __construct(
        EmailTemplatesRepositoryInterface $emailTemplatesRepository,
        UsersRepositoryInterface $usersRepository
    ) {
        $this->emailTemplatesRepository = $emailTemplatesRepository;
        $this->usersRepository = $usersRepository;
    }

    /**
     * Get all email templates.
     *
     * @param int $perPage
     * @param int $page
     * @param string|null $search
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAll(int $userId, int $perPage = 15, int $page = 1, string $search = null)
    {
        return $this->emailTemplatesRepository->getAll($userId, $perPage, $page, $search);
    }

    /**
     * Get an email template by ID.
     *
     * @param int $emailTemplateId
     * @return EmailTemplate
     * @throws ModelNotFoundException
     */
    public function findById(int $emailTemplateId): EmailTemplate
    {
        $emailTemplate = $this->emailTemplatesRepository->findById($emailTemplateId);
        if (!$emailTemplate) {
            throw new ModelNotFoundException('Email template not found');
        }
        return $emailTemplate;
    }

    /**
     * Create a new email template.
     *
     * @param array $data The complete email template data including:
     * - 'name' (string) The name of the email template
     * - 'subject' (string) The email subject
     * - 'body' (string) The HTML body of the email template
     * - 'is_shared' (boolean) Whether the template is shared
     * - 'user_id' (int) The ID of the user who owns the template
     * @return EmailTemplate
     * @throws ModelNotFoundException
     */
    public function create(array $data): EmailTemplate
    {
        return DB::transaction(function () use ($data) {
            // Verify that the user exists before creating the email template
            $user = $this->usersRepository->findById($data['user_id']);
            if (!$user) {
                throw new ModelNotFoundException('User not found');
            }

            // Create the email template
            return $this->emailTemplatesRepository->create($data);
        });
    }

    /**
     * Update an existing email template.
     *
     * @param int $emailTemplateId
     * @param array $data The email template data to update
     * @return EmailTemplate
     * @throws ModelNotFoundException
     */
    public function update(int $emailTemplateId, array $data): EmailTemplate
    {
        return DB::transaction(function () use ($emailTemplateId, $data) {
            $emailTemplate = $this->emailTemplatesRepository->findById($emailTemplateId);
            if (!$emailTemplate) {
                throw new ModelNotFoundException('Email template not found');
            }

            // If changing owner, verify that the new user exists
            if (isset($data['user_id'])) {
                $user = $this->usersRepository->findById($data['user_id']);
                if (!$user) {
                    throw new ModelNotFoundException('New owner user not found');
                }
            }

            // Update the email template
            return $this->emailTemplatesRepository->update($emailTemplate, $data);
        });
    }

    /**
     * Delete an email template.
     *
     * @param int $emailTemplateId
     * @return bool
     * @throws ModelNotFoundException
     */
    public function delete(int $emailTemplateId): bool
    {
        $emailTemplate = $this->emailTemplatesRepository->findById($emailTemplateId);
        if (!$emailTemplate) {
            throw new ModelNotFoundException('Email template not found');
        }

        return $this->emailTemplatesRepository->delete($emailTemplate);
    }

    /**
     * Deletes multiple email templates in a single transaction.
     *
     * This method deletes multiple email templates at once, based on the parameters
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
                    return $this->emailTemplatesRepository->deleteAllExcept($exceptionIds);
                } else {
                    // Delete all
                    return $this->emailTemplatesRepository->deleteAll();
                }
            } else {
                if (!empty($ids)) {
                    // Delete specific ids
                    return $this->emailTemplatesRepository->deleteSome($ids);
                } else {
                    // No records to delete
                    return 0;
                }
            }
        });
    }
}
