<?php

namespace App\Repositories\EmailAccounts;

use App\Models\EmailAccount;

interface EmailAccountsRepositoryInterface
{
    public function getAll(int $perPage = 15, int $page = 1): \Illuminate\Contracts\Pagination\LengthAwarePaginator;
    public function findById(int $emailAccountId): ?EmailAccount;
    public function createOrUpdate(array $attributes, array $values): EmailAccount;
    public function delete(EmailAccount $emailAccount): bool;
    public function hasActiveAccounts(): bool;
}
