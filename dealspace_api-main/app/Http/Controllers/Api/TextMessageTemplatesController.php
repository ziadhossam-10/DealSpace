<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TextMessageTemplates\BulkDeleteTextMessageTemplateRequest;
use App\Http\Requests\TextMessageTemplates\StoreTextMessageTemplateRequest;
use App\Http\Requests\TextMessageTemplates\UpdateTextMessageTemplateRequest;
use App\Http\Resources\TextMessageTemplateCollection;
use App\Http\Resources\TextMessageTemplateResource;
use App\Services\TextMessageTemplates\TextMessageTemplateServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TextMessageTemplatesController extends Controller
{
    protected $textMessageTemplateService;

    public function __construct(TextMessageTemplateServiceInterface $textMessageTemplateService)
    {
        $this->textMessageTemplateService = $textMessageTemplateService;
    }

    /**
     * Get all textMessage templates.
     *
     * @param Request $request
     * @return JsonResponse JSON response containing all textMessage templates.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);
        $search = $request->input('search', null);
        $userId = $request->user()->id;

        $textMessageTemplates = $this->textMessageTemplateService->getAll($userId, $perPage, $page, $search);

        return successResponse(
            'TextMessage templates retrieved successfully',
            new TextMessageTemplateCollection($textMessageTemplates)
        );
    }

    /**
     * Get a specific textMessage template by ID.
     *
     * @param int $id The ID of the textMessage template to retrieve.
     * @return JsonResponse JSON response containing the textMessage template.
     */
    public function show(int $id): JsonResponse
    {
        $textMessageTemplate = $this->textMessageTemplateService->findById($id);

        return successResponse(
            'TextMessage template retrieved successfully',
            new TextMessageTemplateResource($textMessageTemplate)
        );
    }

    /**
     * Create a new textMessage template.
     *
     * @param StoreTextMessageTemplateRequest $request The request instance containing the data to create an textMessage template.
     * @return JsonResponse JSON response containing the created textMessage template and a 201 status code.
     */
    public function store(StoreTextMessageTemplateRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;

        $textMessageTemplate = $this->textMessageTemplateService->create($data);

        return successResponse(
            'TextMessage template created successfully',
            new TextMessageTemplateResource($textMessageTemplate),
            201
        );
    }

    /**
     * Update an existing textMessage template.
     *
     * @param UpdateTextMessageTemplateRequest $request The request instance containing the data to update.
     * @param int $id The ID of the textMessage template to update.
     * @return JsonResponse JSON response containing the updated textMessage template.
     */
    public function update(UpdateTextMessageTemplateRequest $request, int $id): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;

        $textMessageTemplate = $this->textMessageTemplateService->update($id, $data);

        return successResponse(
            'TextMessage template updated successfully',
            new TextMessageTemplateResource($textMessageTemplate)
        );
    }

    /**
     * Delete an textMessage template.
     *
     * @param int $id The ID of the textMessage template to delete.
     * @return JsonResponse JSON response indicating the result of the deletion.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->textMessageTemplateService->delete($id);

        return successResponse(
            'TextMessage template deleted successfully',
            null
        );
    }

    /**
     * Bulk delete textMessage templates based on provided parameters
     *
     * @param BulkDeleteTextMessageTemplateRequest $request
     * @return JsonResponse
     */
    public function bulkDelete(BulkDeleteTextMessageTemplateRequest $request): JsonResponse
    {
        $deletedCount = $this->textMessageTemplateService->bulkDelete($request->validated());

        return successResponse(
            'TextMessage templates deleted successfully',
            ['count' => $deletedCount]
        );
    }
}
