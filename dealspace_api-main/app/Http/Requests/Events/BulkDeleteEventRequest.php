<?php
// BulkDeleteEventRequest.php
namespace App\Http\Requests\Events;

use Illuminate\Foundation\Http\FormRequest;

class BulkDeleteEventRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'is_all_selected' => 'required|boolean',
            'exception_ids' => 'nullable|array',
            'exception_ids.*' => 'integer|exists:events,id',
            'ids' => 'nullable|array',
            'ids.*' => 'integer|exists:events,id',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'is_all_selected.required' => 'The selection type is required.',
            'is_all_selected.boolean' => 'The selection type must be true or false.',
            'exception_ids.array' => 'Exception IDs must be an array.',
            'exception_ids.*.integer' => 'Each exception ID must be an integer.',
            'exception_ids.*.exists' => 'One or more exception IDs do not exist.',
            'ids.array' => 'IDs must be an array.',
            'ids.*.integer' => 'Each ID must be an integer.',
            'ids.*.exists' => 'One or more IDs do not exist.',
        ];
    }
}
