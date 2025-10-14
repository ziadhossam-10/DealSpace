<?php

namespace App\Http\Requests\Tasks;

use Illuminate\Foundation\Http\FormRequest;

class GetTasksRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            'person_id' => 'nullable|integer|exists:people,id',
            'assigned_user_id' => 'nullable|integer|exists:users,id',
            'is_completed' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'per_page.integer' => 'Per page must be a valid integer.',
            'per_page.min' => 'Per page must be at least 1.',
            'per_page.max' => 'Per page cannot exceed 100.',
            'page.integer' => 'Page must be a valid integer.',
            'page.min' => 'Page must be at least 1.',
            'person_id.integer' => 'Person ID must be a valid integer.',
            'person_id.exists' => 'The selected person does not exist.',
            'assigned_user_id.integer' => 'Assigned user ID must be a valid integer.',
            'assigned_user_id.exists' => 'The selected assigned user does not exist.',
            'is_completed.boolean' => 'Is completed must be a boolean value.',
        ];
    }
}
