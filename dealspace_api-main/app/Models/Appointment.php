<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use Carbon\Carbon;

class Appointment extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'all_day',
        'start',
        'end',
        'location',
        'created_by_id',
        'type_id',
        'outcome_id',
    ];

    protected $casts = [
        'all_day' => 'boolean',
        'start' => 'datetime',
        'end' => 'datetime',
        'type_id' => 'integer',
        'outcome_id' => 'integer',
        'created_by_id' => 'integer',
    ];

    protected $dates = [
        'start',
        'end',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Get the user who created the appointment.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Get the appointment type.
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(AppointmentType::class, 'type_id');
    }

    /**
     * Get the appointment outcome.
     */
    public function outcome(): BelongsTo
    {
        return $this->belongsTo(AppointmentOutcome::class, 'outcome_id');
    }

    /**
     * Get the users invited to this appointment.
     */
    public function invitedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'appointment_user')
            ->withPivot(['response_status', 'responded_at'])
            ->withTimestamps();
    }

    /**
     * Get the people invited to this appointment.
     */
    public function invitedPeople(): BelongsToMany
    {
        return $this->belongsToMany(Person::class, 'appointment_person')
            ->withPivot(['response_status', 'responded_at'])
            ->withTimestamps();
    }

    /**
     * Get all invitees (users and people) with their response status.
     */
    public function getAllInvitees(): array
    {
        $users = $this->invitedUsers()->get()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'type' => 'user',
                'response_status' => $user->pivot->response_status,
                'responded_at' => $user->pivot->responded_at,
            ];
        });

        $people = $this->invitedPeople()->get()->map(function ($person) {
            return [
                'id' => $person->id,
                'name' => $person->name,
                'email' => $person->email ?? null,
                'type' => 'person',
                'response_status' => $person->pivot->response_status,
                'responded_at' => $person->pivot->responded_at,
            ];
        });

        return $users->concat($people)->toArray();
    }

    /**
     * Scope a query to only include appointments for today.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('start', Carbon::today());
    }

    /**
     * Scope a query to only include appointments for tomorrow.
     */
    public function scopeTomorrow($query)
    {
        return $query->whereDate('start', Carbon::tomorrow());
    }

    /**
     * Scope a query to only include upcoming appointments.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('start', '>', Carbon::now());
    }

    /**
     * Scope a query to only include past appointments.
     */
    public function scopePast($query)
    {
        return $query->where('end', '<', Carbon::now());
    }

    /**
     * Scope a query to only include current appointments.
     */
    public function scopeCurrent($query)
    {
        return $query->where('start', '<=', Carbon::now())
            ->where('end', '>=', Carbon::now());
    }

    /**
     * Scope a query to only include this week's appointments.
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('start', [
            Carbon::now()->startOfWeek(),
            Carbon::now()->endOfWeek()
        ]);
    }

    /**
     * Scope a query to only include next week's appointments.
     */
    public function scopeNextWeek($query)
    {
        return $query->whereBetween('start', [
            Carbon::now()->addWeek()->startOfWeek(),
            Carbon::now()->addWeek()->endOfWeek()
        ]);
    }

    /**
     * Scope a query to only include this month's appointments.
     */
    public function scopeThisMonth($query)
    {
        return $query->whereBetween('start', [
            Carbon::now()->startOfMonth(),
            Carbon::now()->endOfMonth()
        ]);
    }

    /**
     * Scope a query to only include all-day appointments.
     */
    public function scopeAllDay($query)
    {
        return $query->where('all_day', true);
    }

    /**
     * Scope a query to only include timed appointments.
     */
    public function scopeTimed($query)
    {
        return $query->where('all_day', false);
    }

    /**
     * Scope a query to filter by appointment type.
     */
    public function scopeOfType($query, int $typeId)
    {
        return $query->where('type_id', $typeId);
    }

    /**
     * Scope a query to filter by created user.
     */
    public function scopeCreatedBy($query, int $userId)
    {
        return $query->where('created_by_id', $userId);
    }

    /**
     * Scope a query to filter by outcome.
     */
    public function scopeWithOutcome($query, int $outcomeId)
    {
        return $query->where('outcome_id', $outcomeId);
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('start', [$startDate, $endDate])
                ->orWhereBetween('end', [$startDate, $endDate])
                ->orWhere(function ($subQ) use ($startDate, $endDate) {
                    $subQ->where('start', '<=', $startDate)
                        ->where('end', '>=', $endDate);
                });
        });
    }

    /**
     * Scope a query to filter appointments that have specific user invitees.
     */
    public function scopeWithUserInvitee($query, int $userId)
    {
        return $query->whereHas('invitedUsers', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        });
    }

    /**
     * Scope a query to filter appointments that have specific person invitees.
     */
    public function scopeWithPersonInvitee($query, int $personId)
    {
        return $query->whereHas('invitedPeople', function ($q) use ($personId) {
            $q->where('person_id', $personId);
        });
    }

    /**
     * Scope a query to filter appointments by invitee response status.
     */
    public function scopeWithInviteeResponseStatus($query, string $status, string $inviteeType = null, int $inviteeId = null)
    {
        if ($inviteeType === 'user' && $inviteeId) {
            return $query->whereHas('invitedUsers', function ($q) use ($inviteeId, $status) {
                $q->where('user_id', $inviteeId)
                    ->wherePivot('response_status', $status);
            });
        } elseif ($inviteeType === 'person' && $inviteeId) {
            return $query->whereHas('invitedPeople', function ($q) use ($inviteeId, $status) {
                $q->where('person_id', $inviteeId)
                    ->wherePivot('response_status', $status);
            });
        }

        // Filter by response status for any invitee type
        return $query->where(function ($q) use ($status) {
            $q->whereHas('invitedUsers', function ($subQ) use ($status) {
                $subQ->wherePivot('response_status', $status);
            })->orWhereHas('invitedPeople', function ($subQ) use ($status) {
                $subQ->wherePivot('response_status', $status);
            });
        });
    }

    /**
     * Check if the appointment is happening today.
     */
    public function isToday(): bool
    {
        return Carbon::parse($this->start)->isToday();
    }

    /**
     * Check if the appointment is happening tomorrow.
     */
    public function isTomorrow(): bool
    {
        return Carbon::parse($this->start)->isTomorrow();
    }

    /**
     * Check if the appointment is in the past.
     */
    public function isPast(): bool
    {
        return Carbon::parse($this->end)->isPast();
    }

    /**
     * Check if the appointment is upcoming.
     */
    public function isUpcoming(): bool
    {
        return Carbon::parse($this->start)->isFuture();
    }

    /**
     * Check if the appointment is currently happening.
     */
    public function isCurrent(): bool
    {
        $now = Carbon::now();
        return Carbon::parse($this->start)->lessThanOrEqualTo($now)
            && Carbon::parse($this->end)->greaterThanOrEqualTo($now);
    }

    /**
     * Check if the appointment is this week.
     */
    public function isThisWeek(): bool
    {
        return Carbon::parse($this->start)->isCurrentWeek();
    }

    /**
     * Check if the appointment is next week.
     */
    public function isNextWeek(): bool
    {
        return Carbon::parse($this->start)->isNextWeek();
    }

    /**
     * Check if the appointment is this month.
     */
    public function isThisMonth(): bool
    {
        return Carbon::parse($this->start)->isCurrentMonth();
    }

    /**
     * Get the duration of the appointment in minutes.
     */
    public function getDurationInMinutes(): int
    {
        return Carbon::parse($this->start)->diffInMinutes(Carbon::parse($this->end));
    }

    /**
     * Get the duration of the appointment in hours.
     */
    public function getDurationInHours(): float
    {
        return Carbon::parse($this->start)->diffInHours(Carbon::parse($this->end), true);
    }

    /**
     * Get the formatted start date.
     */
    public function getFormattedStartAttribute(): string
    {
        if ($this->all_day) {
            return Carbon::parse($this->start)->format('M j, Y');
        }

        return Carbon::parse($this->start)->format('M j, Y g:i A');
    }

    /**
     * Get the formatted end date.
     */
    public function getFormattedEndAttribute(): string
    {
        if ($this->all_day) {
            return Carbon::parse($this->end)->format('M j, Y');
        }

        return Carbon::parse($this->end)->format('M j, Y g:i A');
    }

    /**
     * Get the formatted date range.
     */
    public function getFormattedDateRangeAttribute(): string
    {
        $start = Carbon::parse($this->start);
        $end = Carbon::parse($this->end);

        if ($this->all_day) {
            if ($start->isSameDay($end)) {
                return $start->format('M j, Y');
            }
            return $start->format('M j, Y') . ' - ' . $end->format('M j, Y');
        }

        if ($start->isSameDay($end)) {
            return $start->format('M j, Y g:i A') . ' - ' . $end->format('g:i A');
        }

        return $start->format('M j, Y g:i A') . ' - ' . $end->format('M j, Y g:i A');
    }

    /**
     * Get the status of the appointment.
     */
    public function getStatusAttribute(): string
    {
        if ($this->isCurrent()) {
            return 'In Progress';
        }

        if ($this->isPast()) {
            return 'Completed';
        }

        if ($this->isToday()) {
            return 'Today';
        }

        if ($this->isTomorrow()) {
            return 'Tomorrow';
        }

        if ($this->isUpcoming()) {
            return 'Upcoming';
        }

        return 'Scheduled';
    }

    /**
     * Get user invitees.
     */
    public function getUserInvitees(): array
    {
        return $this->invitedUsers()->get()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'response_status' => $user->pivot->response_status,
                'responded_at' => $user->pivot->responded_at,
            ];
        })->toArray();
    }

    /**
     * Get person invitees.
     */
    public function getPersonInvitees(): array
    {
        return $this->invitedPeople()->get()->map(function ($person) {
            return [
                'id' => $person->id,
                'name' => $person->name,
                'email' => $person->email ?? null,
                'response_status' => $person->pivot->response_status,
                'responded_at' => $person->pivot->responded_at,
            ];
        })->toArray();
    }

    /**
     * Get all invitee names.
     */
    public function getInviteeNames(): array
    {
        $userNames = $this->invitedUsers()->pluck('name')->toArray();
        $personNames = $this->invitedPeople()->pluck('name')->toArray();

        return array_merge($userNames, $personNames);
    }

    /**
     * Check if a user is invited to this appointment.
     */
    public function hasUserInvitee(int $userId): bool
    {
        return $this->invitedUsers()->where('user_id', $userId)->exists();
    }

    /**
     * Check if a person is invited to this appointment.
     */
    public function hasPersonInvitee(int $personId): bool
    {
        return $this->invitedPeople()->where('person_id', $personId)->exists();
    }

    /**
     * Invite users to the appointment.
     */
    public function inviteUsers(array $userIds): void
    {
        $this->invitedUsers()->syncWithoutDetaching($userIds);
    }

    /**
     * Invite people to the appointment.
     */
    public function invitePeople(array $personIds): void
    {
        $this->invitedPeople()->syncWithoutDetaching($personIds);
    }

    /**
     * Remove user invitees.
     */
    public function removeUserInvitees(array $userIds): void
    {
        $this->invitedUsers()->detach($userIds);
    }

    /**
     * Remove person invitees.
     */
    public function removePersonInvitees(array $personIds): void
    {
        $this->invitedPeople()->detach($personIds);
    }

    /**
     * Update user response status.
     */
    public function updateUserResponseStatus(int $userId, string $status): void
    {
        $this->invitedUsers()->updateExistingPivot($userId, [
            'response_status' => $status,
            'responded_at' => Carbon::now(),
        ]);
    }

    /**
     * Update person response status.
     */
    public function updatePersonResponseStatus(int $personId, string $status): void
    {
        $this->invitedPeople()->updateExistingPivot($personId, [
            'response_status' => $status,
            'responded_at' => Carbon::now(),
        ]);
    }

    /**
     * Get all calendar events for this appointment.
     */
    public function calendarEvents()
    {
        return $this->morphMany(CalendarEvent::class, 'syncable');
    }

    /**
     * Create calendar events for this appointment across all active calendar accounts.
     */
    public function syncToCalendars(): void
    {
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
                $existingEvent->updateFromAppointment();
            } else {
                CalendarEvent::createFromAppointment($this, $account);
            }
        }
    }

    /**
     * Remove calendar events for this appointment.
     */
    public function unsyncFromCalendars(): void
    {
        $this->calendarEvents()->delete();
    }
}