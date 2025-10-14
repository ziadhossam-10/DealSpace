<?php

namespace App\Services\CalendarAccounts;

use App\Models\CalendarAccount;

interface CalendarAccountServiceInterface
{
    public function createOrUpdate(array $identifiers, array $data): CalendarAccount;
    public function delete(CalendarAccount $account): bool;
    public function setupWebhook(CalendarAccount $account): bool;
    public function refreshWebhook(CalendarAccount $account): bool;
    public function deleteWebhook(CalendarAccount $account): bool;
}
