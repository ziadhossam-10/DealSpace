<?php

namespace App\Services\EmailAccounts;

use App\Models\EmailAccount;
use Google\Client;
use Google\Service\Gmail;

class GoogleOAuthService
{
    private $client;
    private $emailAccountService;


    public function __construct(EmailAccountServiceInterface $emailAccountService)
    {
        $this->emailAccountService = $emailAccountService;
        $this->client = new Client();
        $this->client->setClientId(config('services.google.client_id'));
        $this->client->setClientSecret(config('services.google.client_secret'));
        $this->client->setRedirectUri(config('services.google.redirect'));
        $this->client->addScope(Gmail::GMAIL_READONLY);
        $this->client->addScope(Gmail::GMAIL_SEND);
        $this->client->addScope(Gmail::GMAIL_MODIFY);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');
    }

    public function getAuthUrl(): string
    {
        return $this->client->createAuthUrl();
    }

    public function handleCallback(string $code): EmailAccount
    {
        $token = $this->client->fetchAccessTokenWithAuthCode($code);

        if (isset($token['error'])) {
            throw new \Exception('OAuth Error: ' . $token['error_description']);
        }

        // Get user email
        $this->client->setAccessToken($token);
        $gmail = new Gmail($this->client);
        $profile = $gmail->users->getProfile('me');

        return $this->emailAccountService->createOrUpdate(
            [
                'provider' => 'gmail',
                'email' => $profile->getEmailAddress()
            ],
            [
                'access_token' => encrypt($token['access_token']),
                'refresh_token' => encrypt($token['refresh_token'] ?? ''),
                'token_expires_at' => now()->addSeconds($token['expires_in']),
                'is_active' => true
            ]
        );
    }

    public function refreshToken(EmailAccount $account): bool
    {
        try {
            $this->client->setAccessToken([
                'access_token' => decrypt($account->access_token),
                'refresh_token' => decrypt($account->refresh_token),
            ]);

            if ($this->client->isAccessTokenExpired()) {
                $newToken = $this->client->fetchAccessTokenWithRefreshToken();

                if (isset($newToken['error'])) {
                    $account->update(['is_active' => false]);
                    return false;
                }

                $account->update([
                    'access_token' => encrypt($newToken['access_token']),
                    'token_expires_at' => now()->addSeconds($newToken['expires_in']),
                    'is_active' => true
                ]);
            }

            return true;
        } catch (\Exception $e) {
            $account->update(['is_active' => false]);
            return false;
        }
    }

    public function getValidToken(EmailAccount $account): ?string
    {
        if ($account->isTokenExpired()) {
            if (!$this->refreshToken($account)) {
                return null;
            }
            $account->refresh();
        }

        return decrypt($account->access_token);
    }
}
