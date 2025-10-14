<?php

namespace App\Http\Requests\Teams;

use Illuminate\Foundation\Http\FormRequest;

class StoreTeamRequest extends FormRequest
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
            'userIds' => 'required|array|min:1',
            'userIds.*' => 'required|integer|exists:users,id',
            'leaderIds' => 'nullable|array',
            'leaderIds.*' => 'required|integer|exists:users,id',
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
            'userIds.required' => 'At least one user must be assigned to the team.',
            'userIds.array' => 'The user IDs must be an array.',
            'userIds.min' => 'At least one user must be assigned to the team.',
            'userIds.*.required' => 'Each user ID is required.',
            'userIds.*.integer' => 'Each user ID must be an integer.',
            'userIds.*.exists' => 'One or more selected users do not exist.',
            'leaderIds.array' => 'The leader IDs must be an array.',
            'leaderIds.*.required' => 'Each leader ID is required.',
            'leaderIds.*.integer' => 'Each leader ID must be an integer.',
            'leaderIds.*.exists' => 'One or more selected leaders do not exist.',
        ];
    }
}
