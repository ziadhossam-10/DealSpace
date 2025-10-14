<?php

namespace App\Http\Requests\Ponds;

use Illuminate\Foundation\Http\FormRequest;

class BulkDeletePondRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'is_all_selected' => 'required|boolean',
            'ids' => 'required_if:is_all_selected,false|array',
            'ids.*' => 'integer|exists:ponds,id',
            'exception_ids' => 'array',
            'exception_ids.*' => 'integer|exists:ponds,id',
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
            'is_all_selected.required' => 'Please specify whether all users are selected or not',
            'ids.required_if' => 'Please provide the IDs of users to delete when not selecting all',
            'exception_ids.required_if' => 'Please provide exception IDs when selecting all users',
        ];
    }
}
