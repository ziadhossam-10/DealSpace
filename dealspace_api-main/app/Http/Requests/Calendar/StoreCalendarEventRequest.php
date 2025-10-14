<?php

namespace App\Http\Requests\Calendar;

use Illuminate\Foundation\Http\FormRequest;

class StoreCalendarEventRequest extends FormRequest
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
            'calendar_account_id' => 'required|integer|exists:calendar_accounts,id',
            'person_id' => 'nullable|integer|exists:people,id',
            'user_id' => 'nullable|integer|exists:users,id',
            'tenant_id' => 'nullable|integer',
            'external_id' => 'nullable|string|max:255',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:500',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'timezone' => 'nullable|string|max:100',
            'is_all_day' => 'boolean',
            'status' => 'required|in:confirmed,tentative,cancelled',
            'visibility' => 'required|in:default,public,private',
            'attendees' => 'nullable|array',
            'attendees.*.email' => 'required_with:attendees|email',
            'attendees.*.name' => 'nullable|string|max:255',
            'attendees.*.status' => 'nullable|in:needsAction,declined,tentative,accepted',
            'attendees.*.type' => 'nullable|in:user,group,resource',
            'organizer_email' => 'nullable|email|max:255',
            'meeting_link' => 'nullable|url|max:500',
            'reminders' => 'nullable|array',
            'reminders.*.method' => 'required_with:reminders|in:popup,email',
            'reminders.*.minutes' => 'required_with:reminders|integer|min:0',
            'recurrence' => 'nullable|array',
            'sync_status' => 'nullable|in:synced,pending,failed',
            'sync_direction' => 'nullable|in:from_external,to_external,bidirectional',
            'last_synced_at' => 'nullable|date',
            'external_updated_at' => 'nullable|date',
            'sync_error' => 'nullable|string',
            'crm_meeting_id' => 'nullable|integer',
            'syncable_type' => 'nullable|string|in:App\Models\Appointment,App\Models\Task',
            'syncable_id' => 'nullable|integer',
            'event_type' => 'required|in:event,appointment,task',
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
            'calendar_account_id.required' => 'Calendar account is required.',
            'calendar_account_id.exists' => 'The selected calendar account does not exist.',
            'title.required' => 'Event title is required.',
            'start_time.required' => 'Event start time is required.',
            'end_time.required' => 'Event end time is required.',
            'end_time.after' => 'Event end time must be after start time.',
            'status.in' => 'Event status must be one of: confirmed, tentative, cancelled.',
            'visibility.in' => 'Event visibility must be one of: default, public, private.',
            'event_type.in' => 'Event type must be one of: event, appointment, task.',
            'attendees.*.email.email' => 'Each attendee must have a valid email address.',
            'meeting_link.url' => 'Meeting link must be a valid URL.',
        ];
    }
}