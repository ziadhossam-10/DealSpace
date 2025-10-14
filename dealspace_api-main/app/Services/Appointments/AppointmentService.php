<?php

namespace App\Services\Appointments;

use App\Models\Appointment;
use App\Models\AppointmentOutcome;
use App\Models\AppointmentType;
use App\Models\User;
use App\Repositories\Appointments\AppointmentsRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AppointmentService implements AppointmentServiceInterface
{
    protected $appointmentRepository;

    public function __construct(AppointmentsRepositoryInterface $appointmentRepository)
    {
        $this->appointmentRepository = $appointmentRepository;
    }

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
    ): LengthAwarePaginator {
        return $this->appointmentRepository->getAllWithOptionalFilters(
            $perPage,
            $page,
            $createdById,
            $typeId,
            $outcomeId,
            $allDay,
            $startDate,
            $endDate,
            $personId,
        );
    }

    /**
     * Get an appointment by ID.
     */
    public function findById(int $appointmentId): Appointment
    {
        $appointment = $this->appointmentRepository->findById($appointmentId);

        if (!$appointment) {
            throw new Exception('Appointment not found');
        }

        return $appointment;
    }

    /**
     * Create a new appointment.
     */
    public function create(array $data): Appointment
    {
        return DB::transaction(function () use ($data) {
            try {
                // Extract invitees data
                $userIds = $data['user_ids'] ?? [];
                $personIds = $data['person_ids'] ?? [];
                unset($data['user_ids'], $data['person_ids']);

                // Validate the appointment data
                $this->validateAppointmentData($data);

                // Set default values
                $data['all_day'] = $data['all_day'] ?? false;


                // Create the appointment record
                $appointment = $this->appointmentRepository->create($data);

                // Attach invitees
                if (!empty($userIds)) {
                    $appointment->inviteUsers($userIds);
                }

                if (!empty($personIds)) {
                    $appointment->invitePeople($personIds);
                }

                Log::info('Appointment created successfully', [
                    'appointment_id' => $appointment->id,
                    'title' => $appointment->title,
                    'created_by_id' => $appointment->created_by_id,
                    'user_invitees' => count($userIds),
                    'person_invitees' => count($personIds)
                ]);

                return $appointment;
            } catch (Exception $e) {
                Log::error('Failed to create appointment', [
                    'error' => $e->getMessage(),
                    'data' => $data
                ]);

                throw $e;
            }
        });
    }

    /**
     * Update an existing appointment.
     */
    public function update(int $appointmentId, array $data): Appointment
    {
        return DB::transaction(function () use ($appointmentId, $data) {
            try {
                $appointment = $this->findById($appointmentId);

                // Extract invitees data
                $userIds = $data['user_ids'] ?? null;
                $personIds = $data['person_ids'] ?? null;
                $userIdsToDelete = $data['user_ids_to_delete'] ?? [];
                $personIdsToDelete = $data['person_ids_to_delete'] ?? [];

                unset($data['user_ids'], $data['person_ids'], $data['user_ids_to_delete'], $data['person_ids_to_delete']);

                // Validate the appointment data if provided
                if (isset($data['title']) || isset($data['start']) || isset($data['end'])) {
                    $this->validateAppointmentData($data, false);
                }

                // Check for conflicts if dates are being updated
                if ((isset($data['start']) || isset($data['end'])) && (isset($data['check_conflicts']) && $data['check_conflicts'])) {
                    $start = $data['start'] ?? $appointment->start;
                    $end = $data['end'] ?? $appointment->end;
                    $conflicts = $this->checkForConflicts($start, $end, $appointmentId, $appointment->created_by_id);
                    if (!empty($conflicts)) {
                        throw new Exception('Appointment conflicts with existing appointments');
                    }
                }

                // Update the appointment
                $updatedAppointment = $this->appointmentRepository->update($appointmentId, $data);

                // Handle invitee updates
                $this->handleInviteeUpdates($updatedAppointment, $userIds, $personIds, $userIdsToDelete, $personIdsToDelete);

                Log::info('Appointment updated successfully', [
                    'appointment_id' => $appointmentId,
                    'updated_fields' => array_keys($data),
                    'user_invitees_updated' => $userIds !== null,
                    'person_invitees_updated' => $personIds !== null,
                    'user_invitees_deleted' => count($userIdsToDelete),
                    'person_invitees_deleted' => count($personIdsToDelete)
                ]);

                return $updatedAppointment;
            } catch (Exception $e) {
                Log::error('Failed to update appointment', [
                    'appointment_id' => $appointmentId,
                    'error' => $e->getMessage(),
                    'data' => $data
                ]);

                throw $e;
            }
        });
    }

    /**
     * Handle invitee updates (add, sync, or delete specific invitees).
     */
    protected function handleInviteeUpdates(
        Appointment $appointment,
        ?array $userIds,
        ?array $personIds,
        array $userIdsToDelete,
        array $personIdsToDelete
    ): void {
        // Handle user invitees
        if ($userIds !== null) {
            // If user_ids is provided, sync (replace all)
            $appointment->invitedUsers()->sync($userIds);
        } elseif (!empty($userIdsToDelete)) {
            // If only deletions are requested, detach specific users
            $appointment->invitedUsers()->detach($userIdsToDelete);
        }

        // Handle person invitees
        if ($personIds !== null) {
            // If person_ids is provided, sync (replace all)
            $appointment->invitedPeople()->sync($personIds);
        } elseif (!empty($personIdsToDelete)) {
            // If only deletions are requested, detach specific people
            $appointment->invitedPeople()->detach($personIdsToDelete);
        }
    }

    /**
     * Delete an appointment.
     */
    public function delete(int $appointmentId): bool
    {
        return DB::transaction(function () use ($appointmentId) {
            try {
                $appointment = $this->findById($appointmentId);

                $deleted = $this->appointmentRepository->delete($appointmentId);

                Log::info('Appointment deleted successfully', [
                    'appointment_id' => $appointmentId
                ]);

                return $deleted;
            } catch (Exception $e) {
                Log::error('Failed to delete appointment', [
                    'appointment_id' => $appointmentId,
                    'error' => $e->getMessage()
                ]);

                throw $e;
            }
        });
    }

    /**
     * Get appointments for a specific user.
     */
    public function getAppointmentsForUser(int $userId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->appointmentRepository->getAppointmentsForUser($userId, $perPage, $page);
    }

    /**
     * Get today's appointments.
     */
    public function getTodayAppointments(?int $createdById = null): array
    {
        return $this->appointmentRepository->getTodayAppointments($createdById);
    }

    /**
     * Get tomorrow's appointments.
     */
    public function getTomorrowAppointments(?int $createdById = null): array
    {
        return $this->appointmentRepository->getTomorrowAppointments($createdById);
    }

    /**
     * Get upcoming appointments.
     */
    public function getUpcomingAppointments(?int $createdById = null, int $limit = 50): array
    {
        return $this->appointmentRepository->getUpcomingAppointments($createdById, $limit);
    }

    /**
     * Get past appointments.
     */
    public function getPastAppointments(?int $createdById = null, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->appointmentRepository->getPastAppointments($createdById, $perPage, $page);
    }

    /**
     * Get current appointments (happening now).
     */
    public function getCurrentAppointments(?int $createdById = null): array
    {
        return $this->appointmentRepository->getCurrentAppointments($createdById);
    }

    /**
     * Get this week's appointments.
     */
    public function getThisWeekAppointments(?int $createdById = null): array
    {
        return $this->appointmentRepository->getThisWeekAppointments($createdById);
    }

    /**
     * Get next week's appointments.
     */
    public function getNextWeekAppointments(?int $createdById = null): array
    {
        return $this->appointmentRepository->getNextWeekAppointments($createdById);
    }

    /**
     * Get this month's appointments.
     */
    public function getThisMonthAppointments(?int $createdById = null): array
    {
        return $this->appointmentRepository->getThisMonthAppointments($createdById);
    }

    /**
     * Get appointments by type.
     */
    public function getAppointmentsByType(int $typeId, ?int $createdById = null, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->appointmentRepository->getAppointmentsByType($typeId, $createdById, $perPage, $page);
    }

    /**
     * Get appointments by outcome.
     */
    public function getAppointmentsByOutcome(int $outcomeId, ?int $createdById = null, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->appointmentRepository->getAppointmentsByOutcome($outcomeId, $createdById, $perPage, $page);
    }

    /**
     * Get appointments within a date range.
     */
    public function getAppointmentsByDateRange(string $startDate, string $endDate, ?int $createdById = null, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->appointmentRepository->getAppointmentsByDateRange($startDate, $endDate, $createdById, $perPage, $page);
    }

    /**
     * Get all-day appointments.
     */
    public function getAllDayAppointments(?int $createdById = null, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->appointmentRepository->getAllDayAppointments($createdById, $perPage, $page);
    }

    /**
     * Get timed appointments.
     */
    public function getTimedAppointments(?int $createdById = null, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->appointmentRepository->getTimedAppointments($createdById, $perPage, $page);
    }

    /**
     * Get appointments with specific invitees.
     */
    public function getAppointmentsWithInvitee(string $inviteeType, $inviteeId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->appointmentRepository->getAppointmentsWithInvitee($inviteeType, $inviteeId, $perPage, $page);
    }

    /**
     * Get appointment statistics for a user.
     */
    public function getAppointmentStatistics(int $createdById): array
    {
        return $this->appointmentRepository->getAppointmentStatistics($createdById);
    }

    /**
     * Check for appointment conflicts.
     */
    public function checkForConflicts(string $start, string $end, ?int $excludeId = null, ?int $createdById = null): array
    {
        return $this->appointmentRepository->checkForConflicts($start, $end, $excludeId, $createdById);
    }

    /**
     * Validate appointment data.
     */
    protected function validateAppointmentData(array $data, bool $isCreating = true): void
    {
        if ($isCreating) {
            // Required fields for creation
            if (empty($data['title'])) {
                throw new \InvalidArgumentException('Appointment title is required');
            }

            if (empty($data['start'])) {
                throw new \InvalidArgumentException('Appointment start date/time is required');
            }

            if (empty($data['end'])) {
                throw new \InvalidArgumentException('Appointment end date/time is required');
            }
        }

        // Validate created by user exists
        if (isset($data['created_by_id']) && !User::find($data['created_by_id'])) {
            throw new \InvalidArgumentException('Created by user not found');
        }

        // Validate start date format
        if (isset($data['start']) && !empty($data['start'])) {
            if (!strtotime($data['start'])) {
                throw new \InvalidArgumentException('Start date/time must be a valid date/time format');
            }
        }

        // Validate end date format
        if (isset($data['end']) && !empty($data['end'])) {
            if (!strtotime($data['end'])) {
                throw new \InvalidArgumentException('End date/time must be a valid date/time format');
            }
        }

        // Validate that end time is after start time
        if (isset($data['start']) && isset($data['end'])) {
            $startTime = strtotime($data['start']);
            $endTime = strtotime($data['end']);

            if ($endTime <= $startTime) {
                throw new \InvalidArgumentException('End date/time must be after start date/time');
            }
        }

        // Validate all_day is boolean
        if (isset($data['all_day']) && !is_bool($data['all_day'])) {
            throw new \InvalidArgumentException('All day must be a boolean value');
        }

        // Validate invitees format if provided
        if (isset($data['invitees']) && !empty($data['invitees'])) {
            if (!is_array($data['invitees'])) {
                throw new \InvalidArgumentException('Invitees must be an array');
            }

            foreach ($data['invitees'] as $invitee) {
                if (!is_array($invitee) || !isset($invitee['type']) || !isset($invitee['id'])) {
                    throw new \InvalidArgumentException('Each invitee must have type and id');
                }

                if (!in_array($invitee['type'], ['user', 'person'])) {
                    throw new \InvalidArgumentException('Invitee type must be either user or person');
                }
            }
        }

        // Validate type_id exists if provided
        if (isset($data['type_id']) && !empty($data['type_id'])) {
            if (!AppointmentType::find($data['type_id'])) {
                throw new \InvalidArgumentException('Appointment type not found');
            }
        }

        // Validate outcome_id exists if provided
        if (isset($data['outcome_id']) && !empty($data['outcome_id'])) {
            if (!AppointmentOutcome::find($data['outcome_id'])) {
                throw new \InvalidArgumentException('Appointment outcome not found');
            }
        }
    }
}
