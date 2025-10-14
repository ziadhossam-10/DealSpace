<?php

namespace App\Http\Requests\Appointments;

use Illuminate\Foundation\Http\FormRequest;

class GetAppointmentsRequest extends FormRequest
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
            'per_page' => 'sometimes|integer|min:1|max:100',
            'page' => 'sometimes|integer|min:1',
            'created_by_id' => 'sometimes|integer|exists:users,id',
            'type_id' => 'sometimes|integer|exists:appointment_types,id',
            'outcome_id' => 'sometimes|integer|exists:appointment_outcomes,id',
            'all_day' => 'sometimes|boolean',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'start' => 'sometimes|date',
            'end' => 'sometimes|date|after:start',
            'exclude_id' => 'sometimes|integer|exists:appointments,id',
            'limit' => 'sometimes|integer|min:1|max:100',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'created_by_id.exists' => 'The selected user does not exist.',
            'type_id.exists' => 'The selected appointment type does not exist.',
            'outcome_id.exists' => 'The selected appointment outcome does not exist.',
            'end_date.after_or_equal' => 'The end date must be after or equal to the start date.',
            'end.after' => 'The end time must be after the start time.',
            'exclude_id.exists' => 'The appointment to exclude does not exist.',
            'per_page.max' => 'The per page value may not be greater than 100.',
            'limit.max' => 'The limit value may not be greater than 100.',
        ];
    }
}
