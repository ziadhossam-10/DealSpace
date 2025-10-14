<?php

namespace App\Repositories\Appointments;

use App\Models\Appointment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AppointmentsRepository implements AppointmentsRepositoryInterface
{
    protected $model;

    public function __construct(Appointment $model)
    {
        $this->model = $model;
    }

    /**
     * Get all appointments with optional filtering.
     *
     * @param int $perPage Number of items per page
     * @param int $page Current page number
     * @param int|null $createdById Optional created by user ID filter
     * @param int|null $typeId Optional appointment type ID filter
     * @param int|null $outcomeId Optional appointment outcome ID filter
     * @param bool|null $allDay Optional all-day filter
     * @param string|null $startDate Optional start date filter
     * @param string|null $endDate Optional end date filter
     * @return LengthAwarePaginator Paginated appointment records
     */
    public function getAllWithOptionalFilters(
        int $perPage = 15,
        int $page = 1,
        ?int $createdById = null,
        ?int $typeId = null,
        ?int $outcomeId = null,
        ?bool $allDay = null,
        ?string $startDate = null,
        ?string $endDate = null,
        ?int $personId = null
    ): LengthAwarePaginator {
        $query = $this->model->with(['createdBy', 'type', 'outcome'])
            ->orderBy('start', 'asc');

        if ($createdById) {
            $query->where('created_by_id', $createdById);
        }

        if ($typeId) {
            $query->where('type_id', $typeId);
        }

        if ($outcomeId) {
            $query->where('outcome_id', $outcomeId);
        }

        if ($allDay !== null) {
            $query->where('all_day', $allDay);
        }

        if ($startDate && $endDate) {
            $query->betweenDates($startDate, $endDate);
        }

        if ($personId) {
            $query->whereHas('invitedPeople', function ($q) use ($personId) {
                $q->where('person_id', $personId);
            });
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Find an appointment by its ID.
     *
     * @param int $appointmentId The ID of the appointment to find.
     * @return Appointment|null The found Appointment instance or null if not found.
     */
    public function findById(int $appointmentId): ?Appointment
    {
        return $this->model->with(['createdBy', 'type', 'outcome'])->find($appointmentId);
    }

    /**
     * Create a new appointment record.
     *
     * @param array $data The data for the new appointment. Example fields:
     * - 'title' (string): Required. The title of the appointment.
     * - 'description' (string): Optional. Description of the appointment.
     * - 'invitees' (array): Optional. Array of users and/or people to invite.
     * - 'all_day' (bool): Optional. Whether the appointment is all day (defaults to false).
     * - 'start' (string): Required. The beginning date and time of the appointment.
     * - 'end' (string): Required. The ending date and time of the appointment.
     * - 'location' (string): Optional. The location or address of the appointment.
     * - 'created_by_id' (int): Optional. The id of the user that created the appointment.
     * - 'type_id' (int): Optional. The appointment type identifier.
     * - 'outcome_id' (int): Optional. The appointment outcome identifier.
     * @return Appointment The newly created Appointment model instance.
     */
    public function create(array $data): Appointment
    {
        return $this->model->create($data)->fresh(['createdBy', 'type', 'outcome']);
    }

    /**
     * Update an existing appointment record.
     *
     * @param int $appointmentId The ID of the appointment to update
     * @param array $data The data to update
     * @return Appointment The updated Appointment model instance.
     */
    public function update(int $appointmentId, array $data): Appointment
    {
        $appointment = $this->model->findOrFail($appointmentId);
        $appointment->update($data);
        return $appointment->fresh(['createdBy', 'type', 'outcome']);
    }

    /**
     * Delete an appointment record.
     *
     * @param int $appointmentId The ID of the appointment to delete
     * @return bool True if deletion was successful
     */
    public function delete(int $appointmentId): bool
    {
        $appointment = $this->model->findOrFail($appointmentId);
        return $appointment->delete();
    }

    /**
     * Get appointments for a specific user (created by).
     *
     * @param int $userId The user ID
     * @param int $perPage Number of items per page
     * @param int $page Current page number
     * @return LengthAwarePaginator Paginated appointment records
     */
    public function getAppointmentsForUser(int $userId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->model->with(['createdBy', 'type', 'outcome'])
            ->where('created_by_id', $userId)
            ->orderBy('start', 'asc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get appointments for today.
     *
     * @param int|null $createdById Optional user ID filter
     * @return array Collection of appointments for today
     */
    public function getTodayAppointments(?int $createdById = null): array
    {
        $query = $this->model->with(['createdBy', 'type', 'outcome'])
            ->today()
            ->orderBy('start', 'asc');

        if ($createdById) {
            $query->where('created_by_id', $createdById);
        }

        return $query->get()->toArray();
    }

    /**
     * Get appointments for tomorrow.
     *
     * @param int|null $createdById Optional user ID filter
     * @return array Collection of appointments for tomorrow
     */
    public function getTomorrowAppointments(?int $createdById = null): array
    {
        $query = $this->model->with(['createdBy', 'type', 'outcome'])
            ->tomorrow()
            ->orderBy('start', 'asc');

        if ($createdById) {
            $query->where('created_by_id', $createdById);
        }

        return $query->get()->toArray();
    }

    /**
     * Get upcoming appointments.
     *
     * @param int|null $createdById Optional user ID filter
     * @param int $limit Optional limit for results
     * @return array Collection of upcoming appointments
     */
    public function getUpcomingAppointments(?int $createdById = null, int $limit = 50): array
    {
        $query = $this->model->with(['createdBy', 'type', 'outcome'])
            ->upcoming()
            ->orderBy('start', 'asc');

        if ($createdById) {
            $query->where('created_by_id', $createdById);
        }

        return $query->limit($limit)->get()->toArray();
    }

    /**
     * Get past appointments.
     *
     * @param int|null $createdById Optional user ID filter
     * @param int $perPage Number of items per page
     * @param int $page Current page number
     * @return LengthAwarePaginator Paginated past appointments
     */
    public function getPastAppointments(?int $createdById = null, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        $query = $this->model->with(['createdBy', 'type', 'outcome'])
            ->past()
            ->orderBy('start', 'desc');

        if ($createdById) {
            $query->where('created_by_id', $createdById);
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get current appointments (happening now).
     *
     * @param int|null $createdById Optional user ID filter
     * @return array Collection of current appointments
     */
    public function getCurrentAppointments(?int $createdById = null): array
    {
        $query = $this->model->with(['createdBy', 'type', 'outcome'])
            ->current()
            ->orderBy('start', 'asc');

        if ($createdById) {
            $query->where('created_by_id', $createdById);
        }

        return $query->get()->toArray();
    }

    /**
     * Get appointments for this week.
     *
     * @param int|null $createdById Optional user ID filter
     * @return array Collection of appointments for this week
     */
    public function getThisWeekAppointments(?int $createdById = null): array
    {
        $query = $this->model->with(['createdBy', 'type', 'outcome'])
            ->thisWeek()
            ->orderBy('start', 'asc');

        if ($createdById) {
            $query->where('created_by_id', $createdById);
        }

        return $query->get()->toArray();
    }

    /**
     * Get appointments for next week.
     *
     * @param int|null $createdById Optional user ID filter
     * @return array Collection of appointments for next week
     */
    public function getNextWeekAppointments(?int $createdById = null): array
    {
        $query = $this->model->with(['createdBy', 'type', 'outcome'])
            ->nextWeek()
            ->orderBy('start', 'asc');

        if ($createdById) {
            $query->where('created_by_id', $createdById);
        }

        return $query->get()->toArray();
    }

    /**
     * Get appointments for this month.
     *
     * @param int|null $createdById Optional user ID filter
     * @return array Collection of appointments for this month
     */
    public function getThisMonthAppointments(?int $createdById = null): array
    {
        $query = $this->model->with(['createdBy', 'type', 'outcome'])
            ->thisMonth()
            ->orderBy('start', 'asc');

        if ($createdById) {
            $query->where('created_by_id', $createdById);
        }

        return $query->get()->toArray();
    }

    /**
     * Get appointments by type.
     *
     * @param int $typeId The appointment type ID
     * @param int|null $createdById Optional user ID filter
     * @param int $perPage Number of items per page
     * @param int $page Current page number
     * @return LengthAwarePaginator Paginated appointment records
     */
    public function getAppointmentsByType(int $typeId, ?int $createdById = null, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        $query = $this->model->with(['createdBy', 'type', 'outcome'])
            ->where('type_id', $typeId)
            ->orderBy('start', 'asc');

        if ($createdById) {
            $query->where('created_by_id', $createdById);
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get appointments by outcome.
     *
     * @param int $outcomeId The appointment outcome ID
     * @param int|null $createdById Optional user ID filter
     * @param int $perPage Number of items per page
     * @param int $page Current page number
     * @return LengthAwarePaginator Paginated appointment records
     */
    public function getAppointmentsByOutcome(int $outcomeId, ?int $createdById = null, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        $query = $this->model->with(['createdBy', 'type', 'outcome'])
            ->where('outcome_id', $outcomeId)
            ->orderBy('start', 'asc');

        if ($createdById) {
            $query->where('created_by_id', $createdById);
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get appointments within a date range.
     *
     * @param string $startDate Start date
     * @param string $endDate End date
     * @param int|null $createdById Optional user ID filter
     * @param int $perPage Number of items per page
     * @param int $page Current page number
     * @return LengthAwarePaginator Paginated appointment records
     */
    public function getAppointmentsByDateRange(string $startDate, string $endDate, ?int $createdById = null, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        $query = $this->model->with(['createdBy', 'type', 'outcome'])
            ->betweenDates($startDate, $endDate)
            ->orderBy('start', 'asc');

        if ($createdById) {
            $query->where('created_by_id', $createdById);
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get all-day appointments.
     *
     * @param int|null $createdById Optional user ID filter
     * @param int $perPage Number of items per page
     * @param int $page Current page number
     * @return LengthAwarePaginator Paginated appointment records
     */
    public function getAllDayAppointments(?int $createdById = null, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        $query = $this->model->with(['createdBy', 'type', 'outcome'])
            ->allDay()
            ->orderBy('start', 'asc');

        if ($createdById) {
            $query->where('created_by_id', $createdById);
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get timed appointments.
     *
     * @param int|null $createdById Optional user ID filter
     * @param int $perPage Number of items per page
     * @param int $page Current page number
     * @return LengthAwarePaginator Paginated appointment records
     */
    public function getTimedAppointments(?int $createdById = null, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        $query = $this->model->with(['createdBy', 'type', 'outcome'])
            ->timed()
            ->orderBy('start', 'asc');

        if ($createdById) {
            $query->where('created_by_id', $createdById);
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get appointments with specific invitees.
     *
     * @param string $inviteeType 'user' or 'person'
     * @param int|string $inviteeId The invitee ID
     * @param int $perPage Number of items per page
     * @param int $page Current page number
     * @return LengthAwarePaginator Paginated appointment records
     */
    public function getAppointmentsWithInvitee(string $inviteeType, $inviteeId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->model->with(['createdBy', 'type', 'outcome'])
            ->withInvitee($inviteeType, $inviteeId)
            ->orderBy('start', 'asc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get appointment statistics for a user.
     *
     * @param int $createdById The user ID
     * @return array Statistics array
     */
    public function getAppointmentStatistics(int $createdById): array
    {
        $baseQuery = $this->model->where('created_by_id', $createdById);

        return [
            'total_appointments' => $baseQuery->count(),
            'today_appointments' => $baseQuery->today()->count(),
            'tomorrow_appointments' => $baseQuery->tomorrow()->count(),
            'this_week_appointments' => $baseQuery->thisWeek()->count(),
            'next_week_appointments' => $baseQuery->nextWeek()->count(),
            'this_month_appointments' => $baseQuery->thisMonth()->count(),
            'upcoming_appointments' => $baseQuery->upcoming()->count(),
            'past_appointments' => $baseQuery->past()->count(),
            'current_appointments' => $baseQuery->current()->count(),
            'all_day_appointments' => $baseQuery->allDay()->count(),
            'timed_appointments' => $baseQuery->timed()->count(),
        ];
    }

    /**
     * Check for appointment conflicts.
     *
     * @param string $start Start date/time
     * @param string $end End date/time
     * @param int|null $excludeId Optional appointment ID to exclude from conflict check
     * @param int|null $createdById Optional user ID filter
     * @return array Conflicting appointments
     */
    public function checkForConflicts(string $start, string $end, ?int $excludeId = null, ?int $createdById = null): array
    {
        $query = $this->model->with(['createdBy', 'type', 'outcome'])
            ->where(function ($q) use ($start, $end) {
                $q->where(function ($subQ) use ($start, $end) {
                    // Overlapping appointments
                    $subQ->where('start', '<', $end)
                        ->where('end', '>', $start);
                });
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        if ($createdById) {
            $query->where('created_by_id', $createdById);
        }

        return $query->get()->toArray();
    }
}
