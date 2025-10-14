<?php

namespace App\Http\Requests\Notes;

use Illuminate\Foundation\Http\FormRequest;

class AssignMentionsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Adjust based on your authorization logic
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'integer|exists:users,id|distinct',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_ids.required' => 'At least one user must be mentioned.',
            'user_ids.array' => 'User IDs must be provided as an array.',
            'user_ids.min' => 'At least one user must be mentioned.',
            'user_ids.*.integer' => 'Each user ID must be a valid integer.',
            'user_ids.*.exists' => 'One or more selected users do not exist.',
            'user_ids.*.distinct' => 'Duplicate user IDs are not allowed.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'user_ids' => 'mentioned users',
            'user_ids.*' => 'user',
        ];
    }
}
