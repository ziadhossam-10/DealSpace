<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class CalendarEvent extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'calendar_account_id',
        'person_id',
        'user_id',
        'tenant_id',
        'external_id', // Google/Outlook event ID
        'title',
        'description',
        'location',
        'start_time',
        'end_time',
        'timezone',
        'is_all_day',
        'status', // 'confirmed', 'tentative', 'cancelled'
        'visibility', // 'default', 'public', 'private'
        'attendees',
        'organizer_email',
        'meeting_link',
        'reminders',
        'recurrence',
        'sync_status', // 'synced', 'pending', 'failed'
        'sync_direction', // 'from_external', 'to_external', 'bidirectional'
        'last_synced_at',
        'external_updated_at',
        'sync_error',
        'crm_meeting_id', // Link to CRM meeting if applicable
        'syncable_type', // 'App\Models\Appointment', 'App\Models\Task', or null for standalone events
        'syncable_id', // ID of the linked appointment/task
        'event_type', // 'event', 'appointment', 'task'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_all_day' => 'boolean',
        'attendees' => 'array',
        'reminders' => 'array',
        'recurrence' => 'array',
        'last_synced_at' => 'datetime',
        'external_updated_at' => 'datetime'
    ];

    /**
     * Get the calendar account that owns this event.
     */
    public function calendarAccount(): BelongsTo
    {
        return $this->belongsTo(CalendarAccount::class);
    }

    /**
     * Get the person associated with this event.
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    /**
     * Get the user who created this event.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the syncable model (Appointment or Task).
     */
    public function syncable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope to filter by event type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('event_type', $type);
    }

    /**
     * Scope to filter standalone events (not linked to appointments/tasks).
     */
    public function scopeStandalone($query)
    {
        return $query->whereNull('syncable_type')->whereNull('syncable_id');
    }

    /**
     * Scope to filter linked events (appointments/tasks).
     */
    public function scopeLinked($query)
    {
        return $query->whereNotNull('syncable_type')->whereNotNull('syncable_id');
    }

    /**
     * Create a calendar event from an appointment.
     */
    public static function createFromAppointment(Appointment $appointment, CalendarAccount $calendarAccount): self
    {
        return self::create([
            'calendar_account_id' => $calendarAccount->id,
            'person_id' => $appointment->invitedPeople()->first()?->id,
            'user_id' => $appointment->created_by_id,
            'title' => $appointment->title,
            'description' => $appointment->description,
            'location' => $appointment->location,
            'start_time' => $appointment->start,
            'end_time' => $appointment->end,
            'is_all_day' => $appointment->all_day,
            'status' => 'confirmed',
            'attendees' => $appointment->getAllInvitees(),
            'syncable_type' => Appointment::class,
            'syncable_id' => $appointment->id,
            'event_type' => 'appointment',
            'sync_status' => 'pending',
            'sync_direction' => 'to_external',
        ]);
    }

    /**
     * Create a calendar event from a task.
     */
    public static function createFromTask(Task $task, CalendarAccount $calendarAccount): self
    {
        // For tasks, we create an event at the due time
        $startTime = $task->due_date_time ?? $task->due_date?->startOfDay();
        $endTime = $task->due_date_time ?? $task->due_date?->endOfDay();

        return self::create([
            'calendar_account_id' => $calendarAccount->id,
            'person_id' => $task->person_id,
            'user_id' => $task->assigned_user_id,
            'title' => $task->name,
            'description' => "Task: {$task->type}" . ($task->notes ? "\n\nNotes: {$task->notes}" : ''),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'is_all_day' => is_null($task->due_date_time),
            'status' => $task->is_completed ? 'confirmed' : 'tentative',
            'reminders' => $task->remind_seconds_before ? [
                ['method' => 'popup', 'minutes' => $task->remind_seconds_before / 60]
            ] : [],
            'syncable_type' => Task::class,
            'syncable_id' => $task->id,
            'event_type' => 'task',
            'sync_status' => 'pending',
            'sync_direction' => 'to_external',
        ]);
    }

    /**
     * Update calendar event from its linked appointment.
     */
    public function updateFromAppointment(): void
    {
        if ($this->syncable_type !== Appointment::class || !$this->syncable) {
            return;
        }

        $appointment = $this->syncable;

        $this->update([
            'title' => $appointment->title,
            'description' => $appointment->description,
            'location' => $appointment->location,
            'start_time' => $appointment->start,
            'end_time' => $appointment->end,
            'is_all_day' => $appointment->all_day,
            'attendees' => $appointment->getAllInvitees(),
            'sync_status' => 'pending',
        ]);
    }

    /**
     * Update calendar event from its linked task.
     */
    public function updateFromTask(): void
    {
        if ($this->syncable_type !== Task::class || !$this->syncable) {
            return;
        }

        $task = $this->syncable;
        $startTime = $task->due_date_time ?? $task->due_date?->startOfDay();
        $endTime = $task->due_date_time ?? $task->due_date?->endOfDay();

        $this->update([
            'title' => $task->name,
            'description' => "Task: {$task->type}" . ($task->notes ? "\n\nNotes: {$task->notes}" : ''),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'is_all_day' => is_null($task->due_date_time),
            'status' => $task->is_completed ? 'confirmed' : 'tentative',
            'reminders' => $task->remind_seconds_before ? [
                ['method' => 'popup', 'minutes' => $task->remind_seconds_before / 60]
            ] : [],
            'sync_status' => 'pending',
        ]);
    }

    /**
     * Check if this event needs to be synced.
     */
    public function needsSync(): bool
    {
        return $this->sync_status === 'pending' ||
            ($this->external_updated_at && (!$this->last_synced_at || $this->external_updated_at->gt($this->last_synced_at)));
    }

    /**
     * Mark event as synced.
     */
    public function markAsSynced(): void
    {
        $this->update([
            'sync_status' => 'synced',
            'last_synced_at' => now(),
            'sync_error' => null
        ]);
    }

    /**
     * Mark event as failed to sync.
     */
    public function markAsFailed(string $error): void
    {
        $this->update([
            'sync_status' => 'failed',
            'sync_error' => $error
        ]);
    }

    /**
     * Get formatted attendees for display.
     */
    public function getFormattedAttendeesAttribute(): array
    {
        if (!$this->attendees) {
            return [];
        }

        return collect($this->attendees)->map(function ($attendee) {
            return [
                'email' => $attendee['email'] ?? null,
                'name' => $attendee['name'] ?? $attendee['email'] ?? 'Unknown',
                'status' => $attendee['status'] ?? 'needsAction',
                'type' => $attendee['type'] ?? 'user'
            ];
        })->toArray();
    }

    /**
     * Get the source object (appointment, task, or self for standalone events).
     */
    public function getSourceAttribute()
    {
        return $this->syncable ?? $this;
    }

    /**
     * Get display title with prefix for type.
     */
    public function getDisplayTitleAttribute(): string
    {
        $prefix = match ($this->event_type) {
            'appointment' => '[Appointment] ',
            'task' => '[Task] ',
            default => ''
        };

        return $prefix . $this->title;
    }

    /**
     * Check if this is a linked event (appointment or task).
     */
    public function isLinked(): bool
    {
        return !is_null($this->syncable_type) && !is_null($this->syncable_id);
    }

    /**
     * Check if this is a standalone event.
     */
    public function isStandalone(): bool
    {
        return is_null($this->syncable_type) && is_null($this->syncable_id);
    }

    /**
     * Get color coding based on event type.
     */
    public function getColorAttribute(): string
    {
        return match ($this->event_type) {
            'appointment' => '#2563eb', // Blue
            'task' => '#dc2626', // Red
            default => '#059669' // Green
        };
    }

    /**
     * Boot method to handle model events.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($event) {
            // Set default event_type if not provided
            if (!$event->event_type) {
                $event->event_type = $event->syncable_type ?
                    (str_contains($event->syncable_type, 'Appointment') ? 'appointment' : 'task') :
                    'event';
            }
        });
    }
}