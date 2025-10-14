<?php

namespace App\Http\Requests\Notes;

use Illuminate\Foundation\Http\FormRequest;

class StoreNoteRequest extends FormRequest
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
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'person_id' => 'required|integer|exists:people,id',
            'mentions' => 'sometimes|array',
            'mentions.*' => 'integer|exists:users,id',
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
            'subject.required' => 'The note subject is required.',
            'subject.max' => 'The note subject may not be greater than 255 characters.',
            'body.required' => 'The note body is required.',
            'person_id.required' => 'The person ID is required.',
            'person_id.exists' => 'The selected person does not exist.',
            'mentions.array' => 'Mentions must be an array.',
            'mentions.*.integer' => 'Each mention must be a valid user ID.',
            'mentions.*.exists' => 'One or more mentioned users do not exist.',
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
            'person_id' => 'person',
            'mentions.*' => 'mentioned user',
        ];
    }
}
