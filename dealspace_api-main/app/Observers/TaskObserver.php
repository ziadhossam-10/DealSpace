<?php

namespace App\Observers;

use App\Models\CalendarAccount;
use App\Models\CalendarEvent;
use App\Models\Task;
use Illuminate\Support\Facades\Log;

class TaskObserver
{
    /**
     * Handle the Task "created" event.
     */
    public function created(Task $task)
    {
        $this->createCalendarEvents($task);
    }

    /**
     * Handle the Task "updated" event.
     */
    public function updated(Task $task)
    {
        $this->updateCalendarEvents($task);
    }

    /**
     * Handle the Task "deleting" event.
     */
    public function deleting(Task $task)
    {
        // Get all associated calendar events
        $calendarEvents = CalendarEvent::where('syncable_type', Task::class)
            ->where('syncable_id', $task->id)
            ->get();

        // Delete each calendar event individually to trigger model events
        foreach ($calendarEvents as $event) {
            try {
                // This will trigger the CalendarEventObserver::deleting() method
                // which will handle deletion from external calendars
                $event->delete();
            } catch (\Exception $e) {
                Log::error('Failed to delete calendar event during task deletion', [
                    'task_id' => $task->id,
                    'event_id' => $event->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Create calendar events for the task
     */
    private function createCalendarEvents(Task $task)
    {
        // Only create calendar events for tasks with due dates
        if (!$task->due_date && !$task->due_date_time) {
            return;
        }

        // Get active calendar accounts for the assigned user
        $userId = $task->assigned_user_id ?? $task->created_by_id;
        $calendarAccounts = CalendarAccount::where('is_active', true)
            ->get();

        foreach ($calendarAccounts as $account) {
            try {
                $this->createCalendarEventFromTask($task, $account);
            } catch (\Exception $e) {
                Log::error('Failed to create calendar event from task', [
                    'task_id' => $task->id,
                    'account_id' => $account->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Update calendar events for the task
     */
    private function updateCalendarEvents(Task $task)
    {
        $calendarEvents = CalendarEvent::where('syncable_type', Task::class)
            ->where('syncable_id', $task->id)
            ->get();

        foreach ($calendarEvents as $event) {
            try {
                $this->updateCalendarEventFromTask($task, $event);
            } catch (\Exception $e) {
                Log::error('Failed to update calendar event from task', [
                    'task_id' => $task->id,
                    'event_id' => $event->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Create calendar event from task
     */
    private function createCalendarEventFromTask(Task $task, CalendarAccount $account)
    {
        // Determine start and end times
        $startTime = $task->due_date_time ?: $task->due_date;
        $endTime = $startTime ? $startTime->copy()->addHour() : now()->addHour();

        $eventData = [
            'calendar_account_id' => $account->id,
            'person_id' => $task->person_id,
            'user_id' => $task->assigned_user_id ?: $task->created_by_id,
            'tenant_id' => $task->tenant_id,
            'syncable_type' => Task::class,
            'syncable_id' => $task->id,
            'title' => $task->name ?: 'Task',
            'description' => $task->description,
            'location' => null,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'timezone' => config('app.timezone'),
            'is_all_day' => !$task->due_date_time && $task->due_date,
            'status' => $this->mapTaskStatus($task->status),
            'visibility' => $task->priority === 'high' ? 'public' : 'default',
            'attendees' => [],
            'organizer_email' => $task->createdBy->email ?? null,
            'meeting_link' => null,
            'reminders' => $this->getTaskReminders($task),
            'recurrence' => [],
            'sync_status' => 'pending',
            'sync_direction' => 'to_external',
            'event_type' => 'task'
        ];

        return CalendarEvent::create($eventData);
    }

    /**
     * Update calendar event from task
     */
    private function updateCalendarEventFromTask(Task $task, CalendarEvent $event)
    {
        // Determine start and end times
        $startTime = $task->due_date_time ?: $task->due_date;
        $endTime = $startTime ? $startTime->copy()->addHour() : now()->addHour();

        $event->update([
            'title' => $task->name ?: 'Task',
            'description' => $task->description,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'is_all_day' => !$task->due_date_time && $task->due_date,
            'status' => $this->mapTaskStatus($task->status),
            'visibility' => $task->priority === 'high' ? 'public' : 'default',
            'reminders' => $this->getTaskReminders($task),
            'sync_status' => 'pending',
            'sync_error' => null
        ]);
    }

    /**
     * Map task status to calendar event status
     */
    private function mapTaskStatus($status): string
    {
        $statusMap = [
            'pending' => 'tentative',
            'in_progress' => 'confirmed',
            'completed' => 'confirmed',
            'cancelled' => 'cancelled',
            'on_hold' => 'tentative'
        ];

        return $statusMap[$status] ?? 'tentative';
    }

    /**
     * Get reminders for task based on priority
     */
    private function getTaskReminders(Task $task): array
    {
        $reminders = [];

        switch ($task->priority) {
            case 'high':
                $reminders = [
                    ['method' => 'popup', 'minutes' => 60],
                    ['method' => 'popup', 'minutes' => 15],
                    ['method' => 'email', 'minutes' => 1440] // 24 hours
                ];
                break;
            case 'medium':
                $reminders = [
                    ['method' => 'popup', 'minutes' => 30],
                    ['method' => 'email', 'minutes' => 60]
                ];
                break;
            case 'low':
                $reminders = [
                    ['method' => 'popup', 'minutes' => 15]
                ];
                break;
            default:
                $reminders = [
                    ['method' => 'popup', 'minutes' => 15]
                ];
        }

        return $reminders;
    }
}