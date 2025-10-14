<?php

namespace App\Http\Requests\Teams;

use Illuminate\Foundation\Http\FormRequest;

class BulkDeleteTeamRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'is_all_selected' => 'required|boolean',
            'exception_ids' => 'required_if:is_all_selected,true|array',
            'exception_ids.*' => 'integer|exists:teams,id',
            'ids' => 'required_if:is_all_selected,false|array',
            'ids.*' => 'integer|exists:teams,id',
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
            'is_all_selected.required' => 'The is_all_selected field is required.',
            'is_all_selected.boolean' => 'The is_all_selected field must be true or false.',
            'exception_ids.required_if' => 'The exception_ids field is required when is_all_selected is true.',
            'exception_ids.array' => 'The exception_ids must be an array.',
            'exception_ids.*.integer' => 'Each exception ID must be an integer.',
            'exception_ids.*.exists' => 'One or more exception teams do not exist.',
            'ids.required_if' => 'The ids field is required when is_all_selected is false.',
            'ids.array' => 'The ids must be an array.',
            'ids.*.integer' => 'Each ID must be an integer.',
            'ids.*.exists' => 'One or more selected teams do not exist.',
        ];
    }
}
