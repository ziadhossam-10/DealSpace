<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TextMessage;
use App\Services\TextMessages\TextMessageServiceInterface;
use App\Services\TextMessages\TextMessageTwilioServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TextMessageTwilioWebhookController extends Controller
{
    protected $textMessageService;
    protected $twilioService;

    public function __construct(
        TextMessageServiceInterface $textMessageService,
        TextMessageTwilioServiceInterface $twilioService
    ) {
        $this->textMessageService = $textMessageService;
        $this->twilioService = $twilioService;
    }

    /**
     * Handle incoming SMS webhook from Twilio.
     */
    public function handleIncomingSms(Request $request)
    {
        try {
            // Validate the webhook came from Twilio
            if (!$this->validateTwilioWebhook($request)) {
                Log::warning('Invalid Twilio webhook signature', [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);
                return response('Forbidden', 403);
            }

            // Process the incoming message
            $textMessage = $this->textMessageService->processIncomingMessage($request->all());

            // Optional: Trigger events or notifications here
            // event(new IncomingSmsReceived($textMessage));

            // Return TwiML response
            return response('<?xml version="1.0" encoding="UTF-8"?><Response></Response>', 200)
                ->header('Content-Type', 'text/xml');
        } catch (\Exception $e) {
            Log::error('Error processing incoming SMS', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response('Error processing message', 500);
        }
    }

    /**
     * Handle message status updates from Twilio.
     */
    public function handleStatusUpdate(Request $request)
    {
        try {
            if (!$this->validateTwilioWebhook($request)) {
                return response('Forbidden', 403);
            }

            $messageSid = $request->input('MessageSid');
            $status = $request->input('MessageStatus');

            // Find and update the message status
            $textMessage = TextMessage::where('external_label', $messageSid)->first();

            if ($textMessage) {
                // You might want to add a status field to your TextMessage model
                Log::info('Message status updated', [
                    'message_id' => $textMessage->id,
                    'sid' => $messageSid,
                    'status' => $status
                ]);
            }

            return response('OK', 200);
        } catch (\Exception $e) {
            Log::error('Error processing status update', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response('Error', 500);
        }
    }

    /**
     * Validate Twilio webhook signature.
     */
    protected function validateTwilioWebhook(Request $request): bool
    {
        $signature = $request->header('X-Twilio-Signature');
        $url = $request->url();

        if (!$signature) {
            return false;
        }

        return $this->twilioService->validateWebhook($signature, $url, $request->all());
    }
}
