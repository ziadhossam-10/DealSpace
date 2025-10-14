<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Http\Requests\Users\BulkDeleteUserRequest;
use App\Http\Requests\Users\BulkExportUserRequest;
use App\Http\Requests\Users\ImportUserRequest;
use App\Http\Resources\UserResource;
use App\Http\Resources\UserCollection;
use App\Services\Users\UserServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    protected $userService;

    public function __construct(UserServiceInterface $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Display a paginated list of users.
     *
     * @param Request $request The request instance containing query parameters.
     * @return JsonResponse JSON response containing the list of users and pagination metadata.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);
        $role = $request->input('role', null);
        $search = $request->input('search', null);

        $users = $this->userService->getAll($perPage, $page, $role, $search);

        return successResponse(
            'Users retrieved successfully',
            new UserCollection($users)
        );
    }

    /**
     * Store a newly created User in storage.
     *
     * @param StoreUserRequest $request The request instance containing the data to store.
     * @return JsonResponse JSON response containing the created User and a 201 status code.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->userService->create($request->validated());
        return successResponse(
            'User created successfully',
            new UserResource($user),
            201
        );
    }

    /**
     * Display the specified User.
     *
     * @param int $id The ID of the User to retrieve.
     * @return JsonResponse JSON response containing the retrieved User.
     */
    public function show(int $id): JsonResponse
    {
        $user = $this->userService->findById($id);
        return successResponse(
            'User retrieved successfully',
            new UserResource($user)
        );
    }

    /**
     * Update the specified User in storage.
     *
     * @param UpdateUserRequest $request The request instance containing the data to update.
     * @param int $id The ID of the User to update.
     * @return JsonResponse JSON response containing the updated User.
     */
    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        $user = $this->userService->update($id, $request->validated());
        return successResponse(
            'User updated successfully',
            new UserResource($user)
        );
    }

    /**
     * Remove the specified User from storage.
     *
     * @param int $id The ID of the User to delete.
     * @return JsonResponse JSON response containing the result of the deletion.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->userService->delete($id);
        return successResponse(
            'User deleted successfully',
            null
        );
    }

    /**
     * Bulk delete users based on provided parameters
     *
     * @param BulkDeleteUserRequest $request
     * @return JsonResponse
     */
    public function bulkDelete(BulkDeleteUserRequest $request): JsonResponse
    {
        $deletedCount = $this->userService->bulkDelete($request->validated());

        return successResponse(
            'Users deleted successfully',
            ['count' => $deletedCount]
        );
    }

    /**
     * Download Excel template for users import
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function downloadTemplate()
    {
        return $this->userService->downloadExcelTemplate();
    }

    /**
     * Import users from Excel file
     *
     * @param ImportUserRequest $request
     * @return JsonResponse
     */
    public function import(ImportUserRequest $request): JsonResponse
    {
        $result = $this->userService->importExcel($request->getFile());

        return successResponse(
            sprintf(
                'Import completed. Total: %d, Created: %d, Failed: %d',
                $result['total'],
                $result['created'],
                $result['failed']
            ),
            $result
        );
    }

    /**
     * Bulk export users to Excel based on provided parameters
     *
     * @param BulkExportUserRequest $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function bulkExport(BulkExportUserRequest $request)
    {
        return $this->userService->bulkExport($request->validated());
    }
}
