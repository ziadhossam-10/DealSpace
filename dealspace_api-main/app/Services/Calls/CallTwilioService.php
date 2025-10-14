<?php

namespace App\Services\Calls;

use App\Events\CallEndedEvent;
use App\Models\Call;
use App\Models\User;
use App\Repositories\Calls\CallsRepositoryInterface;
use App\Repositories\Users\UsersRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;
use Twilio\TwiML\VoiceResponse;
use Twilio\Jwt\AccessToken;
use Twilio\Jwt\Grants\VoiceGrant;

class CallTwilioService implements CallTwilioServiceInterface
{
    protected $client;
    protected $accountSid;
    protected $authToken;
    protected $twilioNumber;
    protected $apiKey;
    protected $apiSecret;
    protected $appSid;
    protected $callsRepository;
    protected $usersRepository;

    public function __construct(
        CallsRepositoryInterface $callsRepository,
        UsersRepositoryInterface $usersRepository
    ) {
        $this->accountSid = config('services.twilio.account_sid');
        $this->authToken = config('services.twilio.auth_token');
        $this->twilioNumber = config('services.twilio.phone_number');
        $this->apiKey = config('services.twilio.api_key');
        $this->apiSecret = config('services.twilio.api_secret');
        $this->appSid = config('services.twilio.app_sid');
        $this->client = new Client($this->accountSid, $this->authToken);
        $this->callsRepository = $callsRepository;
        $this->usersRepository = $usersRepository;
    }

