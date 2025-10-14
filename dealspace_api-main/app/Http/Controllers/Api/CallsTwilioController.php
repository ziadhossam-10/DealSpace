<?php

namespace App\Http\Controllers\Api;

use App\Services\Calls\CallTwilioServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Calls\InitiateCallRequest;
use App\Http\Requests\Calls\LogCallRequest;
use App\Http\Requests\Calls\GenerateTokenRequest;
use App\Http\Resources\CallCollection;
use App\Http\Resources\CallResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Twilio\TwiML\VoiceResponse;

class CallsTwilioController extends Controller
{
    protected $twilioService;

    public function __construct(CallTwilioServiceInterface $twilioService)
    {
        $this->twilioService = $twilioService;
    }
    public function handleVoiceCall(Request $request)
    {
        $to = $request->input('To');

        $response = new VoiceResponse();

        if ($to) {
            $response->dial($to, [
                'callerId' => '+12027604921',
                'timeout' => 30,
                'answerOnBridge' => true,
            ]);
        } else {
            $response->say("No number specified.");
        }

        return response($response, 200)->header('Content-Type', 'text/xml');
    }

    public function recordingCallback(Request $request)
    {
        Log::info('Recording Callback', $request->all());
        return response()->json(['status' => 'ok']);
    }

    /**
     * Initiate outbound call
     *
     * @param InitiateCallRequest $request
     * @return JsonResponse
     */
    public function initiateCall(InitiateCallRequest $request): JsonResponse
    {
        $result = $this->twilioService->initiateCall($request->validated());

        return successResponse(
            'Call initiated successfully',
            $result
        );
    }

    /**
     * Generate access token for Twilio Client
     *
     * @param GenerateTokenRequest $request
     * @return JsonResponse
     */
    public function generateAccessToken(GenerateTokenRequest $request): JsonResponse
    {
        $tokenData = $this->twilioService->generateAccessToken($request->validated()['agent_id']);

        return successResponse(
            'Access token generated successfully',
            $tokenData
        );
    }

    /**
     * Log call after completion
     *
     * @param LogCallRequest $request
     * @param int $callId
     * @return JsonResponse
     */
    public function logCall(LogCallRequest $request, int $callId): JsonResponse
    {
        $call = $this->twilioService->logCall($callId, $request->validated());

        return successResponse(
            'Call logged successfully',
            new CallResource($call)
        );
    }

    /**
     * Get call history
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getCallHistory(Request $request): JsonResponse
    {
        $filters = [
            'agent_id' => $request->input('agent_id'),
            'person_id' => $request->input('person_id'),
            'per_page' => $request->input('per_page', 20),
            'page' => $request->input('page', 1)
        ];

        $calls = $this->twilioService->getCallHistory($filters);

        return successResponse(
            'Call history retrieved successfully',
            new CallCollection($calls)
        );
    }

    // =====================================================
    // TwiML Response Methods (Return XML, not JSON)
    // =====================================================

    /**
     * TwiML for outbound calls
     *
     * @param int $callId
     * @return Response
     */
    public function outboundTwiML(int $callId): Response
    {
        $twiml = $this->twilioService->generateOutboundTwiML($callId);
        return response($twiml, 200, ['Content-Type' => 'text/xml']);
    }

    /**
     * Handle incoming calls
     *
     * @param Request $request
     * @return Response
     */
    public function incomingCall(Request $request): Response
    {
        $data = [
            'from' => $request->input('From'),
            'to' => $request->input('To')
        ];

        $twiml = $this->twilioService->handleIncomingCall($data);
        return response($twiml, 200, ['Content-Type' => 'text/xml']);
    }

    /**
     * Queue callback for incoming calls
     *
     * @param int $callId
     * @return Response
     */
    public function queueCallback(int $callId): Response
    {
        $twiml = $this->twilioService->distributeToAgents($callId);
        return response($twiml, 200, ['Content-Type' => 'text/xml']);
    }

    /**
     * Agent screening
     *
     * @param int $agentId
     * @param int $callId
     * @return Response
     */
    public function agentScreen(int $agentId, int $callId): Response
    {
        $twiml = $this->twilioService->agentScreening($agentId, $callId);
        return response($twiml, 200, ['Content-Type' => 'text/xml']);
    }

    /**
     * Agent accepts or declines call
     *
     * @param Request $request
     * @param int $agentId
     * @param int $callId
     * @return Response
     */
    public function agentAccept(Request $request, int $agentId, int $callId): Response
    {
        $data = [
            'digits' => $request->input('Digits'),
            'agent_id' => $agentId,
            'call_id' => $callId
        ];

        $twiml = $this->twilioService->handleAgentResponse($data);
        return response($twiml, 200, ['Content-Type' => 'text/xml']);
    }

    /**
     * Wait music for queue
     *
     * @return Response
     */
    public function waitMusic(): Response
    {
        $twiml = $this->twilioService->generateWaitMusic();
        return response($twiml, 200, ['Content-Type' => 'text/xml']);
    }

    // =====================================================
    // Webhook Callbacks (Return Empty Response)
    // =====================================================

    /**
     * Call status callback
     *
     * @param Request $request
     * @return Response
     */
    public function statusCallback(Request $request): Response
    {
        Log::info("statusCallback", [$request]);
        $data = [
            'call_sid' => $request->input('CallSid'),
            'call_status' => $request->input('CallStatus'),
            'duration' => $request->input('CallDuration', 0)
        ];

        $this->twilioService->updateCallStatus($data);

        return response('', 200);
    }


    /**
     * Handle Voice SDK calls (TwiML Application webhook)
     *
     * @param Request $request
     * @return Response
     */
    public function handleVoiceSDKCall(Request $request): Response
    {
        Log::info('handleVoiceSDKCall', [$request]);
        $data = [
            'To' => $request->input('To'),
            'From' => $request->input('From'),
            'AgentId' => $request->input('AgentId'),
            'PersonId' => $request->input('PersonId'),
        ];

        $twiml = $this->twilioService->handleOutboundCall($data);
        return response($twiml, 200, ['Content-Type' => 'text/xml']);
    }
}
