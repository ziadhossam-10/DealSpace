<?php

namespace App\Http\Requests\TextMessages;

use Illuminate\Foundation\Http\FormRequest;

class StoreTextMessageRequest extends FormRequest
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
            'person_id' => 'required|integer|exists:people,id',
            'message' => 'required|string',
            'to_number' => 'required|string|max:20',
            'is_incoming' => 'boolean',
            'external_label' => 'nullable|string|max:255',
            'external_url' => 'nullable|url|max:500',
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
            'person_id.required' => 'The person ID is required.',
            'person_id.exists' => 'The selected person does not exist.',
            'message.required' => 'The message body is required.',
            'to_number.required' => 'The to number is required.',
            'from_number.required' => 'The from number is required.',
            'external_url.url' => 'The external URL must be a valid URL.',
        ];
    }
}
