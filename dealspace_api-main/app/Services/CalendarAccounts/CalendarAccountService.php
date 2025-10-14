<?php

namespace App\Services\CalendarAccounts;

use App\Models\CalendarAccount;
use Google\Client;
use Google\Service\Calendar;
use GuzzleHttp\Client as HttpClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CalendarAccountService implements CalendarAccountServiceInterface
{
    private $httpClient;

    public function __construct()
    {
        $this->httpClient = new HttpClient();
    }

    public function createOrUpdate(array $identifiers, array $data): CalendarAccount
    {
        $account = CalendarAccount::updateOrCreate(
            $identifiers,
            $data
        );

        // Setup webhook for real-time sync
        $this->setupWebhook($account);

        return $account;
    }

    public function delete(CalendarAccount $account): bool
    {
        $this->deleteWebhook($account);
        return $account->delete();
    }

    public function setupWebhook(CalendarAccount $account): bool
    {
        try {
            if ($account->provider === 'google') {
                return $this->setupGoogleWebhook($account);
            } elseif ($account->provider === 'outlook') {
                return $this->setupOutlookWebhook($account);
            }
            return false;
        } catch (\Exception $e) {
            Log::error('Webhook setup failed', [
                'account_id' => $account->id,
                'error' => $e->getMessage()
            ]);
            $account->update(['webhook_registration_failed' => true]);
            return false;
        }
    }

    private function setupGoogleWebhook(CalendarAccount $account): bool
    {
        $client = new Client();
        $client->setAccessToken(decrypt($account->access_token));

        $calendar = new Calendar($client);

        $channel = new \Google\Service\Calendar\Channel();
        $channel->setId(Str::uuid()->toString());
        $channel->setType('web_hook');
        $channel->setAddress(config('app.url') . '/api/calendars/webhooks/google');
        $channel->setExpiration((now()->addDays(30)->timestamp) * 1000);

        $watchRequest = $calendar->events->watch($account->calendar_id, $channel);

        $account->update([
            'webhook_channel_id' => $channel->getId(),
            'webhook_resource_id' => $watchRequest->getResourceId(),
            'webhook_expires_at' => now()->addDays(30),
            'webhook_registered_at' => now(),
            'webhook_registration_failed' => false
        ]);

        return true;
    }

    private function setupOutlookWebhook(CalendarAccount $account): bool
    {
        $subscription = [
            'changeType' => 'created,updated,deleted',
            'notificationUrl' => route('calendars.webhook.outlook'),
            'resource' => '/me/events',
            'expirationDateTime' => now()->addDays(3)->toISOString(), // Max 3 days for Outlook
            'clientState' => hash_hmac('sha256', $account->id, config('app.key'))
        ];

        $response = $this->httpClient->post('https://graph.microsoft.com/v1.0/subscriptions', [
            'headers' => [
                'Authorization' => 'Bearer ' . decrypt($account->access_token),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'json' => $subscription
        ]);

        $responseData = json_decode($response->getBody(), true);

        $account->update([
            'webhook_subscription_id' => $responseData['id'],
            'webhook_expires_at' => now()->addDays(3),
            'webhook_registered_at' => now(),
            'webhook_registration_failed' => false
        ]);

        return true;
    }

    public function refreshWebhook(CalendarAccount $account): bool
    {
        $this->deleteWebhook($account);
        return $this->setupWebhook($account);
    }

    public function deleteWebhook(CalendarAccount $account): bool
    {
        try {
            if ($account->provider === 'google' && $account->webhook_channel_id) {
                $client = new Client();
                $client->setAccessToken(decrypt($account->access_token));
                $calendar = new Calendar($client);

                $channel = new \Google\Service\Calendar\Channel();
                $channel->setId($account->webhook_channel_id);
                $channel->setResourceId($account->webhook_resource_id);

                $calendar->channels->stop($channel);
            } elseif ($account->provider === 'outlook' && $account->webhook_subscription_id) {
                $this->httpClient->delete(
                    'https://graph.microsoft.com/v1.0/subscriptions/' . $account->webhook_subscription_id,
                    [
                        'headers' => [
                            'Authorization' => 'Bearer ' . decrypt($account->access_token),
                            'Accept' => 'application/json',
                        ]
                    ]
                );
            }

            $account->update([
                'webhook_channel_id' => null,
                'webhook_resource_id' => null,
                'webhook_subscription_id' => null,
                'webhook_expires_at' => null,
                'webhook_registered_at' => null
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Webhook deletion failed', [
                'account_id' => $account->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Make authenticated request to Microsoft Graph API
     */
    private function makeGraphRequest(string $endpoint, string $accessToken, string $method = 'GET', array $data = null): array
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

        $response = $this->httpClient->request($method, 'https://graph.microsoft.com/v1.0' . $endpoint, $options);

        return json_decode($response->getBody(), true);
    }

    /**
     * Update existing Outlook webhook subscription
     */
    public function updateOutlookWebhook(CalendarAccount $account): bool
    {
        try {
            if (!$account->webhook_subscription_id) {
                return $this->setupOutlookWebhook($account);
            }

            $updateData = [
                'expirationDateTime' => now()->addDays(3)->toISOString()
            ];

            $this->httpClient->patch(
                'https://graph.microsoft.com/v1.0/subscriptions/' . $account->webhook_subscription_id,
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . decrypt($account->access_token),
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $updateData
                ]
            );

            $account->update([
                'webhook_expires_at' => now()->addDays(3),
                'webhook_registration_failed' => false
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Outlook webhook update failed', [
                'account_id' => $account->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
