<?php

namespace App\Http\Controllers\Api\People;

use App\Http\Controllers\Controller;
use App\Http\Requests\People\StorePersonEmailRequest;
use App\Http\Requests\People\UpdatePersonEmailRequest;
use App\Services\People\EmailServiceInterface;
use Illuminate\Http\JsonResponse;

class EmailsController extends Controller
{
    protected $emailService;

    public function __construct(EmailServiceInterface $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Display a listing of emails for the person.
     *
     * @param int $personId The ID of the person to get emails for.
     * @return JsonResponse JSON response containing the list of emails.
     */
    public function index(int $personId): JsonResponse
    {
        $emails = $this->emailService->getAll($personId);

        return successResponse(
            'Emails retrieved successfully',
            $emails
        );
    }

    /**
     * Store a newly created email for the person.
     *
     * @param StorePersonEmailRequest $request The request instance containing the data to create.
     * @param int $personId The ID of the person to add the email to.
     * @return JsonResponse JSON response containing the added email and a 201 status code.
     */
    public function store(StorePersonEmailRequest $request, int $personId): JsonResponse
    {
        $email = $this->emailService->create($personId, $request->validated());

        return successResponse(
            'Email created successfully',
            $email,
            201
        );
    }

    /**
     * Display the specified email of the person.
     *
     * @param int $personId The ID of the person.
     * @param int $emailId The ID of the email to show.
     * @return JsonResponse JSON response containing the email.
     */
    public function show(int $personId, int $emailId): JsonResponse
    {
        $email = $this->emailService->findById($personId, $emailId);

        return successResponse(
            'Email retrieved successfully',
            $email
        );
    }

    /**
     * Update the specified email of the person.
     *
     * @param UpdatePersonEmailRequest $request The request instance containing the data to update.
     * @param int $personId The ID of the person.
     * @param int $emailId The ID of the email to update.
     * @return JsonResponse JSON response containing the updated email.
     */
    public function update(UpdatePersonEmailRequest $request, int $personId, int $emailId): JsonResponse
    {
        $email = $this->emailService->update($personId, $emailId, $request->validated());

        return successResponse(
            'Email updated successfully',
            $email
        );
    }

    /**
     * Remove the specified email from the person.
     *
     * @param int $personId The ID of the person.
     * @param int $emailId The ID of the email to delete.
     * @return JsonResponse JSON response indicating the result of the deletion.
     */
    public function destroy(int $personId, int $emailId): JsonResponse
    {
        $this->emailService->delete($personId, $emailId);

        return successResponse(
            'Email deleted successfully',
            null
        );
    }

    /**
     * Set the specified email as primary for the person.
     *
     * @param int $personId The ID of the person.
     * @param int $emailId The ID of the email to set as primary.
     * @return JsonResponse JSON response indicating the result of the operation.
     */
    public function setPrimary(int $personId, int $emailId): JsonResponse
    {
        $this->emailService->setPrimary($personId, $emailId);
        return successResponse(
            'Email set as primary successfully',
            null
        );
    }
}
