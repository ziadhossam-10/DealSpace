<?php

namespace App\Http\Requests\Notifications;

use Illuminate\Foundation\Http\FormRequest;

class BulkDeleteNotificationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'is_all_selected' => 'required|boolean',
            'ids' => 'required_if:is_all_selected,false|array',
            'ids.*' => 'integer|exists:notifications,id',
            'exception_ids' => 'array',
            'exception_ids.*' => 'integer|exists:notifications,id',
        ];
    }
}
