<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmailAccountCollection;
use App\Services\EmailAccounts\EmailAccountServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailAccountsController extends Controller
{
    protected $emailAccountService;

    public function __construct(EmailAccountServiceInterface $emailAccountService)
    {
        $this->emailAccountService = $emailAccountService;
    }

    /**
     * Get all email accounts.
     *
     * @param Request $request
     * @return JsonResponse JSON response containing all email accounts.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);

        $emailAccounts = $this->emailAccountService->getAll($perPage, $page);

        return successResponse(
            'Email accounts retrieved successfully',
            new EmailAccountCollection($emailAccounts)
        );
    }

    /**
     * Delete an email account.
     *
     * @param int $id The ID of the email account to delete.
     * @return JsonResponse JSON response indicating the result of the deletion.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->emailAccountService->delete($id);

        return successResponse(
            'Email account deleted successfully',
            null
        );
    }
}
