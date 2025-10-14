<?php

namespace App\Repositories\Appointments;

use App\Models\Appointment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AppointmentsRepositoryInterface
{
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
        ?int $personId = null,
    ): LengthAwarePaginator;

    /**
     * Find an appointment by its ID.
     *
     * @param int $appointmentId The ID of the appointment to find
     * @return Appointment|null The found Appointment instance or null if not found
     */
    public function findById(int $appointmentId): ?Appointment;

    /**
     * Create a new appointment record.
     *
     * @param array $data The data for the new appointment
     * @return Appointment The newly created Appointment model instance
     */
    public function create(array $data): Appointment;

    /**
     * Update an existing appointment record.
     *
     * @param int $appointmentId The ID of the appointment to update
     * @param array $data The data to update
     * @return Appointment The updated Appointment model instance
     */
    public function update(int $appointmentId, array $data): Appointment;

    /**
     * Delete an appointment record.
     *
     * @param int $appointmentId The ID of the appointment to delete
     * @return bool True if deletion was successful
     */
    public function delete(int $appointmentId): bool;

    /**
     * Get appointments for a specific user (created by).
     *
     * @param int $userId The user ID
     * @param int $perPage Number of items per page
     * @param int $page Current page number
     * @return LengthAwarePaginator Paginated appointment records
     */
    public function getAppointmentsForUser(int $userId, int $perPage = 15, int $page = 1): LengthAwarePaginator;

    /**
     * Get appointments for today.
     *
     * @param int|null $createdById Optional user ID filter
     * @return array Collection of appointments for today
     */
    public function getTodayAppointments(?int $createdById = null): array;

    /**
     * Get appointments for tomorrow.
     *
     * @param int|null $createdById Optional user ID filter
     * @return array Collection of appointments for tomorrow
     */
    public function getTomorrowAppointments(?int $createdById = null): array;

    /**
     * Get upcoming appointments.
     *
     * @param int|null $createdById Optional user ID filter
     * @param int $limit Optional limit for results
     * @return array Collection of upcoming appointments
     */
    public function getUpcomingAppointments(?int $createdById = null, int $limit = 50): array;

    /**
     * Get past appointments.
     *
     * @param int|null $createdById Optional user ID filter
     * @param int $perPage Number of items per page
     * @param int $page Current page number
     * @return LengthAwarePaginator Paginated past appointments
     */
    public function getPastAppointments(?int $createdById = null, int $perPage = 15, int $page = 1): LengthAwarePaginator;

    /**
     * Get current appointments (happening now).
     *
     * @param int|null $createdById Optional user ID filter
     * @return array Collection of current appointments
     */
    public function getCurrentAppointments(?int $createdById = null): array;

    /**
     * Get appointments for this week.
     *
     * @param int|null $createdById Optional user ID filter
     * @return array Collection of appointments for this week
     */
    public function getThisWeekAppointments(?int $createdById = null): array;

    /**
     * Get appointments for next week.
     *
     * @param int|null $createdById Optional user ID filter
     * @return array Collection of appointments for next week
     */
    public function getNextWeekAppointments(?int $createdById = null): array;

    /**
     * Get appointments for this month.
     *
     * @param int|null $createdById Optional user ID filter
     * @return array Collection of appointments for this month
     */
    public function getThisMonthAppointments(?int $createdById = null): array;

    /**
     * Get appointments by type.
     *
     * @param int $typeId The appointment type ID
     * @param int|null $createdById Optional user ID filter
     * @param int $perPage Number of items per page
     * @param int $page Current page number
     * @return LengthAwarePaginator Paginated appointment records
     */
    public function getAppointmentsByType(int $typeId, ?int $createdById = null, int $perPage = 15, int $page = 1): LengthAwarePaginator;

    /**
     * Get appointments by outcome.
     *
     * @param int $outcomeId The appointment outcome ID
     * @param int|null $createdById Optional user ID filter
     * @param int $perPage Number of items per page
     * @param int $page Current page number
     * @return LengthAwarePaginator Paginated appointment records
     */
    public function getAppointmentsByOutcome(int $outcomeId, ?int $createdById = null, int $perPage = 15, int $page = 1): LengthAwarePaginator;

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
    public function getAppointmentsByDateRange(string $startDate, string $endDate, ?int $createdById = null, int $perPage = 15, int $page = 1): LengthAwarePaginator;

    /**
     * Get all-day appointments.
     *
     * @param int|null $createdById Optional user ID filter
     * @param int $perPage Number of items per page
     * @param int $page Current page number
     * @return LengthAwarePaginator Paginated appointment records
     */
    public function getAllDayAppointments(?int $createdById = null, int $perPage = 15, int $page = 1): LengthAwarePaginator;

    /**
     * Get timed appointments.
     *
     * @param int|null $createdById Optional user ID filter
     * @param int $perPage Number of items per page
     * @param int $page Current page number
     * @return LengthAwarePaginator Paginated appointment records
     */
    public function getTimedAppointments(?int $createdById = null, int $perPage = 15, int $page = 1): LengthAwarePaginator;

    /**
     * Get appointments with specific invitees.
     *
     * @param string $inviteeType 'user' or 'person'
     * @param int|string $inviteeId The invitee ID
     * @param int $perPage Number of items per page
     * @param int $page Current page number
     * @return LengthAwarePaginator Paginated appointment records
     */
    public function getAppointmentsWithInvitee(string $inviteeType, $inviteeId, int $perPage = 15, int $page = 1): LengthAwarePaginator;

    /**
     * Get appointment statistics for a user.
     *
     * @param int $createdById The user ID
     * @return array Statistics array
     */
    public function getAppointmentStatistics(int $createdById): array;

    /**
     * Check for appointment conflicts.
     *
     * @param string $start Start date/time
     * @param string $end End date/time
     * @param int|null $excludeId Optional appointment ID to exclude from conflict check
     * @param int|null $createdById Optional user ID filter
     * @return array Conflicting appointments
     */
    public function checkForConflicts(string $start, string $end, ?int $excludeId = null, ?int $createdById = null): array;
}