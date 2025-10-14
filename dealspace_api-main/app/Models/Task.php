<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use Carbon\Carbon;

class Task extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'person_id',
        'assigned_user_id',
        'name',
        'type',
        'is_completed',
        'due_date',
        'due_date_time',
        'remind_seconds_before',
        'notes'
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'due_date' => 'date',
        'due_date_time' => 'datetime',
        'remind_seconds_before' => 'integer',
    ];

    protected $dates = [
        'due_date',
        'due_date_time',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * The valid task types.
     */
    public const TASK_TYPES = [
        'Follow Up',
        'Call',
        'Text',
        'Email',
        'Appointment',
        'Showing',
        'Closing',
        'Open House',
        'Thank You',
    ];

    /**
     * Get the person that owns the task.
     */
    public function person()
    {
        return $this->belongsTo(Person::class);
    }

    /**
     * Get the user assigned to the task.
     */
    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    /**
     * Scope a query to only include completed tasks.
     */
    public function scopeCompleted($query)
    {
        return $query->where('is_completed', true);
    }

    /**
     * Scope a query to only include pending tasks.
     */
    public function scopePending($query)
    {
        return $query->where('is_completed', false);
    }

    /**
     * Scope a query to only include overdue tasks.
     */
    public function scopeOverdue($query)
    {
        return $query->where('is_completed', false)
            ->where(function ($q) {
                $q->where('due_date_time', '<', Carbon::now())
                    ->orWhere('due_date', '<', Carbon::now()->toDateString());
            });
    }

    /**
     * Scope a query to only include tasks due today.
     */
    public function scopeDueToday($query)
    {
        return $query->where('is_completed', false)
            ->whereDate('due_date', Carbon::today());
    }

    /**
     * Scope a query to only include tasks due soon (within 24 hours).
     */
    public function scopeDueSoon($query)
    {
        return $query->where('is_completed', false)
            ->whereNotNull('due_date_time')
            ->whereBetween('due_date_time', [
                Carbon::now(),
                Carbon::now()->addHours(24)
            ]);
    }

    /**
     * Scope a query to only include future tasks.
     */
    public function scopeFuture($query)
    {
        return $query->where('is_completed', false)
            ->where(function ($q) {
                $q->where(function ($subQuery) {
                    // Tasks with due_date_time that are in the future
                    $subQuery->whereNotNull('due_date_time')
                        ->where('due_date_time', '>', Carbon::now());
                })->orWhere(function ($subQuery) {
                    // Tasks with only due_date that are in the future (and no due_date_time)
                    $subQuery->whereNull('due_date_time')
                        ->whereNotNull('due_date')
                        ->where('due_date', '>', Carbon::today());
                });
            });
    }

    /**
     * Scope a query to filter by task type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to filter by assigned user.
     */
    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assigned_user_id', $userId);
    }

    /**
     * Scope a query to filter by person.
     */
    public function scopeForPerson($query, int $personId)
    {
        return $query->where('person_id', $personId);
    }

    /**
     * Check if the task is overdue.
     */
    public function isOverdue(): bool
    {
        if ($this->is_completed) {
            return false;
        }

        if ($this->due_date_time) {
            return Carbon::parse($this->due_date_time)->isPast();
        }

        if ($this->due_date) {
            return Carbon::parse($this->due_date)->isPast();
        }

        return false;
    }

    /**
     * Check if the task is due today.
     */
    public function isDueToday(): bool
    {
        if ($this->is_completed) {
            return false;
        }

        if ($this->due_date_time) {
            return Carbon::parse($this->due_date_time)->isToday();
        }

        if ($this->due_date) {
            return Carbon::parse($this->due_date)->isToday();
        }

        return false;
    }

    /**
     * Check if the task is due soon (within 24 hours).
     */
    public function isDueSoon(): bool
    {
        if ($this->is_completed || !$this->due_date_time) {
            return false;
        }

        $dueTime = Carbon::parse($this->due_date_time);
        return $dueTime->isFuture() && $dueTime->lessThanOrEqualTo(Carbon::now()->addHours(24));
    }

    /**
     * Check if the task is a future task.
     */
    public function isFuture(): bool
    {
        if ($this->is_completed) {
            return false;
        }

        if ($this->due_date_time) {
            return Carbon::parse($this->due_date_time)->isFuture();
        }

        if ($this->due_date) {
            return Carbon::parse($this->due_date)->isFuture();
        }

        return false;
    }

    /**
     * Get the reminder time for the task.
     */
    public function getReminderTime(): ?Carbon
    {
        if (!$this->due_date_time || !$this->remind_seconds_before) {
            return null;
        }

        return Carbon::parse($this->due_date_time)->subSeconds($this->remind_seconds_before);
    }

    /**
     * Check if the task needs a reminder now.
     */
    public function needsReminderNow(): bool
    {
        $reminderTime = $this->getReminderTime();

        if (!$reminderTime || $this->is_completed) {
            return false;
        }

        return Carbon::now()->greaterThanOrEqualTo($reminderTime);
    }

    /**
     * Get the formatted due date.
     */
    public function getFormattedDueDateAttribute(): ?string
    {
        if ($this->due_date_time) {
            return Carbon::parse($this->due_date_time)->format('M j, Y g:i A');
        }

        if ($this->due_date) {
            return Carbon::parse($this->due_date)->format('M j, Y');
        }

        return null;
    }

    /**
     * Get the status of the task.
     */
    public function getStatusAttribute(): string
    {
        if ($this->is_completed) {
            return 'Completed';
        }

        if ($this->isOverdue()) {
            return 'Overdue';
        }

        if ($this->isDueToday()) {
            return 'Due Today';
        }

        if ($this->isDueSoon()) {
            return 'Due Soon';
        }

        return 'Pending';
    }

    /**
     * Get the priority level based on due date.
     */
    public function getPriorityAttribute(): string
    {
        if ($this->is_completed) {
            return 'None';
        }

        if ($this->isOverdue()) {
            return 'High';
        }

        if ($this->isDueToday()) {
            return 'High';
        }

        if ($this->isDueSoon()) {
            return 'Medium';
        }

        return 'Low';
    }

    /**
     * Get all calendar events for this task.
     */
    public function calendarEvents()
    {
        return $this->morphMany(CalendarEvent::class, 'syncable');
    }

    /**
     * Create calendar events for this task across all active calendar accounts.
     */
    public function syncToCalendars(): void
    {
        // Only sync tasks with due dates
        if (!$this->due_date && !$this->due_date_time) {
            return;
        }

        // Get all active calendar accounts for the tenant
        $calendarAccounts = CalendarAccount::where('tenant_id', $this->tenant_id)
            ->where('is_active', true)
            ->get();

        foreach ($calendarAccounts as $account) {
            // Check if event already exists
            $existingEvent = $this->calendarEvents()
                ->where('calendar_account_id', $account->id)
                ->first();

            if ($existingEvent) {
                $existingEvent->updateFromTask();
            } else {
                CalendarEvent::createFromTask($this, $account);
            }
        }
    }

    /**
     * Remove calendar events for this task.
     */
    public function unsyncFromCalendars(): void
    {
        $this->calendarEvents()->delete();
    }

    /**
     * Check if this task should be synced to calendar.
     */
    public function shouldSyncToCalendar(): bool
    {
        return !is_null($this->due_date) || !is_null($this->due_date_time);
    }
}