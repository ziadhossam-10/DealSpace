<?php

namespace App\Services\EmailAccounts;

use App\Models\EmailAccount;

interface EmailAccountServiceInterface
{
    /**
     * Get all email accounts.
     *
     * @param int $perPage
     * @param int $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAll(int $perPage = 15, int $page = 1);

    /**
     * Find an email account by its ID.
     *
     * @param int $emailAccountId
     * @return EmailAccount
     */
    public function findById(int $emailAccountId): EmailAccount;

    /**
     * Create a new email account or update an existing one.
     *
     * @param array $attributes
     * @param array $values
     * @return EmailAccount
     */
    public function createOrUpdate(array $attributes, array $values): EmailAccount;

    /**
     * Delete an email account.
     *
     * @param int $emailAccountId
     * @return bool
     */
    public function delete(int $emailAccountId): bool;
}
