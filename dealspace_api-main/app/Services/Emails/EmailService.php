<?php

namespace App\Services\Emails;

use App\Models\Email;
use App\Models\EmailAccount;
use App\Models\Person;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Repositories\Emails\EmailsRepositoryInterface;
use App\Services\EmailAccounts\EmailAccountServiceInterface;
use App\Services\EmailAccounts\GoogleOAuthService;
use App\Services\EmailAccounts\MicrosoftOAuthService;
use App\Services\People\EmailServiceInterface as PersonEmailService;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Http;
use Google\Service\Gmail;
use Google\Service\Gmail\Message;
use Google\Client;
use Illuminate\Support\Facades\Log;

class EmailService implements EmailServiceInterface
{
    protected $emailRepository;
    private $googleService;
    private $microsoftService;
    private $personEmailService;
    private $emailAccountService;

    public function __construct(
        EmailsRepositoryInterface $emailRepository,
        GoogleOAuthService $googleService,
        MicrosoftOAuthService $microsoftService,
        PersonEmailService $personEmailService,
        EmailAccountServiceInterface $emailAccountService
    ) {
        $this->emailRepository = $emailRepository;
        $this->googleService = $googleService;
        $this->microsoftService = $microsoftService;
        $this->personEmailService = $personEmailService;
        $this->emailAccountService = $emailAccountService;
    }

    public function getAll(int $personId, int $perPage = 15, int $page = 1)
    {
        return $this->emailRepository->getAll($personId, $perPage, $page);
    }

    public function findById(int $id): Email
    {
        $email = $this->emailRepository->findById($id);

        if (!$email) {
            throw new ModelNotFoundException('Email not found');
        }

        return $email;
    }

    public function create(array $data): Email
    {
        return $this->emailRepository->create($data);
    }

    /**
     * Send an email using the specified email account and create a record of it.
     * Enhanced to support campaign tracking and webhooks.
     */
    public function sendEmail(array $emailData, int $user_id): Email
    {
        $account = $this->emailAccountService->findById((int) $emailData['account_id']);

        if (!$account->is_active)
            throw new \Exception('Email account is not active');

        // Create email record first
        $email = $this->create([
            'person_id' => $emailData['person_id'],
            'email_account_id' => $account->id,
            'campaign_id' => $emailData['campaign_id'] ?? null, // New field for campaign association
            'subject' => $emailData['subject'],
            'body' => $emailData['body'],
            'body_html' => $emailData['body_html'] ?? $emailData['body'],
            'to_email' => $emailData['to_email'],
            'from_email' => $account->email,
            'is_incoming' => false,
            'status' => 'pending',
            'user_id' => $user_id,
            'sent_at' => now(),
        ]);

        try {
            if ($account->provider === 'gmail') {
                $messageId = $this->sendGmailEmail($account, $emailData);
            } elseif ($account->provider === 'outlook') {
                $messageId = $this->sendOutlookEmail($account, $emailData);
            } else {
                throw new \Exception('Unsupported email provider');
            }

            // Update email record with success
            $email->update([
                'message_id' => $messageId,
                'status' => 'sent'
            ]);

            // Update campaign recipient if this is a campaign email
            if (isset($emailData['campaign_recipient_id'])) {
                CampaignRecipient::where('id', $emailData['campaign_recipient_id'])
                    ->update([
                        'status' => 'sent',
                        'sent_at' => now()
                    ]);
            }
        } catch (\Exception $e) {
            // Update email record with failure
            $email->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);

            // Update campaign recipient if this is a campaign email
            if (isset($emailData['campaign_recipient_id'])) {
                CampaignRecipient::where('id', $emailData['campaign_recipient_id'])
                    ->update([
                        'status' => 'failed',
                        'failure_reason' => $e->getMessage()
                    ]);
            }

            throw $e;
        }

