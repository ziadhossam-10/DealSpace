<?php

namespace App\Repositories\EmailAccounts;

use App\Models\EmailAccount;

class EmailAccountsRepository implements EmailAccountsRepositoryInterface
{
    protected $model;

    public function __construct(EmailAccount $model)
    {
        $this->model = $model;
    }

    /**
     * Get all email accounts with pagination.
     *
     * @param int $perPage Number of items per page
     * @param int $page Current page number
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator Paginated email account records.
     */
    public function getAll(int $perPage = 15, int $page = 1): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->model->latest()
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Find an email account by its ID.
     *
     * @param int $emailAccountId The ID of the email account to find.
     * @return EmailAccount|null The found email account or null if not found.
     */
    public function findById(int $emailAccountId): ?EmailAccount
    {
        return $this->model->find($emailAccountId);
    }

    /**
     * Create a new email account or update an existing one.
     *
     * @param array $attributes The attributes to search for an existing record.
     * @param array $values The values to create or update the record with.
     * @return EmailAccount The created or updated EmailAccount model instance.
     */
    public function createOrUpdate(array $attributes, array $values): EmailAccount
    {
        return $this->model->updateOrCreate($attributes, $values);
    }

    /**
     * Delete an email account.
     *
     * @param EmailAccount $emailAccount The email account to delete.
     * @return bool True if deletion was successful, false otherwise.
     */
    public function delete(EmailAccount $emailAccount): bool
    {
        return $emailAccount->delete();
    }

    /**
     * Check if there are any active email accounts.
     *
     * @return bool True if there are active accounts, false otherwise.
     */
    public function hasActiveAccounts(): bool
    {
        return $this->model->where('is_active', true)->exists();
    }
}
