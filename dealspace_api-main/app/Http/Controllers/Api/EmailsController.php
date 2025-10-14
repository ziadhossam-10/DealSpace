<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Emails\GetEmailsRequest;
use App\Http\Requests\Emails\SendEmailRequest;
use App\Http\Resources\EmailCollection;
use App\Http\Resources\EmailResource;
use App\Services\Emails\EmailServiceInterface;

class EmailsController extends Controller
{
    protected $emailService;

    public function __construct(EmailServiceInterface $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Get all emails for the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(GetEmailsRequest $request)
    {
        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);
        $personId = $request->input('person_id', null);

        $emails = $this->emailService->getAll($personId, $perPage, $page);

        return successResponse(
            'Emails retrieved successfully',
            new EmailCollection($emails)
        );
    }

    /**
     * Store a new email.
     *
     * @param SendEmailRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(SendEmailRequest $request)
    {
        $userId = $request->user()->id;

        $email = $this->emailService->sendEmail($request->validated(), $userId);

        return successResponse(
            'Email sent successfully',
            new EmailResource($email),
            201
        );
    }

    public function show(int $id)
    {
        $email = $this->emailService->findById($id);

        return successResponse(
            'Email retrieved successfully',
            new EmailResource($email)
        );
    }
}
