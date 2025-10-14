<?php

namespace App\Services\EmailAccounts;

use App\Models\EmailAccount;
use App\Repositories\EmailAccounts\EmailAccountsRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

class EmailAccountService implements EmailAccountServiceInterface
{
    protected $emailAccountsRepository;
    protected $webhookRegisterationService;

    public function __construct(EmailAccountsRepositoryInterface $emailAccountsRepository, WebhookRegistrationService $webhookRegisterationService)
    {
        $this->emailAccountsRepository = $emailAccountsRepository;
        $this->webhookRegisterationService = $webhookRegisterationService;
    }

    /**
     * Get all email accounts.
     *
     * @param int $perPage
     * @param int $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAll(int $perPage = 15, int $page = 1)
    {
        return $this->emailAccountsRepository->getAll($perPage, $page);
    }

    /**
     * Find an email account by its ID.
     *
     * @param int $emailAccountId
     * @return EmailAccount
     * @throws ModelNotFoundException
     */
    public function findById(int $emailAccountId): EmailAccount
    {
        $emailAccount = $this->emailAccountsRepository->findById($emailAccountId);
        if (!$emailAccount) {
            throw new ModelNotFoundException('Email account not found');
        }

        return $emailAccount;
    }

    /**
     * Create a new email account or update an existing one.
     *
     * @param array $attributes The attributes to search for an existing record including:
     * - 'email' (string) The email address to search for
     * - 'tenant_id' (int) The tenant ID to search for
     * @param array $values The values to create or update the record with including:
     * - 'provider' (string) The email provider
     * - 'access_token' (string) The OAuth access token
     * - 'refresh_token' (string) The OAuth refresh token
     * - 'token_expires_at' (datetime) When the token expires
     * - 'is_active' (boolean) Whether the account is active
     * @return EmailAccount
     */
    public function createOrUpdate(array $attributes, array $values): EmailAccount
    {
        $account = $this->emailAccountsRepository->createOrUpdate($attributes, $values);

        try {
            $webhookRegistered = $this->webhookRegisterationService->registerWebhook($account);

            if (!$webhookRegistered) {
                Log::warning("Failed to register webhook for new account", [
                    'account_id' => $account->id,
                    'provider' => $account->provider
                ]);

                // Don't fail account creation, but mark for retry
                $account->update(['webhook_registration_failed' => true]);
            }
        } catch (\Exception $e) {
            Log::error("Webhook registration error for new account", [
                'account_id' => $account->id,
                'error' => $e->getMessage()
            ]);

            $account->update(['webhook_registration_failed' => true]);
        }
        return $account;
    }

    /**
     * Delete an email account.
     *
     * @param int $emailAccountId
     * @return bool
     * @throws ModelNotFoundException
     */
    public function delete(int $emailAccountId): bool
    {
        $emailAccount = $this->emailAccountsRepository->findById($emailAccountId);
        if (!$emailAccount) {
            throw new ModelNotFoundException('Email account not found');
        }

        return $this->emailAccountsRepository->delete($emailAccount);
    }
}
