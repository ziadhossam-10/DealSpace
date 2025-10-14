<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmailAccount;
use App\Services\Emails\EmailServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmailsWebhookController extends Controller
{
    private $emailService;

    public function __construct(EmailServiceInterface $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Handle Gmail webhook notifications from Cloud Pub/Sub
     */
    public function handleGmailWebhook(Request $request)
    {
        try {
            // Gmail sends data as base64 encoded JSON in Pub/Sub message
            $pubsubMessage = $request->json()->all();


            if (!isset($pubsubMessage['message']['data'])) {
                Log::warning('Gmail webhook received without message data');
                return response('OK', 200);
            }

            // Decode the base64 data
            $decodedData = base64_decode($pubsubMessage['message']['data']);
            $data = json_decode($decodedData, true);

            if (!$data || !isset($data['emailAddress'])) {
                Log::warning('Gmail webhook: Invalid data format', ['data' => $decodedData]);
                return response('OK', 200);
            }

            $emailAddress = $data['emailAddress'];
            $historyId = $data['historyId'] ?? null;

            // Find the email account
            $account = EmailAccount::where('email', $emailAddress)
                ->where('provider', 'gmail')
                ->where('is_active', true)
                ->first();

            if (!$account) {
                Log::info('Gmail webhook: Account not found', ['email' => $emailAddress]);
                return response('OK', 200);
            }

            // Only process if this is a newer history ID
            if ($historyId && $account->webhook_history_id && $historyId <= $account->webhook_history_id) {
                Log::info('Gmail webhook: History ID already processed', [
                    'account_id' => $account->id,
                    'current_history_id' => $account->webhook_history_id,
                    'received_history_id' => $historyId
                ]);
                return response('OK', 200);
            }

            // Fetch and store new emails
            $newEmails = $this->emailService->fetchNewEmails($account);

            foreach ($newEmails as $emailData) {
                $email = $this->emailService->create($emailData);
                $this->logIncomingEmail($email);
            }

            // Update the history ID
            if ($historyId) {
                $account->update(['webhook_history_id' => $historyId]);
            }

            Log::info('Gmail webhook processed successfully', [
                'account_id' => $account->id,
                'new_emails_count' => count($newEmails),
                'history_id' => $historyId
            ]);

            return response('OK', 200);
        } catch (\Exception $e) {
            Log::error('Gmail webhook error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return response('Error', 500);
        }
    }

    /**
     * Handle Outlook webhook notifications
     */
    public function handleOutlookWebhook(Request $request)
    {
        try {
            // Verify webhook signature
            if (!$this->verifyOutlookWebhook($request)) {
                Log::warning('Outlook webhook: Verification failed');
                return response('Unauthorized', 401);
            }

            $data = $request->json()->all();

            // Handle validation request from Microsoft
            if (isset($data['validationToken'])) {
                return response($data['validationToken'], 200)
                    ->header('Content-Type', 'text/plain');
            }

            if (!isset($data['value']) || !is_array($data['value'])) {
                Log::warning('Outlook webhook: Invalid data format');
                return response('OK', 200);
            }

            foreach ($data['value'] as $notification) {
                $this->processOutlookNotification($notification);
            }

            return response('OK', 200);
        } catch (\Exception $e) {
            Log::error('Outlook webhook error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return response('Error', 500);
        }
    }

    /**
     * Process individual Outlook notification
     */
    private function processOutlookNotification(array $notification): void
    {
        $resource = $notification['resource'] ?? '';
        $clientState = $notification['clientState'] ?? '';

        // Extract email address from resource
        $emailAddress = $this->extractEmailFromResource($resource);

        if (!$emailAddress) {
            Log::warning('Outlook webhook: Could not extract email from resource', ['resource' => $resource]);
            return;
        }

        // Find the email account
        $account = EmailAccount::where('email', $emailAddress)
            ->where('provider', 'outlook')
            ->where('is_active', true)
            ->first();

        if (!$account) {
            Log::info('Outlook webhook: Account not found', ['email' => $emailAddress]);
            return;
        }

        // Verify client state matches what we sent during registration
        $expectedClientState = hash_hmac('sha256', $account->id, config('app.key'));
        if ($clientState !== $expectedClientState) {
            Log::warning('Outlook webhook: Client state mismatch', [
                'account_id' => $account->id,
                'expected' => $expectedClientState,
                'received' => $clientState
            ]);
            return;
        }

        // Fetch and store new emails
        $newEmails = $this->emailService->fetchNewEmails($account);

        foreach ($newEmails as $emailData) {
            $email = $this->emailService->create($emailData);
            $this->logIncomingEmail($email);
        }

        Log::info('Outlook webhook processed successfully', [
            'account_id' => $account->id,
            'new_emails_count' => count($newEmails),
            'resource' => $resource
        ]);
    }

    /**
     * Verify Outlook webhook signature
     */
    private function verifyOutlookWebhook(Request $request): bool
    {
        $signature = $request->header('X-MS-Signature');

        if (!$signature) {
            return true; // Microsoft doesn't always send signatures for validation requests
        }

        $webhookSecret = config('services.microsoft.webhook_secret');

        if (!$webhookSecret) {
            Log::warning('Outlook webhook secret not configured');
            return true; // Allow if not configured, but log warning
        }

        $expectedSignature = base64_encode(hash_hmac('sha256', $request->getContent(), $webhookSecret, true));

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Extract email address from Outlook resource URL
     */
    private function extractEmailFromResource(string $resource): ?string
    {
        // Resource format: /v1.0/Users('user@domain.com')/Messages('messageId')
        // or: /v1.0/Users/user@domain.com/Messages/messageId

        if (preg_match('/\/Users\([\'"]([^\'")]+)[\'"]\)/', $resource, $matches)) {
            return $matches[1];
        }

        if (preg_match('/\/Users\/([^\/]+)\//', $resource, $matches)) {
            return urldecode($matches[1]);
        }

        return null;
    }

    /**
     * Log incoming email details
     */
    private function logIncomingEmail($email): void
    {
        Log::info('New email received via webhook', [
            'email_id' => $email->id,
            'person_id' => $email->person_id,
            'account_id' => $email->email_account_id,
            'from' => $email->from_email,
            'subject' => $email->subject,
            'received_at' => $email->received_at
        ]);
    }
}
