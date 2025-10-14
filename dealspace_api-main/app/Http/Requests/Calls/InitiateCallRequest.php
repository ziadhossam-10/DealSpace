<?php

namespace App\Http\Requests\Calls;

use Illuminate\Foundation\Http\FormRequest;

class InitiateCallRequest extends FormRequest
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
            'to_number' => 'required|string|max:20',
            'person_id' => 'nullable|integer|exists:people,id',
            'agent_id' => 'required|integer|exists:users,id'
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'to_number.required' => 'Phone number is required',
            'agent_id.required' => 'Agent ID is required',
            'agent_id.exists' => 'Selected agent does not exist',
            'person_id.exists' => 'Selected person does not exist'
        ];
    }
}
