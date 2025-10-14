<?php

namespace App\Services\Calls;

use App\Models\Call;
use Twilio\TwiML\VoiceResponse;

interface CallTwilioServiceInterface
{
    /**
     * Initiate an outbound call
     *
     * @param array $data Contains:
     * - 'agent_id' (int) The ID of the agent making the call
     * - 'to_number' (string) The phone number to call
     * - 'person_id' (int|null) Optional person ID associated with the call
     * @return array
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \Exception
     */
    public function initiateCall(array $data): array;

    /**
     * Generate access token for Twilio Client
     *
     * @param int $agentId
     * @return array
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function generateAccessToken(int $agentId): array;

    /**
     * Generate TwiML for outbound calls
     *
     * @param int $callId
     * @return VoiceResponse
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function generateOutboundTwiML(int $callId): VoiceResponse;

    /**
     * Handle incoming calls
     *
     * @param array $data Contains:
     * - 'from' (string) The phone number calling
     * - 'to' (string) The phone number being called
     * @return VoiceResponse
     */
    public function handleIncomingCall(array $data): VoiceResponse;

    /**
     * Distribute call to available agents
     *
     * @param int $callId
     * @return VoiceResponse
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function distributeToAgents(int $callId): VoiceResponse;

    /**
     * Agent call screening
     *
     * @param int $agentId
     * @param int $callId
     * @return VoiceResponse
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function agentScreening(int $agentId, int $callId): VoiceResponse;

    /**
     * Handle agent accepting or declining a call
     *
     * @param array $data Contains:
     * - 'digits' (string) The digit pressed by the agent
     * - 'agent_id' (int) The ID of the agent
     * - 'call_id' (int) The ID of the call
     * @return VoiceResponse
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function handleAgentResponse(array $data): VoiceResponse;

    /**
     * Update call status from Twilio callback
     *
     * @param array $data Contains:
     * - 'call_sid' (string) Twilio call SID
     * - 'call_status' (string) The call status
     * - 'duration' (int) Call duration in seconds
     * @return void
     */
    public function updateCallStatus(array $data): void;

    /**
     * Update call recording information
     *
     * @param array $data Contains:
     * - 'call_sid' (string) Twilio call SID
     * - 'recording_url' (string) URL to the recording
     * - 'recording_sid' (string) Twilio recording SID
     * @return void
     */
    public function updateCallRecording(array $data): void;

    /**
     * Log call outcome and notes
     *
     * @param int $callId
     * @param array $data Contains:
     * - 'outcome' (string) The call outcome
     * - 'note' (string|null) Optional notes about the call
     * @return Call
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function logCall(int $callId, array $data): Call;

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
    public function getCallHistory(array $filters = []);

    /**
     * Generate wait music TwiML
     *
     * @return VoiceResponse
     */
    public function generateWaitMusic(): VoiceResponse;

    /**
     * Handle outbound calls from Voice SDK
     * This is called when device.connect() is used with TwiML Application
     *
     * @param array $data Contains parameters from the Voice SDK call
     * @return VoiceResponse
     */
    public function handleOutboundCall(array $data): VoiceResponse;
}
