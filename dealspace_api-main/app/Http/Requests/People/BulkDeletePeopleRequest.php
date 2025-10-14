<?php

namespace App\Http\Requests\People;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkDeletePeopleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // Update this based on your authorization logic
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'is_all_selected' => 'required|boolean',
            'ids' => [
                'array',
                Rule::requiredIf(function () {
                    return $this->input('is_all_selected') === false &&
                        empty($this->input('ids'));
                }),
            ],
            'ids.*' => 'integer|exists:people,id',
            'exception_ids' => 'array',
            'exception_ids.*' => 'integer|exists:people,id',
            'stage_id' => 'nullable|integer|exists:stages,id',
            'team_id' => 'nullable|integer|exists:teams,id',
            'search' => 'nullable|string|max:255',
            'deal_type_id' => 'nullable|integer|exists:deal_types,id',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'integer|exists:users,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'is_all_selected.required' => 'Please specify whether all items are selected.',
            'is_all_selected.boolean' => 'The is_all_selected field must be true or false.',
            'ids.required_if' => 'Please provide IDs to delete when is_all_selected is false.',
            'ids.array' => 'The IDs must be provided as an array.',
            'ids.*.integer' => 'Each ID must be an integer.',
            'ids.*.exists' => 'One or more IDs do not exist in the database.',
            'exception_ids.array' => 'The exception IDs must be provided as an array.',
            'exception_ids.*.integer' => 'Each exception ID must be an integer.',
            'exception_ids.*.exists' => 'One or more exception IDs do not exist in the database.',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        // Convert string boolean to actual boolean if needed
        if ($this->has('is_all_selected') && is_string($this->is_all_selected)) {
            $this->merge([
                'is_all_selected' => filter_var($this->is_all_selected, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            ]);
        }
    }
}
