<?php

namespace App\Http\Requests\Appointments;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAppointmentRequest extends FormRequest
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
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'start' => 'sometimes|date',
            'end' => 'sometimes|date|after:start',
            'all_day' => 'boolean',
            'location' => 'nullable|string|max:255',
            'type_id' => 'nullable|integer|exists:appointment_types,id',
            'outcome_id' => 'nullable|integer|exists:appointment_outcomes,id',
            'user_ids' => 'sometimes|array',
            'user_ids.*' => 'integer|exists:users,id',
            'person_ids' => 'sometimes|array',
            'person_ids.*' => 'integer|exists:people,id',
            'user_ids_to_delete' => 'sometimes|array',
            'user_ids_to_delete.*' => 'integer|exists:users,id',
            'person_ids_to_delete' => 'sometimes|array',
            'person_ids_to_delete.*' => 'integer|exists:people,id',
            'check_conflicts' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.max' => 'The appointment title may not be greater than 255 characters.',
            'start.date' => 'The start date must be a valid date.',
            'end.date' => 'The end date must be a valid date.',
            'end.after' => 'The end time must be after the start time.',
            'type_id.exists' => 'The selected appointment type does not exist.',
            'outcome_id.exists' => 'The selected appointment outcome does not exist.',
            'invitees.array' => 'The invitees must be an array.',
            'invitees.*.type.required_with' => 'Each invitee must have a type.',
            'invitees.*.type.in' => 'Each invitee type must be either user or person.',
            'invitees.*.id.required_with' => 'Each invitee must have an ID.',
            'invitees.*.id.integer' => 'Each invitee ID must be an integer.',
            'invitees.*.name.required_with' => 'Each invitee must have a name.',
            'invitees.*.name.max' => 'Each invitee name may not be greater than 255 characters.',
            'invitees.*.email.email' => 'Each invitee email must be a valid email address.',
            'description.max' => 'The description may not be greater than 1000 characters.',
            'location.max' => 'The location may not be greater than 255 characters.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Get the current appointment to check current values
            $appointmentId = $this->route('appointment') ?? $this->route('id');
            $currentAppointment = null;

            if ($appointmentId) {
                $currentAppointment = \App\Models\Appointment::find($appointmentId);
            }

            // Custom validation for all_day appointments
            if ($this->has('all_day') && $this->boolean('all_day')) {
                $start = $this->input('start') ?? ($currentAppointment ? $currentAppointment->start : null);
                $end = $this->input('end') ?? ($currentAppointment ? $currentAppointment->end : null);

                if ($start && $end) {
                    $startDate = date('Y-m-d', strtotime($start));
                    $endDate = date('Y-m-d', strtotime($end));

                    // For all-day appointments, start and end should be on the same day
                    // or end should be the next day at midnight
                    if ($startDate !== $endDate) {
                        $nextDay = date('Y-m-d', strtotime($startDate . ' +1 day'));
                        if ($endDate !== $nextDay || date('H:i:s', strtotime($end)) !== '00:00:00') {
                            $validator->errors()->add('end', 'For all-day appointments, end date should be the same day or next day at midnight.');
                        }
                    }
                }
            }

            // Validate that end time is after start time when both are provided
            if ($this->has('start') && $this->has('end')) {
                $start = $this->input('start');
                $end = $this->input('end');

                if ($start && $end && strtotime($end) <= strtotime($start)) {
                    $validator->errors()->add('end', 'The end time must be after the start time.');
                }
            }

            // Validate that end time is after start time when only one is provided
            if ($this->has('start') && !$this->has('end') && $currentAppointment) {
                $start = $this->input('start');
                $end = $currentAppointment->end;

                if ($start && $end && strtotime($end) <= strtotime($start)) {
                    $validator->errors()->add('start', 'The start time must be before the current end time.');
                }
            }

            if ($this->has('end') && !$this->has('start') && $currentAppointment) {
                $start = $currentAppointment->start;
                $end = $this->input('end');

                if ($start && $end && strtotime($end) <= strtotime($start)) {
                    $validator->errors()->add('end', 'The end time must be after the current start time.');
                }
            }

            // Validate invitees based on their type
            if ($this->has('invitees') && is_array($this->input('invitees'))) {
                foreach ($this->input('invitees') as $index => $invitee) {
                    if (isset($invitee['type']) && $invitee['type'] === 'user') {
                        // Validate user exists
                        if (isset($invitee['id'])) {
                            $user = \App\Models\User::find($invitee['id']);
                            if (!$user) {
                                $validator->errors()->add("invitees.{$index}.id", 'The selected user does not exist.');
                            }
                        }
                    } elseif (isset($invitee['type']) && $invitee['type'] === 'person') {
                        // Validate person exists (assuming you have a Person model)
                        if (isset($invitee['id'])) {
                            // Uncomment if you have a Person model
                            // $person = \App\Models\Person::find($invitee['id']);
                            // if (!$person) {
                            //     $validator->errors()->add("invitees.{$index}.id", 'The selected person does not exist.');
                            // }
                        }
                    }
                }
            }
        });
    }

    /**
     * Get the validated data from the request.
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        // Set default values
        if (!isset($validated['check_conflicts'])) {
            $validated['check_conflicts'] = false;
        }

        return $validated;
    }
}
