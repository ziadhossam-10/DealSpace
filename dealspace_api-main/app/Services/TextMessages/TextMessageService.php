<?php

namespace App\Services\TextMessages;

use App\Models\Person;
use App\Models\TextMessage;
use App\Repositories\TextMessages\TextMessagesRepositoryInterface;
use App\Services\PhoneNumber\PhoneNumberServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class TextMessageService implements TextMessageServiceInterface
{
    protected $twilioService;
    protected $phoneNumberService;
    protected $textMessageRepository;

    public function __construct(
        TextMessageTwilioServiceInterface $twilioService,
        PhoneNumberServiceInterface $phoneNumberService,
        TextMessagesRepositoryInterface $textMessageRepository
    ) {
        $this->twilioService = $twilioService;
        $this->phoneNumberService = $phoneNumberService;
        $this->textMessageRepository = $textMessageRepository;
    }

    /**
     * Get all text messages for a person.
     */
    public function getAll(int $perPage = 15, int $page = 1, $personId = null)
    {
        return $this->textMessageRepository->getAllWithOptionalPersonFilter($perPage, $page, $personId);
    }

    /**
     * Get a text message by ID.
     */
    public function findById(int $textMessageId): TextMessage
    {
        $textMessage = $this->textMessageRepository->findById($textMessageId);

        if (!$textMessage) {
            throw new Exception('Text message not found');
        }

        return $textMessage;
    }

    /**
     * Create a new text message and optionally send it via Twilio.
     */
    public function create(array $data): TextMessage
    {
        // Use database transaction to ensure data consistency
        return DB::transaction(function () use ($data) {
            $textMessage = null;

            try {
                // If it's an outgoing message, try to send it first
                if (!isset($data['is_incoming']) || !$data['is_incoming']) {
                    // Pre-validate the message can be sent
                    $this->validateOutgoingMessage($data);

                    // Create the text message record
                    $textMessage = $this->textMessageRepository->create($data);

                    // Try to send via Twilio
                    $this->sendMessage($textMessage);

                    Log::info('Outgoing text message created and sent successfully', [
                        'message_id' => $textMessage->id
                    ]);
                } else {
                    // For incoming messages, just create the record
                    $textMessage = $this->textMessageRepository->create($data);

                    Log::info('Incoming text message created successfully', [
                        'message_id' => $textMessage->id
                    ]);
                }

                return $textMessage->load(['person', 'user']);
            } catch (Exception $e) {
                // If sending fails and we created a record, the transaction will roll back
                Log::error('Failed to create/send text message', [
                    'error' => $e->getMessage(),
                    'data' => $data
                ]);

                throw $e;
            }
        });
    }

    /**
     * Send an existing text message via Twilio.
     */
    public function sendMessage($textMessage): bool
    {
        if ($textMessage->is_incoming) {
            throw new \InvalidArgumentException('Cannot send incoming messages');
        }

        $result = $this->twilioService->sendSms(
            $textMessage->to_number,
            $textMessage->message,
            $textMessage->from_number
        );

        if ($result['success']) {
            Log::info('Text message sent successfully', [
                'message_id' => $textMessage->id,
                'twilio_sid' => $result['sid']
            ]);

            return true;
        } else {
            Log::error('Failed to send text message', [
                'message_id' => $textMessage->id,
                'error' => $result['error']
            ]);

            throw new Exception('Failed to send SMS: ' . $result['error']);
        }
    }

    /**
     * Process incoming message and find associated person.
     */
    public function processIncomingMessage(array $webhookData): TextMessage
    {
        return DB::transaction(function () use ($webhookData) {
            $fromNumber = $this->phoneNumberService->normalize($webhookData['From']);

            // Try to find person by phone number
            $person = $this->findPersonByPhoneNumber($fromNumber);

            $textMessage = $this->textMessageRepository->create([
                'person_id' => $person?->id,
                'message' => $webhookData['Body'],
                'to_number' => $webhookData['To'],
                'from_number' => $webhookData['From'], // Keep original format
                'is_incoming' => true,
                'external_label' => $webhookData['MessageSid'] ?? null,
                'user_id' => null, // Incoming messages don't have a user
            ]);

            Log::info('Incoming SMS processed', [
                'message_id' => $textMessage->id,
                'person_found' => $person ? true : false,
                'from_number' => $fromNumber
            ]);

            return $textMessage->load(['person']);
        });
    }

    /**
     * Find person by phone number using various formats.
     */
    protected function findPersonByPhoneNumber(string $phoneNumber): ?Person
    {
        $normalizedNumber = $this->phoneNumberService->normalize($phoneNumber);
        $e164Format = $this->phoneNumberService->format($phoneNumber, 'E164');
        $nationalFormat = $this->phoneNumberService->format($phoneNumber, 'NATIONAL');

        // Try to find person by various phone number fields and formats
        return Person::where(function ($query) use ($phoneNumber, $normalizedNumber, $e164Format, $nationalFormat) {
            $query->where('phone', $phoneNumber)
                ->orWhere('phone', $normalizedNumber)
                ->orWhere('phone', $e164Format)
                ->orWhere('phone', $nationalFormat)
                ->orWhere('mobile', $phoneNumber)
                ->orWhere('mobile', $normalizedNumber)
                ->orWhere('mobile', $e164Format)
                ->orWhere('mobile', $nationalFormat)
                ->orWhere('cell_phone', $phoneNumber)
                ->orWhere('cell_phone', $normalizedNumber)
                ->orWhere('cell_phone', $e164Format)
                ->orWhere('cell_phone', $nationalFormat);
        })->first();
    }

    /**
     * Resend a failed message.
     */
    public function resendMessage(int $textMessageId): bool
    {
        return DB::transaction(function () use ($textMessageId) {
            $textMessage = $this->findById($textMessageId);

            if ($textMessage->is_incoming) {
                throw new \InvalidArgumentException('Cannot resend incoming messages');
            }

            return $this->sendMessage($textMessage);
        });
    }

    /**
     * Get message delivery status from Twilio.
     */
    public function getMessageStatus(int $textMessageId): array
    {
        $textMessage = $this->findById($textMessageId);

        if (!$textMessage->external_label) {
            return ['success' => false, 'error' => 'No Twilio SID available'];
        }

        return $this->twilioService->getMessageStatus($textMessage->external_label);
    }

    /**
     * Validate outgoing message data before attempting to send.
     */
    protected function validateOutgoingMessage(array $data): void
    {
        if (empty($data['to_number'])) {
            throw new \InvalidArgumentException('Recipient phone number is required');
        }

        if (empty($data['message'])) {
            throw new \InvalidArgumentException('Message content is required');
        }

        if (empty($data['from_number'])) {
            throw new \InvalidArgumentException('Sender phone number is required');
        }
    }
}