    /**
     * Initiate an outbound call
     *
     * @param array $data Contains:
     * - 'agent_id' (int) The ID of the agent making the call
     * - 'to_number' (string) The phone number to call
     * - 'person_id' (int|null) Optional person ID associated with the call
     * @return array
     * @throws ModelNotFoundException
     * @throws \Exception
     */
    public function initiateCall(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $agent = $this->usersRepository->findById($data['agent_id']);
            if (!$agent) {
                throw new ModelNotFoundException('Agent not found');
            }

            // Create call record using repository
            $call = $this->callsRepository->create([
                'person_id' => $data['person_id'] ?? null,
                'phone' => $data['to_number'],
                'is_incoming' => false,
                'to_number' => $data['to_number'],
                'from_number' => $this->twilioNumber,
                'user_id' => $data['agent_id'],
            ]);

            try {
                // Create the call via Twilio
                $twilioCall = $this->client->calls->create(
                    $data['to_number'], // To
                    $this->twilioNumber, // From
                    [
                        'url' => route('twilio.outbound.twiml', ['callId' => $call->id]),
                        'statusCallback' => route('twilio.status.callback'),
                        'statusCallbackEvent' => ['initiated', 'ringing', 'answered', 'completed'],
                        'statusCallbackMethod' => 'POST',
                        'record' => true,
                        'recordingStatusCallback' => route('twilio.recording.callback'),
                        'recordingStatusCallbackMethod' => 'POST'
                    ]
                );

                // Update call with Twilio SID
                $this->callsRepository->update($call, ['twilio_call_sid' => $twilioCall->sid]);

                return [
                    'success' => true,
                    'call_id' => $call->id,
                    'twilio_sid' => $twilioCall->sid,
                    'agent_phone' => $agent->phone ?? null
                ];
            } catch (\Exception $e) {
                // Clean up the call record if Twilio call fails
                $this->callsRepository->delete($call);
                throw new \Exception('Twilio call initiation failed: ' . $e->getMessage());
            }
        });
    }

    /**
     * Generate access token for Twilio Client
     *
     * @param int $agentId
     * @return array
     * @throws ModelNotFoundException
     */
    public function generateAccessToken(int $agentId): array
    {
        $agent = $this->usersRepository->findById($agentId);
        if (!$agent) {
            throw new ModelNotFoundException('Agent not found');
        }

        // Create access token
        $token = new AccessToken($this->accountSid, $this->apiKey, $this->apiSecret, 3600, 'agent_' . $agent->id);

        // Create Voice grant
        $voiceGrant = new VoiceGrant();
        $voiceGrant->setOutgoingApplicationSid($this->appSid);
        $voiceGrant->setIncomingAllow(true);

        $token->addGrant($voiceGrant);

        return [
            'token' => $token->toJWT(),
            'identity' => 'agent_' . $agent->id
        ];
    }

    /**
     * Handle outbound calls from Voice SDK
     * This is called when device.connect() is used with TwiML Application
     *
     * @param array $data Contains parameters from the Voice SDK call
     * @return VoiceResponse
     */
    public function handleOutboundCall(array $data): VoiceResponse
    {
        $response = new VoiceResponse();

        // Get the To parameter from Voice SDK
        $toNumber = $data['To'] ?? null;
        $agentId = $data['AgentId'] ?? null;
        $personId = $data['PersonId'] ?? null;

        if (!$toNumber) {
            $response->say('Invalid phone number provided.');
            $response->hangup();
            return $response;
        }

        // Create call record
        $call = $this->callsRepository->create([
            'person_id' => $personId ?: null,
            'phone' => $toNumber,
            'is_incoming' => false,
            'to_number' => $toNumber,
            'from_number' => $this->twilioNumber,
            'user_id' => $agentId ?: null,
        ]);

        // Dial the customer
        $dial = $response->dial($toNumber, [
            'callerId' => $this->twilioNumber,
            'timeout' => 30,
            'record' => 'record-from-answer',
            'recordingStatusCallback' => route('twilio.recording.callback'),
            // 'action' => route('twilio.dial.status', ['callId' => $call->id])
        ]);

        return $response;
    }

    /**
     * Generate TwiML for outbound calls
     * Updated to handle Voice SDK calls properly
     *
     * @param int $callId
     * @return VoiceResponse
     * @throws ModelNotFoundException
     */
    public function generateOutboundTwiML(int $callId): VoiceResponse
    {
        $call = $this->callsRepository->findById($callId);
        if (!$call) {
            throw new ModelNotFoundException('Call not found');
        }

        $response = new VoiceResponse();

        // For outbound calls from Voice SDK, we need to dial the customer's number
        $dial = $response->dial($call->phone, [
            'callerId' => $this->twilioNumber,
            'timeout' => 30,
            'record' => 'record-from-answer',
            'recordingStatusCallback' => route('twilio.recording.callback'),
            // 'action' => route('twilio.dial.status', ['callId' => $callId])
        ]);

        return $response;
    }

    /**
     * Handle incoming calls
     *
     * @param array $data Contains:
     * - 'from' (string) The phone number calling
     * - 'to' (string) The phone number being called
     * @return VoiceResponse
     */
    public function handleIncomingCall(array $data): VoiceResponse
    {
        // Create call record
        $call = $this->callsRepository->create([
            'phone' => $data['from'],
            'is_incoming' => true,
            'to_number' => $data['to'],
            'from_number' => $data['from'],
        ]);

        $response = new VoiceResponse();

        // Play hold music or greeting
        $response->say('Please hold while we connect you to an agent.');
        $response->play('http://com.twilio.music.classical.s3.amazonaws.com/BusyStrings.wav', ['loop' => 5]);

        // Queue the call for available agents
        $response->enqueue('support_queue', [
            'action' => route('twilio.queue.callback', ['callId' => $call->id]),
            'waitUrl' => route('twilio.wait.music')
        ]);

        return $response;
    }

    /**
     * Distribute call to available agents
     *
     * @param int $callId
     * @return VoiceResponse
     * @throws ModelNotFoundException
     */
    public function distributeToAgents(int $callId): VoiceResponse
    {
        $call = $this->callsRepository->findById($callId);
        if (!$call) {
            throw new ModelNotFoundException('Call not found');
        }

        $availableAgents = User::where('is_available', true)->get();

        $response = new VoiceResponse();

        if ($availableAgents->count() > 0) {
            $dial = $response->dial('', [
                'timeout' => 30,
                'action' => route('twilio.agent.response', ['callId' => $callId])
            ]);

            foreach ($availableAgents as $agent) {
                if ($agent->phone) {
                    $dial->number($agent->phone, [
                        'url' => route('twilio.agent.screen', ['agentId' => $agent->id, 'callId' => $callId])
                    ]);
                }
                // Also ring browser clients
                $dial->client('agent_' . $agent->id);
            }
        } else {
            $response->say('All agents are currently busy. Please leave a message after the beep.');
            $response->record([
                'action' => route('twilio.voicemail', ['callId' => $callId]),
                'maxLength' => 120,
                'finishOnKey' => '#'
            ]);
        }

        return $response;
    }

    /**
     * Agent call screening
     *
     * @param int $agentId
     * @param int $callId
     * @return VoiceResponse
     * @throws ModelNotFoundException
     */
    public function agentScreening(int $agentId, int $callId): VoiceResponse
    {
        $call = $this->callsRepository->findById($callId);
        if (!$call) {
            throw new ModelNotFoundException('Call not found');
        }

        $agent = $this->usersRepository->findById($agentId);
        if (!$agent) {
            throw new ModelNotFoundException('Agent not found');
        }

        $response = new VoiceResponse();

        $gather = $response->gather([
            'numDigits' => 1,
            'action' => route('twilio.agent.accept', ['agentId' => $agentId, 'callId' => $callId])
        ]);

        $gather->say("Hello {$agent->name}, you have an incoming call from {$call->from_number}. Press 1 to accept or 2 to decline.");

        return $response;
    }

    /**
     * Handle agent accepting or declining a call
     *
     * @param array $data Contains:
     * - 'digits' (string) The digit pressed by the agent
     * - 'agent_id' (int) The ID of the agent
     * - 'call_id' (int) The ID of the call
     * @return VoiceResponse
     * @throws ModelNotFoundException
     */
    public function handleAgentResponse(array $data): VoiceResponse
    {
        $call = $this->callsRepository->findById($data['call_id']);
        if (!$call) {
            throw new ModelNotFoundException('Call not found');
        }

        $response = new VoiceResponse();

        if ($data['digits'] == '1') {
            // Agent accepted
            $this->callsRepository->update($call, ['user_id' => $data['agent_id']]);
            $response->say('Call connected.');
            $response->dial($call->from_number);
        } else {
            // Agent declined
            $response->say('Call declined.');
            $response->hangup();
        }

        return $response;
    }

    /**
     * Update call status from Twilio callback
     *
     * @param array $data Contains:
     * - 'call_sid' (string) Twilio call SID
     * - 'call_status' (string) The call status
     * - 'duration' (int) Call duration in seconds
     * @return void
     */
    public function updateCallStatus(array $data): void
    {
        $call = Call::where('twilio_call_sid', $data['call_sid'])->first();

        if ($call) {
            $this->callsRepository->update($call, [
                'status' => $data['call_status'],
                'duration' => $data['duration'] ?? 0
            ]);

            // If call ended, trigger call logging UI
            if (in_array($data['call_status'], ['completed', 'busy', 'no-answer', 'failed', 'canceled'])) {
                broadcast(new CallEndedEvent($call));
            }
        }
    }

    /**
     * Update call recording information
     *
     * @param array $data Contains:
     * - 'call_sid' (string) Twilio call SID
     * - 'recording_url' (string) URL to the recording
     * - 'recording_sid' (string) Twilio recording SID
     * @return void
     */
    public function updateCallRecording(array $data): void
    {
        $call = Call::where('twilio_call_sid', $data['call_sid'])->first();

        if ($call) {
            $this->callsRepository->update($call, [
                'recording_url' => $data['recording_url'],
                'recording_sid' => $data['recording_sid']
            ]);
        }
    }

    /**
     * Log call outcome and notes
     *
     * @param int $callId
     * @param array $data Contains:
     * - 'outcome' (string) The call outcome
     * - 'note' (string|null) Optional notes about the call
     * @return Call
     * @throws ModelNotFoundException
     */
    public function logCall(int $callId, array $data): Call
    {
        $call = $this->callsRepository->findById($callId);
        if (!$call) {
            throw new ModelNotFoundException('Call not found');
        }

        return $this->callsRepository->update($call, [
            'outcome' => $data['outcome'],
            'note' => $data['note'] ?? null
        ]);
    }

    /**
     * Get call history with filters
     *
     * @param array $filters Contains:
     * - 'agent_id' (int|null) Filter by agent ID
     * - 'person_id' (int|null) Filter by person ID
     * - 'per_page' (int) Number of items per page
     * - 'page' (int) Current page number
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getCallHistory(array $filters = [])
    {
        $perPage = $filters['per_page'] ?? 20;
        $page = $filters['page'] ?? 1;

        $query = Call::with(['person', 'user']);

        if (isset($filters['agent_id'])) {
            $query->where('user_id', $filters['agent_id']);
        }

        if (isset($filters['person_id'])) {
            $query->where('person_id', $filters['person_id']);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Generate wait music TwiML
     *
     * @return VoiceResponse
     */
    public function generateWaitMusic(): VoiceResponse
    {
        $response = new VoiceResponse();
        $response->play('http://com.twilio.music.classical.s3.amazonaws.com/BusyStrings.wav');
        return $response;
    }
}