        return $email;
    }

    /**
     * Send an email using Gmail API with enhanced tracking.
     */
    private function sendGmailEmail(EmailAccount $account, array $emailData): string
    {
        $accessToken = $this->googleService->getValidToken($account);
        if (!$accessToken) {
            throw new \Exception('Invalid access token');
        }

        $client = new Client();
        $client->setAccessToken($accessToken);
        $gmail = new Gmail($client);

        // Create email message with enhanced headers for tracking
        $message = $this->createGmailMessage($emailData, $account);
        $sentMessage = $gmail->users_messages->send('me', $message);

        return $sentMessage->getId();
    }

    /**
     * Send an email using Microsoft Graph API with enhanced tracking.
     */
    private function sendOutlookEmail(EmailAccount $account, array $emailData): string
    {
        $accessToken = $this->microsoftService->getValidToken($account);
        if (!$accessToken) {
            throw new \Exception('Invalid access token');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json'
        ])->post('https://graph.microsoft.com/v1.0/me/sendMail', [
            'message' => $this->createOutlookMessage($emailData, $account)
        ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to send email via Outlook: ' . $response->body());
        }

        return 'outlook_' . uniqid() . '_' . time();
    }

    /**
     * Create a Gmail message object with enhanced tracking headers.
     */
    private function createGmailMessage(array $emailData, EmailAccount $account): Message
    {
        $rawMessage = "To: {$emailData['to_email']}\r\n";
        $rawMessage .= "From: {$account->email}\r\n";
        $rawMessage .= "Subject: {$emailData['subject']}\r\n";

        // Add custom headers for tracking
        if (isset($emailData['campaign_id'])) {
            $rawMessage .= "X-Campaign-ID: {$emailData['campaign_id']}\r\n";
        }
        if (isset($emailData['campaign_recipient_id'])) {
            $rawMessage .= "X-Campaign-Recipient-ID: {$emailData['campaign_recipient_id']}\r\n";
        }

        $rawMessage .= "Content-Type: text/html; charset=utf-8\r\n\r\n";
        $rawMessage .= $emailData['body_html'] ?? $emailData['body'];

        $message = new Message();
        $message->setRaw(base64url_encode($rawMessage));

        return $message;
    }

    /**
     * Create an Outlook message array with enhanced tracking.
     */
    private function createOutlookMessage(array $emailData, EmailAccount $account): array
    {
        $message = [
            'subject' => $emailData['subject'],
            'body' => [
                'contentType' => 'HTML',
                'content' => $emailData['body_html'] ?? $emailData['body']
            ],
            'toRecipients' => [
                [
                    'emailAddress' => [
                        'address' => $emailData['to_email']
                    ]
                ]
            ]
        ];

        // Add custom properties for tracking
        if (isset($emailData['campaign_id']) || isset($emailData['campaign_recipient_id'])) {
            $message['singleValueExtendedProperties'] = [];

            if (isset($emailData['campaign_id'])) {
                $message['singleValueExtendedProperties'][] = [
                    'id' => 'String {66f5a359-4659-4830-9070-00047ec6ac6e} Name X-Campaign-ID',
                    'value' => (string) $emailData['campaign_id']
                ];
            }

            if (isset($emailData['campaign_recipient_id'])) {
                $message['singleValueExtendedProperties'][] = [
                    'id' => 'String {66f5a359-4659-4830-9070-00047ec6ac6e} Name X-Campaign-Recipient-ID',
                    'value' => (string) $emailData['campaign_recipient_id']
                ];
            }
        }

        return $message;
    }

    /**
     * Enhanced email fetching with campaign tracking support.
     */
    public function fetchNewEmails(EmailAccount $account): array
    {
        try {
            if ($account->provider === 'gmail') {
                return $this->fetchGmailEmails($account);
            } elseif ($account->provider === 'outlook') {
                return $this->fetchOutlookEmails($account);
            }

            return [];
        } catch (\Exception $e) {
            Log::error('Failed to fetch emails', [
                'account_id' => $account->id,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    private function fetchGmailEmails(EmailAccount $account): array
    {
        $accessToken = $this->googleService->getValidToken($account);
        if (!$accessToken) {
            return [];
        }

        $client = new Client();
        $client->setAccessToken($accessToken);
        $gmail = new Gmail($client);

        // Get messages from last 24 hours
        $query = 'newer_than:1d';
        $messages = $gmail->users_messages->listUsersMessages('me', ['q' => $query]);

        $emails = [];
        if (!$messages->getMessages()) {
            return $emails;
        }

        foreach ($messages->getMessages() as $message) {
            try {
                $messageDetails = $gmail->users_messages->get('me', $message->getId());

                // Check if we already have this email
                $existingEmail = Email::where('message_id', $message->getId())
                    ->where('email_account_id', $account->id)
                    ->first();

                if (!$existingEmail) {
                    $parsedEmail = $this->parseGmailMessage($messageDetails, $account);
                    if ($parsedEmail) {
                        // Check if this is a response to a campaign email
                        $this->checkCampaignResponse($parsedEmail, $messageDetails);
                        $emails[] = $parsedEmail;
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Failed to process Gmail message', [
                    'message_id' => $message->getId(),
                    'error' => $e->getMessage()
                ]);
                continue;
            }
        }

        return $emails;
    }

    private function fetchOutlookEmails(EmailAccount $account): array
    {
        $accessToken = $this->microsoftService->getValidToken($account);
        if (!$accessToken) {
            return [];
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken
        ])->get('https://graph.microsoft.com/v1.0/me/messages', [
            '$filter' => "receivedDateTime ge " . now()->subDay()->toISOString(),
            '$top' => 50,
            '$expand' => 'singleValueExtendedProperties'
        ]);

        if (!$response->successful()) {
            return [];
        }

        $emails = [];
        foreach ($response->json()['value'] as $message) {
            try {
                // Check if we already have this email
                $existingEmail = Email::where('message_id', $message['id'])
                    ->where('email_account_id', $account->id)
                    ->first();

                if (!$existingEmail) {
                    $parsedEmail = $this->parseOutlookMessage($message, $account);
                    if ($parsedEmail) {
                        // Check if this is a response to a campaign email
                        $this->checkCampaignResponse($parsedEmail, $message);
                        $emails[] = $parsedEmail;
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Failed to process Outlook message', [
                    'message_id' => $message['id'],
                    'error' => $e->getMessage()
                ]);
                continue;
            }
        }

        return $emails;
    }

    /**
     * Check if an incoming email is a response to a campaign email.
     */
    private function checkCampaignResponse(array $emailData, $messageDetails): void
    {
        try {
            // For Gmail, check headers
            if (isset($messageDetails)) {
                $headers = [];
                if (method_exists($messageDetails, 'getPayload')) {
                    foreach ($messageDetails->getPayload()->getHeaders() as $header) {
                        $headers[$header->getName()] = $header->getValue();
                    }
                } else {
                    // Outlook message
                    if (isset($messageDetails['internetMessageHeaders'])) {
                        foreach ($messageDetails['internetMessageHeaders'] as $header) {
                            $headers[$header['name']] = $header['value'];
                        }
                    }
                }

                // Look for campaign tracking in References or In-Reply-To headers
                $references = $headers['References'] ?? $headers['In-Reply-To'] ?? '';

                // Also check subject for campaign patterns
                $subject = $headers['Subject'] ?? $emailData['subject'] ?? '';

                // Try to find the original campaign email this is responding to
                $originalEmail = Email::where('to_email', $emailData['from_email'])
                    ->where('from_email', $emailData['to_email'])
                    ->whereNotNull('campaign_id')
                    ->where('created_at', '>=', now()->subDays(30)) // Look back 30 days
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($originalEmail) {
                    // Update the email data to include campaign association
                    $emailData['campaign_id'] = $originalEmail->campaign_id;
                    $emailData['parent_email_id'] = $originalEmail->id;
                    $emailData['is_campaign_response'] = true;

                    // Update campaign recipient status to indicate response
                    CampaignRecipient::where('campaign_id', $originalEmail->campaign_id)
                        ->where('email', $emailData['from_email'])
                        ->update(['status' => 'replied']);
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to check campaign response', [
                'email_from' => $emailData['from_email'] ?? null,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function parseGmailMessage($message, $account): array | bool
    {
        $headers = [];
        foreach ($message->getPayload()->getHeaders() as $header) {
            $headers[$header->getName()] = $header->getValue();
        }

        // Try to find existing person by email
        $fromHeader = $headers['From'] ?? '';
        preg_match('/<(.+?)>/', $fromHeader, $matches);
        $fromEmail = $matches[1] ?? $fromHeader;
        $person = $this->personEmailService->findByEmailAddress($fromEmail);

        if (!$person) {
            return false;
        }

        // Extract dates
        $receivedAt = $this->parseEmailDate($headers['Received'] ?? $headers['Date'] ?? null);
        $createdAt = $this->parseEmailDate($headers['Date'] ?? null);

        return [
            'person_id' => $person->person_id,
            'email_account_id' => $account->id,
            'message_id' => $message->getId(),
            'from_email' => $fromEmail,
            'to_email' => $headers['To'] ?? $account->email,
            'subject' => $headers['Subject'] ?? '',
            'body' => $this->extractGmailBody($message->getPayload(), 'text/plain'),
            'body_html' => $this->extractGmailBody($message->getPayload(), 'text/html'),
            'headers' => $headers,
            'is_incoming' => true,
            'received_at' => $receivedAt ?: now(),
            'created_at' => $createdAt ?: now(),
            'status' => 'delivered',
            'user_id' => $account->user_id ?? null,
            'tenant_id' => $account->tenant_id,
        ];
    }

    private function parseOutlookMessage($message, $account): array | bool
    {
        $fromEmail = $message['from']['emailAddress']['address'] ?? '';
        $person = $this->personEmailService->findByEmailAddress($fromEmail);

        if (!$person) {
            return false;
        }

        $receivedAt = $this->parseOutlookDate($message['receivedDateTime'] ?? null);
        $createdAt = $this->parseOutlookDate($message['createdDateTime'] ?? $message['sentDateTime'] ?? null);

        return [
            'person_id' => $person->id,
            'email_account_id' => $account->id,
            'message_id' => $message['id'],
            'from_email' => $fromEmail,
            'to_email' => $message['toRecipients'][0]['emailAddress']['address'] ?? $account->email,
            'subject' => $message['subject'] ?? '',
            'body' => strip_tags($message['body']['content'] ?? ''),
            'body_html' => $message['body']['content'] ?? '',
            'headers' => $message['internetMessageHeaders'] ?? [],
            'is_incoming' => true,
            'received_at' => $receivedAt ?: now(),
            'created_at' => $createdAt ?: now(),
            'status' => 'delivered',
            'user_id' => $account->user_id ?? null,
        ];
    }

    private function parseEmailDate($dateHeader): ?Carbon
    {
        if (!$dateHeader) {
            return null;
        }

        try {
            if (str_contains($dateHeader, ';')) {
                $parts = explode(';', $dateHeader);
                $dateString = trim(end($parts));
            } else {
                $dateString = trim($dateHeader);
            }

            return Carbon::parse($dateString);
        } catch (Exception $e) {
            Log::warning('Failed to parse email date: ' . $dateHeader, ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function parseOutlookDate($dateString): ?Carbon
    {
        if (!$dateString) {
            return null;
        }

        try {
            return Carbon::parse($dateString);
        } catch (Exception $e) {
            Log::warning('Failed to parse Outlook date: ' . $dateString, ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function extractGmailBody($payload, $mimeType)
    {
        if ($payload->getMimeType() === $mimeType) {
            return base64url_decode($payload->getBody()->getData());
        }

        foreach ($payload->getParts() as $part) {
            if ($part->getMimeType() === $mimeType) {
                return base64url_decode($part->getBody()->getData());
            }
        }

        return '';
    }

    /**
     * Webhook handler for email delivery status updates
     * This can be called by email service providers to update delivery status
     */
    public function handleDeliveryWebhook(array $webhookData): void
    {
        try {
            $messageId = $webhookData['message_id'] ?? null;
            $event = $webhookData['event'] ?? null; // delivered, bounced, opened, clicked

            if (!$messageId || !$event) {
                return;
            }

            $email = Email::where('message_id', $messageId)->first();
            if (!$email) {
                return;
            }

            // Update email status
            switch ($event) {
                case 'delivered':
                    $email->update(['status' => 'delivered']);

                    // Update campaign recipient if associated
                    if ($email->campaign_id) {
                        CampaignRecipient::where('campaign_id', $email->campaign_id)
                            ->where('email', $email->to_email)
                            ->update([
                                'status' => 'delivered',
                                'delivered_at' => now()
                            ]);
                    }
                    break;

                case 'bounced':
                    $email->update(['status' => 'bounced']);

                    if ($email->campaign_id) {
                        CampaignRecipient::where('campaign_id', $email->campaign_id)
                            ->where('email', $email->to_email)
                            ->update(['status' => 'bounced']);

                        // Update campaign bounce count
                        Campaign::where('id', $email->campaign_id)->increment('emails_bounced');
                    }
                    break;
            }

            Log::info('Processed delivery webhook', [
                'message_id' => $messageId,
                'event' => $event,
                'email_id' => $email->id
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to process delivery webhook', [
                'webhook_data' => $webhookData,
                'error' => $e->getMessage()
            ]);
        }
    }
}
