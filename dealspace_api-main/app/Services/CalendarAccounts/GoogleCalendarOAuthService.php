<?php

namespace App\Services\CalendarAccounts;

use App\Models\CalendarAccount;
use Google\Client;
use Google\Service\Calendar;
use Google\Service\Tasks;
use Illuminate\Support\Facades\Log;

class GoogleCalendarOAuthService
{
    private $client;
    private $calendarAccountService;

    public function __construct(CalendarAccountServiceInterface $calendarAccountService)
    {
        $this->calendarAccountService = $calendarAccountService;
        $this->client = new Client();
        $this->client->setClientId(config('services.google.client_id_calendar'));
        $this->client->setClientSecret(config('services.google.client_secret_calendar'));
        $this->client->setRedirectUri(config('services.google.calendar_redirect'));

        // Add calendar scopes
        $this->client->addScope(Calendar::CALENDAR);
        $this->client->addScope(Calendar::CALENDAR_EVENTS);

        // Add tasks scopes
        $this->client->addScope(Tasks::TASKS);
        $this->client->addScope(Tasks::TASKS_READONLY);

        // Add user info scope
        $this->client->addScope('https://www.googleapis.com/auth/userinfo.email');

        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');
    }

    public function getAuthUrl(): string
    {
        return $this->client->createAuthUrl();
    }

    public function handleCallback(string $code): CalendarAccount
    {
        $token = $this->client->fetchAccessTokenWithAuthCode($code);

        Log::info('Google Calendar OAuth token received', [
            'token' => $token,
            'expires_in' => $token['expires_in'] ?? null
        ]);

        if (isset($token['error'])) {
            throw new \Exception('OAuth Error: ' . $token['error_description']);
        }

        // Get user email and calendar info
        $this->client->setAccessToken($token);
        $calendar = new Calendar($this->client);

        // Get primary calendar
        $calendarList = $calendar->calendarList->listCalendarList();
        $primaryCalendar = null;

        Log::info('Fetching primary calendar from Google Calendar API');
        foreach ($calendarList->getItems() as $cal) {
            if ($cal->getPrimary()) {
                $primaryCalendar = $cal;
                break;
            }
        }

        if (!$primaryCalendar) {
            throw new \Exception('No primary calendar found');
        }

        // Get user info for email
        $oauth2 = new \Google\Service\Oauth2($this->client);
        $userInfo = $oauth2->userinfo->get();

        Log::info('Google Calendar user info fetched', [
            'email' => $userInfo->email,
            'calendar_id' => $primaryCalendar->getId(),
            'calendar_name' => $primaryCalendar->getSummary()
        ]);

        return $this->calendarAccountService->createOrUpdate(
            [
                'provider' => 'google',
                'email' => $userInfo->email,
                'calendar_id' => $primaryCalendar->getId()
            ],
            [
                'calendar_name' => $primaryCalendar->getSummary(),
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
            $this->client->setAccessToken([
                'access_token' => decrypt($account->access_token),
                'refresh_token' => decrypt($account->refresh_token),
            ]);

            if ($this->client->isAccessTokenExpired()) {
                $newToken = $this->client->fetchAccessTokenWithRefreshToken();

                if (isset($newToken['error'])) {
                    Log::error('Google Calendar token refresh failed', [
                        'account_id' => $account->id,
                        'newToken' => $newToken
                    ]);
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
            Log::error('Google Calendar token refresh exception', [
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

    public function getCalendarService(CalendarAccount $account): ?Calendar
    {
        $accessToken = $this->getValidToken($account);
        if (!$accessToken) {
            return null;
        }

        $this->client->setAccessToken($accessToken);
        return new Calendar($this->client);
    }

    public function getTasksService(CalendarAccount $account): ?Tasks
    {
        $accessToken = $this->getValidToken($account);
        if (!$accessToken) {
            return null;
        }

        $this->client->setAccessToken($accessToken);
        return new Tasks($this->client);
    }
}