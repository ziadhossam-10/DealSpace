<?php

namespace App\Http\Requests\Calls;

use Illuminate\Foundation\Http\FormRequest;

class LogCallRequest extends FormRequest
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
            'outcome' => 'required|string|max:50',
            'note' => 'nullable|string|max:1000'
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'outcome.required' => 'Call outcome is required',
            'outcome.string' => 'Call outcome must be a string',
            'note.max' => 'Note cannot exceed 1000 characters'
        ];
    }
}
