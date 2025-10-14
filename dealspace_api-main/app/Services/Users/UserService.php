<?php

namespace App\Services\Users;

use App\Exports\UsersExport;
use App\Exports\UsersExportTemplate;
use App\Imports\UsersImport;
use App\Repositories\Users\UsersRepositoryInterface;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UserService implements UserServiceInterface
{
    protected $userRepository;

    public function __construct(
        UsersRepositoryInterface $userRepository
    ) {
        $this->userRepository = $userRepository;
    }

    /**
     * Retrieves all users with pagination.
     *
     * @param int $perPage The number of items per page
     * @param int $page The page number to retrieve
     * @return LengthAwarePaginator Paginated list of records
     */
    public function getAll(int $perPage = 15, int $page = 1, string $role = null, string $search = null)
    {
        return $this->userRepository->getAll($perPage, $page, $role, $search);
    }

    /**
     * Retrieves a user by ID.
     *
     * @param int $id The ID of the user to retrieve
     * @return User The user with the given ID, or null if none found
     */
    public function findById(int $id)
    {
        return $this->userRepository->findById($id);
    }

    /**
     * Creates a new user with the given data.
     *
     * This method creates a new user with the given data and handles any
     * associated data. If the password is provided, it will be hashed.
     * If an avatar is provided and is an instance of UploadedFile, it
     * will be uploaded.
     *
     * @param array $data The data to use when creating the user
     * @return User The newly created user
     */
    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $user = Auth::user();


            // Handle password hashing if provided
            if (isset($data['password']) && !empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }

            // Handle avatar upload if provided
            if (isset($data['avatar']) && $data['avatar'] instanceof UploadedFile) {
                $data['avatar'] = $this->uploadAvatar($data['avatar']);
            }

            return $this->userRepository->create($data);
        });
    }

    /**
     * Updates an existing user's information.
     *
     * This method updates the details of a user with the specified ID,
     * including their basic information, password, and avatar.
     * If a new password is provided, it will be hashed before updating.
     * If an avatar is provided, the existing avatar will be deleted and
     * the new one will be uploaded.
     *
     * @param int $id The ID of the user to update
     * @param array $data The data to update the user with, which may include:
     * - 'password': The new password for the user
     * - 'avatar': An instance of UploadedFile representing the new avatar
     * @return User The updated user
     */

    public function update(int $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $user = $this->userRepository->findById($id);

            // Handle password hashing if provided and not empty
            if (isset($data['password']) && !empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            } elseif (isset($data['password']) && empty($data['password'])) {
                // If password is empty, remove it from data to avoid updating with empty value
                unset($data['password']);
            }

            // Handle avatar upload if provided
            if (isset($data['avatar']) && $data['avatar'] instanceof UploadedFile) {
                if ($user->getRawOriginal('avatar')) {
                    $this->deleteAvatar($user->getRawOriginal('avatar'));
                }
                $data['avatar'] = $this->uploadAvatar($data['avatar']);
            }

            return $this->userRepository->update($id, $data);
        });
    }

    /**
     * Deletes a user by ID.
     *
     * This method deletes a user with the specified ID, including their
     * associated avatar if present. The deletion is wrapped in a database
     * transaction to ensure data integrity.
     *
     * @param int $id The ID of the user to delete
     * @return mixed The result of the deletion operation
     */

    public function delete(int $id)
    {
        return DB::transaction(function () use ($id) {
            $user = $this->userRepository->findById($id);
            if ($user->getRawOriginal('avatar')) {
                $this->deleteAvatar($user->getRawOriginal('avatar'));
            }
            return $this->userRepository->delete($id);
        });
    }

    /**
     * Deletes multiple users in a single transaction.
     *
     * This method deletes multiple users at once, based on the parameters
     * provided. If all users are selected, then all users except those with
     * IDs in the exception_ids list are deleted. If specific IDs are provided,
     * those users are deleted.
     *
     * The deletion is wrapped in a database transaction to ensure data
     * integrity. Additionally, the avatars of the deleted users are cleaned up
     * after deletion.
     *
     * @param array $params Parameters to control the deletion operation
     *     - is_all_selected (bool): Delete all users except those in exception_ids
     *     - exception_ids (array): IDs to exclude from deletion
     *     - ids (array): IDs of users to delete
     * @return int Number of deleted records
     */
    public function bulkDelete(array $params): int
    {
        return DB::transaction(function () use ($params) {
            $isAllSelected = $params['is_all_selected'] ?? false;
            $exceptionIds = $params['exception_ids'] ?? [];
            $ids = $params['ids'] ?? [];

            // Get users to delete for avatar cleanup
            $usersToDelete = null;

            if ($isAllSelected) {
                if (!empty($exceptionIds)) {
                    // Get users to delete for avatar cleanup before repository call
                    $usersToDelete = User::whereNotIn('id', $exceptionIds)->get();

                    // Delete all except those in exception_ids
                    $result = $this->userRepository->deleteAllExcept($exceptionIds);
                } else {
                    // Get all users for avatar cleanup before repository call
                    $usersToDelete = User::all();

                    // Delete all
                    $result = $this->userRepository->deleteAll();
                }
            } else {
                if (!empty($ids)) {
                    // Get specific users for avatar cleanup before repository call
                    $usersToDelete = User::whereIn('id', $ids)->get();

                    // Delete specific ids
                    $result = $this->userRepository->deleteSome($ids);
                } else {
                    // No records to delete
                    return 0;
                }
            }

            // Clean up avatars
            if ($usersToDelete) {
                foreach ($usersToDelete as $user) {
                    if ($user->getRawOriginal('avatar')) {
                        $this->deleteAvatar($user->getRawOriginal('avatar'));
                    }
                }
            }

            return $result;
        });
    }

    /**
     * Finds a user by their email address.
     *
     * @param string $email The email address to search for.
     * @return User|null The user with the given email address, or null if none found.
     */

    public function findUserByEmail(string $email)
    {
        return $this->userRepository->findByEmail($email);
    }

    /**
     * Uploads an avatar file to the users/avatars folder in the default filesystem
     * and returns the relative path to the uploaded file.
     *
     * @param UploadedFile $file The file to upload
     * @return string The relative path to the uploaded file
     */
    protected function uploadAvatar(UploadedFile $file): string
    {
        $path = $file->store('users/avatars', 'public');
        return $path; // Return relative path, as getAvatarAttribute in User model will prepend storage URL
    }

    /**
     * Deletes an avatar file from the users/avatars folder in the default filesystem.
     *
     * @param string $path The relative path to the avatar file to delete
     */
    protected function deleteAvatar(string $path): void
    {
        Storage::disk('public')->delete($path);
    }

    /**
     * Import users from an Excel file
     *
     * @param UploadedFile $file Excel file to import
     * @return array Results of the import operation
     */
    public function importExcel(UploadedFile $file): array
    {
        // Create a new instance of the UsersImport class and inject this service
        $import = new UsersImport($this);

        // Import the Excel file
        $import->import($file);

        // Return the import results
        return $import->getResult();
    }

    /**
     * Download Excel template for user import
     *
     * @return BinaryFileResponse Excel file for download
     */
    public function downloadExcelTemplate(): BinaryFileResponse
    {
        // Create a new instance of the UsersExportTemplate class
        $export = new UsersExportTemplate();

        // Generate the Excel file with a specific filename
        return $export->download('users_import_template.xlsx');
    }

    /**
     * Export users to Excel based on provided parameters.
     *
     * @param array $params Parameters to control the export operation
     *     - is_all_selected (bool): Export all users except those in exception_ids
     *     - exception_ids (array): IDs to exclude from export
     *     - ids (array): IDs of users to export
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function bulkExport(array $params): BinaryFileResponse
    {
        // Create a new instance of the UsersExport class with parameters
        $export = new UsersExport($params);

        // Generate the Excel file with a dynamic filename including timestamp
        $filename = 'users_export_' . date('Y-m-d_His') . '.xlsx';

        return $export->download($filename);
    }
}
