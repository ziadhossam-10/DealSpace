<?php

namespace App\Http\Requests\EmailTemplates;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmailTemplateRequest extends FormRequest
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
            'name' => 'sometimes|required|string|max:255',
            'subject' => 'sometimes|required|string|max:255',
            'body' => 'sometimes|required|string',
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
            'name.required' => 'The email template name is required.',
            'name.string' => 'The email template name must be a string.',
            'name.max' => 'The email template name may not be greater than 255 characters.',
            'subject.required' => 'The email subject is required.',
            'subject.string' => 'The email subject must be a string.',
            'subject.max' => 'The email subject may not be greater than 255 characters.',
            'body.required' => 'The email body is required.',
            'body.string' => 'The email body must be a string.',
            'is_shared.boolean' => 'The is_shared field must be true or false.',
            'user_id.required' => 'The user ID is required.',
            'user_id.integer' => 'The user ID must be an integer.',
            'user_id.exists' => 'The selected user does not exist.',
        ];
    }
}
