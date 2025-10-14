<?php

namespace App\Services\EmailAccounts;

use App\Models\EmailAccount;
use App\Services\EmailAccounts\GoogleOAuthService;
use App\Services\EmailAccounts\MicrosoftOAuthService;
use Google\Client;
use Google\Service\Gmail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class WebhookRegistrationService
{

    /**
     * Register webhook for an email account based on provider
     */
    public function registerWebhook(EmailAccount $account): bool
    {
        try {
            Log::info("Starting webhook registration", [
                'account_id' => $account->id,
                'provider' => $account->provider,
                'email' => $account->email
            ]);

            if ($account->provider === 'gmail') {
                return $this->registerGmailWebhook($account);
            } elseif ($account->provider === 'outlook') {
                return $this->registerOutlookWebhook($account);
            }

            Log::warning("Unknown provider for webhook registration", [
                'account_id' => $account->id,
                'provider' => $account->provider
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error("Webhook registration failed", [
                'account_id' => $account->id,
                'provider' => $account->provider,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Register Gmail push notification webhook
     */
    private function registerGmailWebhook(EmailAccount $account): bool
    {
        $accessToken = decrypt($account->access_token);
        if (!$accessToken) {
            throw new \Exception('Invalid or expired access token for Gmail account');
        }

        $pubsubTopic = config('services.google.pubsub_topic');
        if (!$pubsubTopic) {
            throw new \Exception('Gmail Pub/Sub topic not configured. Set GOOGLE_PUBSUB_TOPIC in .env');
        }

        $client = new Client();
        $client->setAccessToken($accessToken);
        $gmail = new Gmail($client);

        $watchRequest = new \Google\Service\Gmail\WatchRequest();
        $watchRequest->setTopicName($pubsubTopic);
        $watchRequest->setLabelIds(['INBOX']);

        try {
            $response = $gmail->users->watch('me', $watchRequest);

            // Store the history ID and registration info
            $account->update([
                'webhook_history_id' => $response->getHistoryId(),
                'webhook_registered_at' => now(),
                'webhook_expires_at' => now()->addDays(7), // Gmail watch expires after 7 days max
                'webhook_registration_failed' => false
            ]);

            Log::info("Gmail webhook registered successfully", [
                'account_id' => $account->id,
                'history_id' => $response->getHistoryId(),
                'expiration' => $response->getExpiration()
            ]);

            return true;
        } catch (\Google\Service\Exception $e) {
            $errorDetails = json_decode($e->getMessage(), true);
            Log::error("Gmail API error during webhook registration", [
                'account_id' => $account->id,
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
                'error_details' => $errorDetails
            ]);
            throw $e;
        }
    }

    /**
     * Register Outlook webhook subscription
     */
    private function registerOutlookWebhook(EmailAccount $account): bool
    {
        $accessToken = decrypt($account->access_token);
        if (!$accessToken) {
            throw new \Exception('Invalid or expired access token for Outlook account');
        }

        $webhookUrl = config('app.url') . '/api/webhooks/on-receive/outlook';
        $clientState = hash_hmac('sha256', $account->id, config('app.key'));
        $expirationDateTime = now()->addDays(3)->toISOString(); // Microsoft allows max 4230 minutes (~3 days)

        $subscriptionData = [
            'changeType' => 'created',
            'notificationUrl' => $webhookUrl,
            'resource' => 'me/messages',
            'expirationDateTime' => $expirationDateTime,
            'clientState' => $clientState
        ];

        Log::info("Registering Outlook webhook", [
            'account_id' => $account->id,
            'webhook_url' => $webhookUrl,
            'expiration' => $expirationDateTime
        ]);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json'
        ])->post('https://graph.microsoft.com/v1.0/subscriptions', $subscriptionData);

        if (!$response->successful()) {
            $errorBody = $response->json();
            Log::error("Outlook webhook registration failed", [
                'account_id' => $account->id,
                'status_code' => $response->status(),
                'error_body' => $errorBody,
                'subscription_data' => $subscriptionData
            ]);
            throw new \Exception('Failed to register Outlook webhook: ' . $response->body());
        }

        $subscription = $response->json();

        // Store subscription details
        $account->update([
            'webhook_subscription_id' => $subscription['id'],
            'webhook_expires_at' => Carbon::parse($subscription['expirationDateTime']),
            'webhook_registered_at' => now(),
            'webhook_registration_failed' => false
        ]);

        Log::info("Outlook webhook registered successfully", [
            'account_id' => $account->id,
            'subscription_id' => $subscription['id'],
            'expires_at' => $subscription['expirationDateTime']
        ]);

        return true;
    }

    /**
     * Renew Outlook webhook subscription (call this before expiration)
     */
    public function renewOutlookWebhook(EmailAccount $account): bool
    {
        if ($account->provider !== 'outlook') {
            Log::warning("Attempted to renew non-Outlook webhook", ['account_id' => $account->id]);
            return false;
        }

        if (!$account->webhook_subscription_id) {
            Log::warning("No subscription ID found for renewal", ['account_id' => $account->id]);

            // Try to register a new webhook instead
            return $this->registerWebhook($account);
        }

        $accessToken = decrypt($account->access_token);
        if (!$accessToken) {
            Log::error("Invalid access token for webhook renewal", ['account_id' => $account->id]);
            return false;
        }

        $newExpirationDateTime = now()->addDays(3)->toISOString();

        Log::info("Renewing Outlook webhook", [
            'account_id' => $account->id,
            'subscription_id' => $account->webhook_subscription_id,
            'new_expiration' => $newExpirationDateTime
        ]);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json'
        ])->patch("https://graph.microsoft.com/v1.0/subscriptions/{$account->webhook_subscription_id}", [
            'expirationDateTime' => $newExpirationDateTime
        ]);

        if (!$response->successful()) {
            Log::error("Outlook webhook renewal failed", [
                'account_id' => $account->id,
                'subscription_id' => $account->webhook_subscription_id,
                'status_code' => $response->status(),
                'error_body' => $response->json()
            ]);

            // If renewal fails, try to create a new subscription
            Log::info("Attempting to register new webhook after renewal failure", [
                'account_id' => $account->id
            ]);

            // Clear old subscription data
            $account->update([
                'webhook_subscription_id' => null,
                'webhook_expires_at' => null
            ]);

            return $this->registerWebhook($account);
        }

        // Update expiration time
        $account->update([
            'webhook_expires_at' => Carbon::parse($newExpirationDateTime)
        ]);

        Log::info("Outlook webhook renewed successfully", [
            'account_id' => $account->id,
            'new_expires_at' => $newExpirationDateTime
        ]);

        return true;
    }

    /**
     * Unregister webhook for account
     */
    public function unregisterWebhook(EmailAccount $account): bool
    {
        try {
            if ($account->provider === 'outlook' && $account->webhook_subscription_id) {
                return $this->unregisterOutlookWebhook($account);
            }

            // Gmail webhooks expire automatically, no need to unregister
            $account->update([
                'webhook_registered_at' => null,
                'webhook_expires_at' => null,
                'webhook_history_id' => null
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Webhook unregistration failed", [
                'account_id' => $account->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    private function unregisterOutlookWebhook(EmailAccount $account): bool
    {
        $accessToken = decrypt($account->access_token);
        if (!$accessToken) {
            return false;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken
        ])->delete("https://graph.microsoft.com/v1.0/subscriptions/{$account->webhook_subscription_id}");

        if ($response->successful()) {
            $account->update([
                'webhook_subscription_id' => null,
                'webhook_expires_at' => null,
                'webhook_registered_at' => null
            ]);

            Log::info("Outlook webhook unregistered successfully", [
                'account_id' => $account->id
            ]);
        }

        return $response->successful();
    }
}
