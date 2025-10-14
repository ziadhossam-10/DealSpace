<?php

namespace App\Services\EmailAccounts;

use App\Models\EmailAccount;
use Illuminate\Support\Facades\Http;

class MicrosoftOAuthService
{
    private $clientId;
    private $clientSecret;
    private $redirectUri;
    private $scope = 'https://graph.microsoft.com/Mail.Read https://graph.microsoft.com/Mail.Send https://graph.microsoft.com/Mail.ReadWrite offline_access';
    private $emailAccountService;

    public function __construct(EmailAccountServiceInterface $emailAccountService)
    {
        $this->emailAccountService = $emailAccountService;
        $this->clientId = config('services.microsoft.client_id');
        $this->clientSecret = config('services.microsoft.client_secret');
        $this->redirectUri = config('services.microsoft.redirect');
    }

    public function getAuthUrl(): string
    {
        $params = [
            'client_id' => $this->clientId,
            'response_type' => 'code',
            'redirect_uri' => $this->redirectUri,
            'scope' => $this->scope,
            'response_mode' => 'query',
            'prompt' => 'consent'
        ];

        return 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize?' . http_build_query($params);
    }

    public function handleCallback(string $code): EmailAccount
    {
        $response = Http::asForm()->post('https://login.microsoftonline.com/common/oauth2/v2.0/token', [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'redirect_uri' => $this->redirectUri,
            'grant_type' => 'authorization_code',
        ]);

        $token = $response->json();

        if (isset($token['error'])) {
            throw new \Exception('OAuth Error: ' . $token['error_description']);
        }

        // Get user email
        $userResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token['access_token']
        ])->get('https://graph.microsoft.com/v1.0/me');

        $user = $userResponse->json();

        return $this->emailAccountService->createOrUpdate(
            [
                'provider' => 'outlook',
                'email' => $user['mail'] ?? $user['userPrincipalName']
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
            $response = Http::asForm()->post('https://login.microsoftonline.com/common/oauth2/v2.0/token', [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'refresh_token' => decrypt($account->refresh_token),
                'grant_type' => 'refresh_token',
            ]);

            $token = $response->json();

            if (isset($token['error'])) {
                $account->update(['is_active' => false]);
                return false;
            }

            $account->update([
                'access_token' => encrypt($token['access_token']),
                'refresh_token' => encrypt($token['refresh_token'] ?? decrypt($account->refresh_token)),
                'token_expires_at' => now()->addSeconds($token['expires_in']),
                'is_active' => true
            ]);

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
