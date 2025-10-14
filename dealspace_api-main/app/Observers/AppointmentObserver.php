<?php

namespace App\Observers;

use App\Models\Appointment;
use App\Models\CalendarAccount;
use App\Models\CalendarEvent;
use Illuminate\Support\Facades\Log;

class AppointmentObserver
{
    /**
     * Handle the Appointment "created" event.
     */
    public function created(Appointment $appointment)
    {
        $this->createCalendarEvents($appointment);
    }

    /**
     * Handle the Appointment "updated" event.
     */
    public function updated(Appointment $appointment)
    {
        $this->updateCalendarEvents($appointment);
    }

    /**
     * Handle the Appointment "deleting" event.
     */
    public function deleting(Appointment $appointment)
    {
        // Get all associated calendar events
        $calendarEvents = CalendarEvent::where('syncable_type', Appointment::class)
            ->where('syncable_id', $appointment->id)
            ->get();

        // Delete each calendar event individually to trigger model events
        foreach ($calendarEvents as $event) {
            try {
                // This will trigger the CalendarEventObserver::deleting() method
                // which will handle deletion from external calendars
                $event->delete();
            } catch (\Exception $e) {
                Log::error('Failed to delete calendar event during task deletion', [
                    'task_id' => $appointment->id,
                    'event_id' => $event->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Create calendar events for the appointment
     */
    private function createCalendarEvents(Appointment $appointment)
    {
        // Get active calendar accounts for the user
        $calendarAccounts = CalendarAccount::where('is_active', true)->get();

        foreach ($calendarAccounts as $account) {
            try {
                $this->createCalendarEventFromAppointment($appointment, $account);
            } catch (\Exception $e) {
                Log::error('Failed to create calendar event from appointment', [
                    'appointment_id' => $appointment->id,
                    'account_id' => $account->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Update calendar events for the appointment
     */
    private function updateCalendarEvents(Appointment $appointment)
    {
        $calendarEvents = CalendarEvent::where('syncable_type', Appointment::class)
            ->where('syncable_id', $appointment->id)
            ->get();

        foreach ($calendarEvents as $event) {
            try {
                $this->updateCalendarEventFromAppointment($appointment, $event);
            } catch (\Exception $e) {
                Log::error('Failed to update calendar event from appointment', [
                    'appointment_id' => $appointment->id,
                    'event_id' => $event->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Create calendar event from appointment
     */
    private function createCalendarEventFromAppointment(Appointment $appointment, CalendarAccount $account)
    {
        $attendees = [];

        // Add appointment participants as attendees
        if ($appointment->participants) {
            foreach ($appointment->participants as $participant) {
                $attendees[] = [
                    'email' => $participant['email'] ?? '',
                    'name' => $participant['name'] ?? '',
                    'status' => 'needsAction'
                ];
            }
        }

        // Add the person/contact as attendee if available
        if ($appointment->person && $appointment->person->email) {
            $attendees[] = [
                'email' => $appointment->person->email,
                'name' => $appointment->person->full_name,
                'status' => 'needsAction'
            ];
        }

        $eventData = [
            'calendar_account_id' => $account->id,
            'person_id' => $appointment->person_id,
            'user_id' => $appointment->created_by_id,
            'tenant_id' => $appointment->tenant_id,
            'syncable_type' => Appointment::class,
            'syncable_id' => $appointment->id,
            'title' => $appointment->title ?: 'Appointment',
            'description' => $appointment->description,
            'location' => $appointment->location,
            'start_time' => $appointment->start,
            'end_time' => $appointment->end,
            'timezone' => $appointment->timezone ?: config('app.timezone'),
            'is_all_day' => $appointment->all_day ?: false,
            'status' => $this->mapAppointmentStatus($appointment->status),
            'visibility' => 'default',
            'attendees' => $attendees,
            'organizer_email' => $appointment->createdBy->email ?? null,
            'meeting_link' => $appointment->meeting_link,
            'reminders' => $this->getDefaultReminders(),
            'recurrence' => [],
            'sync_status' => 'pending',
            'sync_direction' => 'to_external',
            'event_type' => 'appointment'
        ];

        return CalendarEvent::create($eventData);
    }

    /**
     * Update calendar event from appointment
     */
    private function updateCalendarEventFromAppointment(Appointment $appointment, CalendarEvent $event)
    {
        $attendees = [];

        // Add appointment participants as attendees
        if ($appointment->participants) {
            foreach ($appointment->participants as $participant) {
                $attendees[] = [
                    'email' => $participant['email'] ?? '',
                    'name' => $participant['name'] ?? '',
                    'status' => 'needsAction'
                ];
            }
        }

        // Add the person/contact as attendee if available
        if ($appointment->person && $appointment->person->email) {
            $attendees[] = [
                'email' => $appointment->person->email,
                'name' => $appointment->person->full_name,
                'status' => 'needsAction'
            ];
        }

        $event->update([
            'title' => $appointment->title ?: 'Appointment',
            'description' => $appointment->description,
            'location' => $appointment->location,
            'start_time' => $appointment->start,
            'end_time' => $appointment->end,
            'timezone' => $appointment->timezone ?: config('app.timezone'),
            'is_all_day' => $appointment->all_day ?: false,
            'status' => $this->mapAppointmentStatus($appointment->status),
            'attendees' => $attendees,
            'meeting_link' => $appointment->meeting_link,
            'sync_status' => 'pending',
            'sync_error' => null
        ]);
    }

    /**
     * Map appointment status to calendar event status
     */
    private function mapAppointmentStatus($status): string
    {
        $statusMap = [
            'scheduled' => 'confirmed',
            'confirmed' => 'confirmed',
            'cancelled' => 'cancelled',
            'completed' => 'confirmed',
            'no_show' => 'cancelled'
        ];

        return $statusMap[$status] ?? 'tentative';
    }

    /**
     * Get default reminders for appointments
     */
    private function getDefaultReminders(): array
    {
        return [
            [
                'method' => 'popup',
                'minutes' => 15
            ],
            [
                'method' => 'email',
                'minutes' => 60
            ]
        ];
    }
}