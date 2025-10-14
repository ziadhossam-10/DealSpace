<?php

namespace App\Http\Requests\Teams;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTeamRequest extends FormRequest
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
            'userIds' => 'sometimes|array',
            'userIds.*' => 'required|integer|exists:users,id',
            'leaderIds' => 'sometimes|array',
            'leaderIds.*' => 'required|integer|exists:users,id',
            'userIdsToDelete' => 'sometimes|array',
            'userIdsToDelete.*' => 'required|integer|exists:users,id',
            'leaderIdsToDelete' => 'sometimes|array',
            'leaderIdsToDelete.*' => 'required|integer|exists:users,id',
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
            'name.required' => 'The team name is required.',
            'name.string' => 'The team name must be a string.',
            'name.max' => 'The team name may not be greater than 255 characters.',
            'userIds.array' => 'The user IDs must be an array.',
            'userIds.*.required' => 'Each user ID is required.',
            'userIds.*.integer' => 'Each user ID must be an integer.',
            'userIds.*.exists' => 'One or more selected users do not exist.',
            'leaderIds.array' => 'The leader IDs must be an array.',
            'leaderIds.*.required' => 'Each leader ID is required.',
            'leaderIds.*.integer' => 'Each leader ID must be an integer.',
            'leaderIds.*.exists' => 'One or more selected leaders do not exist.',
            'userIdsToDelete.array' => 'The user IDs to delete must be an array.',
            'userIdsToDelete.*.required' => 'Each user ID to delete is required.',
            'userIdsToDelete.*.integer' => 'Each user ID to delete must be an integer.',
            'userIdsToDelete.*.exists' => 'One or more users to delete do not exist.',
            'leaderIdsToDelete.array' => 'The leader IDs to delete must be an array.',
            'leaderIdsToDelete.*.required' => 'Each leader ID to delete is required.',
            'leaderIdsToDelete.*.integer' => 'Each leader ID to delete must be an integer.',
            'leaderIdsToDelete.*.exists' => 'One or more leaders to delete do not exist.',
        ];
    }
}
