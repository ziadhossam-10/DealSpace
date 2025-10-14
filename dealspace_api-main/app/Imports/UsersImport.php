<?php

namespace App\Imports;

use App\Enums\RoleEnum;
use App\Models\User;
use App\Services\Users\UserServiceInterface;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

class UsersImport implements ToCollection, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    use Importable;

    protected $userService;
    protected $result = [
        'total' => 0,
        'created' => 0,
        'failed' => 0,
        'errors' => []
    ];

    /**
     * Constructor
     *
     * @param UserServiceInterface $userService
     */
    public function __construct(UserServiceInterface $userService)
    {
        $this->userService = $userService;
    }

    /**
     * @param Collection $rows
     */
    public function collection(Collection $rows)
    {
        foreach ($rows as $rowIndex => $row) {
            try {
                $rowNumber = $rowIndex + 2; // Account for header row and 0-indexing

                // Skip empty rows (should be handled by SkipsEmptyRows, but just in case)
                if ($row->filter()->isEmpty()) {
                    continue;
                }

                // Extract user data
                $user = [
                    'name' => $row['name'] ?? null,
                    'email' => $row['email'] ?? null,
                    'password' => isset($row['password']) && !empty($row['password'])
                        ? Hash::make($row['password'])
                        : Hash::make('password'),
                    'role' => RoleEnum::value($row['role'] ?? 'Agent'),
                ];

                // Create new user
                $this->userService->create($user);
                $this->result['created']++;
                $this->result['total']++;
            } catch (\Exception $e) {
                $this->result['failed']++;
                $this->result['errors'][] = "Row {$rowNumber}: " . $e->getMessage();
            }
        }
    }

    /**
     * Get the validation rules that apply to the import.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'nullable|string|min:6',
            'role' => 'nullable|string|in:' . $this->getEnumLabels(RoleEnum::class),
        ];
    }

    /**
     * @return array
     */
    public function customValidationMessages()
    {
        return [
            'name.required' => 'The name field is required.',
            'email.required' => 'The email field is required.',
            'email.email' => 'The email must be a valid email address.',
            'password.min' => 'The password must be at least 6 characters.',
            'role.in' => 'The role must be either ' . $this->getEnumLabels(RoleEnum::class) . '.',
        ];
    }

    /**
     * Get the import results.
     *
     * @return array
     */
    public function getResult(): array
    {
        return $this->result;
    }

    /**
     * Get the enum values as a comma-separated string
     *
     * @param string $enumClass
     * @return string
     */
    protected function getEnumValues($enumClass)
    {
        return implode(',', array_map(fn($case) => $case->value, $enumClass::cases()));
    }

    /**
     * Get the enum labels as a comma-separated string
     *
     * @param string $enumClass
     * @return string
     */
    protected function getEnumLabels($enumClass)
    {
        return implode(',', array_map(fn($case) => RoleEnum::label($case->value), $enumClass::cases()));
    }
}
