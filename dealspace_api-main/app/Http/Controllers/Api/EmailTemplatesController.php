<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmailTemplates\BulkDeleteEmailTemplateRequest;
use App\Http\Requests\EmailTemplates\StoreEmailTemplateRequest;
use App\Http\Requests\EmailTemplates\UpdateEmailTemplateRequest;
use App\Http\Resources\EmailTemplateCollection;
use App\Http\Resources\EmailTemplateResource;
use App\Services\EmailTemplates\EmailTemplateServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailTemplatesController extends Controller
{
    protected $emailTemplateService;

    public function __construct(EmailTemplateServiceInterface $emailTemplateService)
    {
        $this->emailTemplateService = $emailTemplateService;
    }

    /**
     * Get all email templates.
     *
     * @param Request $request
     * @return JsonResponse JSON response containing all email templates.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);
        $search = $request->input('search', null);
        $userId = $request->user()->id;

        $emailTemplates = $this->emailTemplateService->getAll($userId, $perPage, $page, $search);

        return successResponse(
            'Email templates retrieved successfully',
            new EmailTemplateCollection($emailTemplates)
        );
    }

    /**
     * Get a specific email template by ID.
     *
     * @param int $id The ID of the email template to retrieve.
     * @return JsonResponse JSON response containing the email template.
     */
    public function show(int $id): JsonResponse
    {
        $emailTemplate = $this->emailTemplateService->findById($id);

        return successResponse(
            'Email template retrieved successfully',
            new EmailTemplateResource($emailTemplate)
        );
    }

    /**
     * Create a new email template.
     *
     * @param StoreEmailTemplateRequest $request The request instance containing the data to create an email template.
     * @return JsonResponse JSON response containing the created email template and a 201 status code.
     */
    public function store(StoreEmailTemplateRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;

        $emailTemplate = $this->emailTemplateService->create($data);

        return successResponse(
            'Email template created successfully',
            new EmailTemplateResource($emailTemplate),
            201
        );
    }

    /**
     * Update an existing email template.
     *
     * @param UpdateEmailTemplateRequest $request The request instance containing the data to update.
     * @param int $id The ID of the email template to update.
     * @return JsonResponse JSON response containing the updated email template.
     */
    public function update(UpdateEmailTemplateRequest $request, int $id): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;

        $emailTemplate = $this->emailTemplateService->update($id, $data);

        return successResponse(
            'Email template updated successfully',
            new EmailTemplateResource($emailTemplate)
        );
    }

    /**
     * Delete an email template.
     *
     * @param int $id The ID of the email template to delete.
     * @return JsonResponse JSON response indicating the result of the deletion.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->emailTemplateService->delete($id);

        return successResponse(
            'Email template deleted successfully',
            null
        );
    }

    /**
     * Bulk delete email templates based on provided parameters
     *
     * @param BulkDeleteEmailTemplateRequest $request
     * @return JsonResponse
     */
    public function bulkDelete(BulkDeleteEmailTemplateRequest $request): JsonResponse
    {
        $deletedCount = $this->emailTemplateService->bulkDelete($request->validated());

        return successResponse(
            'Email templates deleted successfully',
            ['count' => $deletedCount]
        );
    }
}
