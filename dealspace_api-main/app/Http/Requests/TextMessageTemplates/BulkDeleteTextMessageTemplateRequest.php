<?php

namespace App\Http\Requests\TextMessageTemplates;

use Illuminate\Foundation\Http\FormRequest;

class BulkDeleteTextMessageTemplateRequest extends FormRequest
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
            'exception_ids' => 'sometimes|array',
            'exception_ids.*' => 'integer|exists:text_message_templates,id',
            'ids' => 'sometimes|array',
            'ids.*' => 'integer|exists:text_message_templates,id',
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
            'exception_ids.array' => 'The exception IDs must be an array.',
            'exception_ids.*.integer' => 'Each exception ID must be an integer.',
            'exception_ids.*.exists' => 'One or more exception textMessage templates do not exist.',
            'ids.array' => 'The IDs must be an array.',
            'ids.*.integer' => 'Each ID must be an integer.',
            'ids.*.exists' => 'One or more textMessage templates do not exist.',
        ];
    }
}
