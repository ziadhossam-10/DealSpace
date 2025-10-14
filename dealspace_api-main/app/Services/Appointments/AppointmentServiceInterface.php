<?php

namespace App\Services\Appointments;

use App\Models\Appointment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AppointmentServiceInterface
{
    /**
     * Get all appointments with optional filtering.
     */
    public function getAll(
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
     * Get an appointment by ID.
     */
    public function findById(int $appointmentId): Appointment;

    /**
     * Create a new appointment.
     */
    public function create(array $data): Appointment;

    /**
     * Update an existing appointment.
     */
    public function update(int $appointmentId, array $data): Appointment;

    /**
     * Delete an appointment.
     */
    public function delete(int $appointmentId): bool;

    /**
     * Get appointments for a specific user.
     */
    public function getAppointmentsForUser(int $userId, int $perPage = 15, int $page = 1): LengthAwarePaginator;

    /**
     * Get today's appointments.
     */
    public function getTodayAppointments(?int $createdById = null): array;

    /**
     * Get tomorrow's appointments.
     */
    public function getTomorrowAppointments(?int $createdById = null): array;

    /**
     * Get upcoming appointments.
     */
    public function getUpcomingAppointments(?int $createdById = null, int $limit = 50): array;

    /**
     * Get past appointments.
     */
    public function getPastAppointments(?int $createdById = null, int $perPage = 15, int $page = 1): LengthAwarePaginator;

    /**
     * Get current appointments (happening now).
     */
    public function getCurrentAppointments(?int $createdById = null): array;

    /**
     * Get this week's appointments.
     */
    public function getThisWeekAppointments(?int $createdById = null): array;

    /**
     * Get next week's appointments.
     */
    public function getNextWeekAppointments(?int $createdById = null): array;

    /**
     * Get this month's appointments.
     */
    public function getThisMonthAppointments(?int $createdById = null): array;

    /**
     * Get appointments by type.
     */
    public function getAppointmentsByType(int $typeId, ?int $createdById = null, int $perPage = 15, int $page = 1): LengthAwarePaginator;

    /**
     * Get appointments by outcome.
     */
    public function getAppointmentsByOutcome(int $outcomeId, ?int $createdById = null, int $perPage = 15, int $page = 1): LengthAwarePaginator;

    /**
     * Get appointments within a date range.
     */
    public function getAppointmentsByDateRange(string $startDate, string $endDate, ?int $createdById = null, int $perPage = 15, int $page = 1): LengthAwarePaginator;

    /**
     * Get all-day appointments.
     */
    public function getAllDayAppointments(?int $createdById = null, int $perPage = 15, int $page = 1): LengthAwarePaginator;

    /**
     * Get timed appointments.
     */
    public function getTimedAppointments(?int $createdById = null, int $perPage = 15, int $page = 1): LengthAwarePaginator;

    /**
     * Get appointments with specific invitees.
     */
    public function getAppointmentsWithInvitee(string $inviteeType, $inviteeId, int $perPage = 15, int $page = 1): LengthAwarePaginator;

    /**
     * Get appointment statistics for a user.
     */
    public function getAppointmentStatistics(int $createdById): array;

    /**
     * Check for appointment conflicts.
     */
    public function checkForConflicts(string $start, string $end, ?int $excludeId = null, ?int $createdById = null): array;
}
