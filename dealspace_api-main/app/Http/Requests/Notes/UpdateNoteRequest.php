<?php

namespace App\Http\Requests\Notes;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNoteRequest extends FormRequest
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
            'subject' => 'sometimes|string|max:255',
            'body' => 'sometimes|string',
            'person_id' => 'sometimes|integer|exists:people,id',

            // Replace all mentions
            'mentions' => 'sometimes|array',
            'mentions.*' => 'integer|exists:users,id',

            // Add specific mentions
            'mentions_to_add' => 'sometimes|array',
            'mentions_to_add.*' => 'integer|exists:users,id',

            // Remove specific mentions
            'mentions_to_remove' => 'sometimes|array',
            'mentions_to_remove.*' => 'integer|exists:users,id',
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
            'subject.max' => 'The note subject may not be greater than 255 characters.',
            'person_id.exists' => 'The selected person does not exist.',
            'mentions.array' => 'Mentions must be an array.',
            'mentions.*.integer' => 'Each mention must be a valid user ID.',
            'mentions.*.exists' => 'One or more mentioned users do not exist.',
            'mentions_to_add.array' => 'Mentions to add must be an array.',
            'mentions_to_add.*.integer' => 'Each mention to add must be a valid user ID.',
            'mentions_to_add.*.exists' => 'One or more users to mention do not exist.',
            'mentions_to_remove.array' => 'Mentions to remove must be an array.',
            'mentions_to_remove.*.integer' => 'Each mention to remove must be a valid user ID.',
            'mentions_to_remove.*.exists' => 'One or more users to remove from mentions do not exist.',
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
            'mentions_to_add.*' => 'user to mention',
            'mentions_to_remove.*' => 'user to remove from mentions',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Prevent conflicting mention operations
            if ($this->has('mentions') && ($this->has('mentions_to_add') || $this->has('mentions_to_remove'))) {
                $validator->errors()->add(
                    'mentions',
                    'Cannot use "mentions" with "mentions_to_add" or "mentions_to_remove" at the same time.'
                );
            }
        });
    }
}
