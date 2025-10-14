<?php

namespace App\Services\CalendarAccounts;

use App\Models\CalendarAccount;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class MicrosoftCalendarOAuthService
{
    private $client;
    private $calendarAccountService;

    public function __construct(CalendarAccountServiceInterface $calendarAccountService)
    {
        $this->calendarAccountService = $calendarAccountService;
        $this->client = new Client();
    }

    public function getAuthUrl(): string
    {
        $params = [
            'client_id' => config('services.microsoft.client_id'),
            'response_type' => 'code',
            'redirect_uri' => config('services.microsoft.calendar_redirect'),
            'scope' => 'https://graph.microsoft.com/Calendars.ReadWrite https://graph.microsoft.com/User.Read offline_access',
            'response_mode' => 'query',
            'state' => csrf_token(),
        ];

        return 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize?' . http_build_query($params);
    }

    public function handleCallback(string $code): CalendarAccount
    {
        $tokenResponse = $this->client->post('https://login.microsoftonline.com/common/oauth2/v2.0/token', [
            'form_params' => [
                'client_id' => config('services.microsoft.client_id'),
                'client_secret' => config('services.microsoft.client_secret'),
                'code' => $code,
                'redirect_uri' => config('services.microsoft.calendar_redirect'),
                'grant_type' => 'authorization_code',
            ]
        ]);

        $token = json_decode($tokenResponse->getBody(), true);

        if (isset($token['error'])) {
            throw new \Exception('OAuth Error: ' . $token['error_description']);
        }

        // Get user info using direct HTTP request
        $userResponse = $this->client->get('https://graph.microsoft.com/v1.0/me', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token['access_token'],
                'Accept' => 'application/json',
            ]
        ]);

        $user = json_decode($userResponse->getBody(), true);

        // Get primary calendar using direct HTTP request
        $calendarResponse = $this->client->get('https://graph.microsoft.com/v1.0/me/calendar', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token['access_token'],
                'Accept' => 'application/json',
            ]
        ]);

        $calendar = json_decode($calendarResponse->getBody(), true);

        return $this->calendarAccountService->createOrUpdate(
            [
                'provider' => 'outlook',
                'email' => $user['mail'] ?: $user['userPrincipalName'],
                'calendar_id' => $calendar['id']
            ],
            [
                'calendar_name' => $calendar['name'],
                'access_token' => encrypt($token['access_token']),
                'refresh_token' => encrypt($token['refresh_token'] ?? ''),
                'token_expires_at' => now()->addSeconds($token['expires_in']),
                'is_active' => true,
                'last_sync_at' => now()
            ]
        );
    }

    public function refreshToken(CalendarAccount $account): bool
    {
        try {
            $response = $this->client->post('https://login.microsoftonline.com/common/oauth2/v2.0/token', [
                'form_params' => [
                    'client_id' => config('services.microsoft.client_id'),
                    'client_secret' => config('services.microsoft.client_secret'),
                    'refresh_token' => decrypt($account->refresh_token),
                    'grant_type' => 'refresh_token',
                ]
            ]);

            $token = json_decode($response->getBody(), true);

            if (isset($token['error'])) {
                Log::error('Microsoft Calendar token refresh failed', [
                    'account_id' => $account->id,
                    'error' => $token['error']
                ]);
                $account->update(['is_active' => false]);
                return false;
            }

            $account->update([
                'access_token' => encrypt($token['access_token']),
                'token_expires_at' => now()->addSeconds($token['expires_in']),
                'is_active' => true
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Microsoft Calendar token refresh exception', [
                'account_id' => $account->id,
                'error' => $e->getMessage()
            ]);
            $account->update(['is_active' => false]);
            return false;
        }
    }

    public function getValidToken(CalendarAccount $account): ?string
    {
        if ($account->isTokenExpired()) {
            if (!$this->refreshToken($account)) {
                return null;
            }
            $account->refresh();
        }

        return decrypt($account->access_token);
    }

    /**
     * Make authenticated request to Microsoft Graph API
     */
    protected function makeGraphRequest(string $endpoint, string $accessToken, string $method = 'GET', array $data = null): array
    {
        $options = [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]
        ];

        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $options['json'] = $data;
        }

        $response = $this->client->request($method, 'https://graph.microsoft.com/v1.0' . $endpoint, $options);

        return json_decode($response->getBody(), true);
    }
}
