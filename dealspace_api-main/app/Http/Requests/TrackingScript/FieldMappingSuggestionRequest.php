<?php

namespace App\Http\Requests\TrackingScript;

use Illuminate\Foundation\Http\FormRequest;

class FieldMappingSuggestionRequest extends FormRequest
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
            'form_fields' => 'required|array',
            'form_fields.*' => 'string'
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
            'form_fields.required' => 'Form fields are required.',
            'form_fields.array' => 'Form fields must be an array.',
            'form_fields.*.string' => 'Each form field must be a string.',
        ];
    }
}
