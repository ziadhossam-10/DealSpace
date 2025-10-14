<?php

namespace App\Http\Requests\Tasks;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Task;

class UpdateTaskRequest extends FormRequest
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
            'person_id' => 'nullable|integer|exists:people,id',
            'assigned_user_id' => 'nullable|integer|exists:users,id',
            'name' => 'nullable|string|max:255',
            'type' => 'nullable|string|in:' . implode(',', Task::TASK_TYPES),
            'is_completed' => 'nullable|boolean',
            'due_date' => 'nullable|date_format:Y-m-d',
            'due_date_time' => 'nullable|date',
            'remind_seconds_before' => 'nullable|integer|min:0|max:86400', // Max 24 hours
            'notes' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'person_id.integer' => 'Person ID must be a valid integer.',
            'person_id.exists' => 'The selected person does not exist.',
            'assigned_user_id.integer' => 'Assigned user ID must be a valid integer.',
            'assigned_user_id.exists' => 'The selected assigned user does not exist.',
            'name.string' => 'Task name must be a valid string.',
            'name.max' => 'Task name cannot exceed 255 characters.',
            'type.string' => 'Task type must be a valid string.',
            'type.in' => 'Task type must be one of: ' . implode(', ', Task::TASK_TYPES),
            'is_completed.boolean' => 'Is completed must be a boolean value.',
            'due_date.date_format' => 'Due date must be in YYYY-MM-DD format.',
            'due_date_time.date' => 'Due date time must be a valid date.',
            'remind_seconds_before.integer' => 'Reminder seconds must be a valid integer.',
            'remind_seconds_before.min' => 'Reminder seconds must be at least 0.',
            'remind_seconds_before.max' => 'Reminder seconds cannot exceed 86400 (24 hours).',

        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // If remind_seconds_before is set, due_date_time must be provided
            if ($this->filled('remind_seconds_before') && !$this->filled('due_date_time')) {
                $validator->errors()->add('due_date_time', 'Due date time is required when setting a reminder.');
            }
        });
    }
}
