<?php

namespace App\Services\TextMessages;

use App\Services\TextMessages\TextMessageTwilioServiceInterface;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;

class TextMessageTwilioService implements TextMessageTwilioServiceInterface
{
    protected $client;
    protected $defaultFromNumber;
    protected $authToken;

    public function __construct()
    {
        $this->client = new Client(
            config('services.twilio.sid'),
            config('services.twilio.token')
        );
        $this->defaultFromNumber = config('services.twilio.phone_number');
        $this->authToken = config('services.twilio.token');
    }

    /**
     * Send SMS via Twilio API.
     *
     * @param string $to Recipient phone number
     * @param string $message Message content
     * @param string|null $from Sender phone number (optional)
     * @return array Contains 'success', 'sid', 'uri', 'error'
     */
    public function sendSms(string $to, string $message, string $from = null): array
    {
        try {
            $fromNumber = $from ?: $this->defaultFromNumber;

            $twilioMessage = $this->client->messages->create($to, [
                'from' => $fromNumber,
                'body' => $message
            ]);

            Log::info('SMS sent successfully via Twilio', [
                'to' => $to,
                'from' => $fromNumber,
                'sid' => $twilioMessage->sid
            ]);

            return [
                'success' => true,
                'sid' => $twilioMessage->sid,
                'uri' => $twilioMessage->uri,
                'error' => null
            ];
        } catch (\Twilio\Exceptions\TwilioException $e) {
            Log::error('Twilio SMS send failed', [
                'to' => $to,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);

            return [
                'success' => false,
                'sid' => null,
                'uri' => null,
                'error' => $e->getMessage()
            ];
        } catch (\Exception $e) {
            Log::error('Unexpected error sending SMS', [
                'to' => $to,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'sid' => null,
                'uri' => null,
                'error' => 'Unexpected error occurred'
            ];
        }
    }

    /**
     * Validate that the webhook request came from Twilio.
     *
     * @param string $signature The X-Twilio-Signature header
     * @param string $url The full URL of the webhook
     * @param array $data The POST data from the webhook
     * @return bool
     */
    public function validateWebhook(string $signature, string $url, array $data): bool
    {
        try {
            $validator = new \Twilio\Security\RequestValidator($this->authToken);
            return $validator->validate($signature, $url, $data);
        } catch (\Exception $e) {
            Log::error('Error validating Twilio webhook', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get Twilio account information.
     */
    public function getAccountInfo(): array
    {
        try {
            $account = $this->client->api->v2010->accounts(config('services.twilio.sid'))->fetch();

            return [
                'success' => true,
                'account_sid' => $account->sid,
                'friendly_name' => $account->friendlyName,
                'status' => $account->status,
                'error' => null
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get message status from Twilio.
     */
    public function getMessageStatus(string $messageSid): array
    {
        try {
            $message = $this->client->messages($messageSid)->fetch();

            return [
                'success' => true,
                'status' => $message->status,
                'error_code' => $message->errorCode,
                'error_message' => $message->errorMessage,
                'date_sent' => $message->dateSent,
                'price' => $message->price,
                'error' => null
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
