<?php

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\RoleEnum;
use Illuminate\Support\Facades\Log;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // Adjust based on your authorization logic
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {

        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'role' => 'required|integer|in:' . $this->getEnumValues(RoleEnum::class),
        ];
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
}