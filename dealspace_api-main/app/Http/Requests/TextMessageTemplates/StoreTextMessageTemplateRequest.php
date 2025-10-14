<?php

namespace App\Http\Requests\TextMessageTemplates;

use Illuminate\Foundation\Http\FormRequest;

class StoreTextMessageTemplateRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'message' => 'required|string|max:255',
            'is_shared' => 'sometimes|boolean',
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
            'name.required' => 'The text message template name is required.',
            'name.string' => 'The text message template name must be a string.',
            'name.max' => 'The text message template name may not be greater than 255 characters.',
            'subject.required' => 'The text message subject is required.',
            'subject.string' => 'The text message subject must be a string.',
            'subject.max' => 'The text message subject may not be greater than 255 characters.',
            'body.required' => 'The text message body is required.',
            'body.string' => 'The text message body must be a string.',
            'is_shared.boolean' => 'The is_shared field must be true or false.',
            'user_id.required' => 'The user ID is required.',
            'user_id.integer' => 'The user ID must be an integer.',
            'user_id.exists' => 'The selected user does not exist.',
        ];
    }
}
