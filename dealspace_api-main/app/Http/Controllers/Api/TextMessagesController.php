<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TextMessages\StoreTextMessageRequest;
use App\Http\Requests\TextMessages\GetTextMessagesRequest;
use App\Http\Resources\TextMessageCollection;
use App\Http\Resources\TextMessageResource;
use App\Services\TextMessages\TextMessageServiceInterface;
use Illuminate\Http\JsonResponse;

class TextMessagesController extends Controller
{
    protected $textMessageService;
    protected $twilioNumber;

    public function __construct(TextMessageServiceInterface $textMessageService)
    {
        $this->textMessageService = $textMessageService;
        $this->twilioNumber = config('services.twilio.phone_number');
    }

    /**
     * Get all text messages.
     *
     * @return JsonResponse JSON response containing all text messages.
     */
    public function index(GetTextMessagesRequest $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);
        $personId = $request->input('person_id', null);

        $textMessages = $this->textMessageService->getAll($perPage, $page, $personId);

        return successResponse(
            'Text messages retrieved successfully',
            new TextMessageCollection($textMessages)
        );
    }

    /**
     * Get a specific text message by ID.
     *
     * @param int $id The ID of the text message to retrieve.
     * @return JsonResponse JSON response containing the text message.
     */
    public function show(int $id): JsonResponse
    {
        $textMessage = $this->textMessageService->findById($id);

        return successResponse(
            'Text message retrieved successfully',
            new TextMessageResource($textMessage)
        );
    }

    /**
     * Create a new text message.
     *
     * @param StoreTextMessageRequest $request The request instance containing the data to create a text message.
     * @return JsonResponse JSON response containing the created text message and a 201 status code.
     */
    public function store(StoreTextMessageRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;
        $data['from_number'] = $this->twilioNumber;

        $textMessage = $this->textMessageService->create($data);

        return successResponse(
            'Text message created successfully',
            new TextMessageResource($textMessage),
            201
        );
    }
}
