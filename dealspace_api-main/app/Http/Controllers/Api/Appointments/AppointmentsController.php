<?php

namespace App\Http\Controllers\Api\Appointments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Appointments\GetAppointmentsRequest;
use App\Http\Requests\Appointments\StoreAppointmentRequest;
use App\Http\Requests\Appointments\UpdateAppointmentRequest;
use App\Http\Resources\AppointmentCollection;
use App\Http\Resources\AppointmentResource;
use App\Services\Appointments\AppointmentServiceInterface;
use Illuminate\Http\JsonResponse;

class AppointmentsController extends Controller
{
    protected $appointmentService;

    public function __construct(AppointmentServiceInterface $appointmentService)
    {
        $this->appointmentService = $appointmentService;
    }

    /**
     * Get all appointments with optional filtering.
     *
     * @param GetAppointmentsRequest $request
     * @return JsonResponse JSON response containing all appointments.
     */
    public function index(GetAppointmentsRequest $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);
        $createdById = $request->input('created_by_id', null);
        $typeId = $request->input('type_id', null);
        $outcomeId = $request->input('outcome_id', null);
        $allDay = $request->input('all_day', null);
        $startDate = $request->input('start_date', null);
        $endDate = $request->input('end_date', null);
        $personId = $request->input('person_id', null);

        $appointments = $this->appointmentService->getAll(
            $perPage,
            $page,
            $createdById,
            $typeId,
            $outcomeId,
            $allDay,
            $startDate,
            $endDate,
            $personId
        );

        return successResponse(
            'Appointments retrieved successfully',
            new AppointmentCollection($appointments)
        );
    }

    /**
     * Get a specific appointment by ID.
     *
     * @param int $id The ID of the appointment to retrieve.
     * @return JsonResponse JSON response containing the appointment.
     */
    public function show(int $id): JsonResponse
    {
        $appointment = $this->appointmentService->findById($id);

        return successResponse(
            'Appointment retrieved successfully',
            new AppointmentResource($appointment)
        );
    }

    /**
     * Create a new appointment.
     *
     * @param StoreAppointmentRequest $request The request instance containing the data to create an appointment.
     * @return JsonResponse JSON response containing the created appointment and a 201 status code.
     */
    public function store(StoreAppointmentRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['created_by_id'] = $data['created_by_id'] ?? $request->user()->id;

        $appointment = $this->appointmentService->create($data);

        return successResponse(
            'Appointment created successfully',
            new AppointmentResource($appointment),
            201
        );
    }

    /**
     * Update an existing appointment.
     *
     * @param UpdateAppointmentRequest $request The request instance containing the data to update the appointment.
     * @param int $id The ID of the appointment to update.
     * @return JsonResponse JSON response containing the updated appointment.
     */
    public function update(UpdateAppointmentRequest $request, int $id): JsonResponse
    {
        $data = $request->validated();
        $appointment = $this->appointmentService->update($id, $data);

        return successResponse(
            'Appointment updated successfully',
            new AppointmentResource($appointment)
        );
    }

    /**
     * Delete an appointment.
     *
     * @param int $id The ID of the appointment to delete.
     * @return JsonResponse JSON response confirming deletion.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->appointmentService->delete($id);

        return successResponse(
            'Appointment deleted successfully',
            null,
            204
        );
    }

    /**
     * Get today's appointments.
     *
     * @param GetAppointmentsRequest $request
     * @return JsonResponse JSON response containing today's appointments.
     */
    public function todayAppointments(GetAppointmentsRequest $request): JsonResponse
    {
        $createdById = $request->input('created_by_id', null);
        $appointments = $this->appointmentService->getTodayAppointments($createdById);

        return successResponse(
            'Today\'s appointments retrieved successfully',
            AppointmentResource::collection($appointments)
        );
    }

    /**
     * Get tomorrow's appointments.
     *
     * @param GetAppointmentsRequest $request
     * @return JsonResponse JSON response containing tomorrow's appointments.
     */
    public function tomorrowAppointments(GetAppointmentsRequest $request): JsonResponse
    {
        $createdById = $request->input('created_by_id', null);
        $appointments = $this->appointmentService->getTomorrowAppointments($createdById);

        return successResponse(
            'Tomorrow\'s appointments retrieved successfully',
            AppointmentResource::collection($appointments)
        );
    }

    /**
     * Get upcoming appointments.
     *
     * @param GetAppointmentsRequest $request
     * @return JsonResponse JSON response containing upcoming appointments.
     */
    public function upcomingAppointments(GetAppointmentsRequest $request): JsonResponse
    {
        $createdById = $request->input('created_by_id', null);
        $limit = $request->input('limit', 50);
        $appointments = $this->appointmentService->getUpcomingAppointments($createdById, $limit);

        return successResponse(
            'Upcoming appointments retrieved successfully',
            AppointmentResource::collection($appointments)
        );
    }

    /**
     * Get past appointments.
     *
     * @param GetAppointmentsRequest $request
     * @return JsonResponse JSON response containing past appointments.
     */
    public function pastAppointments(GetAppointmentsRequest $request): JsonResponse
    {
        $createdById = $request->input('created_by_id', null);
        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);

        $appointments = $this->appointmentService->getPastAppointments($createdById, $perPage, $page);

        return successResponse(
            'Past appointments retrieved successfully',
            new AppointmentCollection($appointments)
        );
    }

    /**
     * Get current appointments (happening now).
     *
     * @param GetAppointmentsRequest $request
     * @return JsonResponse JSON response containing current appointments.
     */
    public function currentAppointments(GetAppointmentsRequest $request): JsonResponse
    {
        $createdById = $request->input('created_by_id', null);
        $appointments = $this->appointmentService->getCurrentAppointments($createdById);

        return successResponse(
            'Current appointments retrieved successfully',
            AppointmentResource::collection($appointments)
        );
    }

    /**
     * Get this week's appointments.
     *
     * @param GetAppointmentsRequest $request
     * @return JsonResponse JSON response containing this week's appointments.
     */
    public function thisWeekAppointments(GetAppointmentsRequest $request): JsonResponse
    {
        $createdById = $request->input('created_by_id', null);
        $appointments = $this->appointmentService->getThisWeekAppointments($createdById);

        return successResponse(
            'This week\'s appointments retrieved successfully',
            AppointmentResource::collection($appointments)
        );
    }

    /**
     * Get next week's appointments.
     *
     * @param GetAppointmentsRequest $request
     * @return JsonResponse JSON response containing next week's appointments.
     */
    public function nextWeekAppointments(GetAppointmentsRequest $request): JsonResponse
    {
        $createdById = $request->input('created_by_id', null);
        $appointments = $this->appointmentService->getNextWeekAppointments($createdById);

        return successResponse(
            'Next week\'s appointments retrieved successfully',
            AppointmentResource::collection($appointments)
        );
    }

    /**
     * Get this month's appointments.
     *
     * @param GetAppointmentsRequest $request
     * @return JsonResponse JSON response containing this month's appointments.
     */
    public function thisMonthAppointments(GetAppointmentsRequest $request): JsonResponse
    {
        $createdById = $request->input('created_by_id', null);
        $appointments = $this->appointmentService->getThisMonthAppointments($createdById);

        return successResponse(
            'This month\'s appointments retrieved successfully',
            AppointmentResource::collection($appointments)
        );
    }

    /**
     * Get appointments by type.
     *
     * @param GetAppointmentsRequest $request
     * @param int $typeId The ID of the appointment type.
     * @return JsonResponse JSON response containing appointments by type.
     */
    public function appointmentsByType(GetAppointmentsRequest $request, int $typeId): JsonResponse
    {
        $createdById = $request->input('created_by_id', null);
        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);

        $appointments = $this->appointmentService->getAppointmentsByType($typeId, $createdById, $perPage, $page);

        return successResponse(
            'Appointments by type retrieved successfully',
            new AppointmentCollection($appointments)
        );
    }

    /**
     * Get appointments by outcome.
     *
     * @param GetAppointmentsRequest $request
     * @param int $outcomeId The ID of the appointment outcome.
     * @return JsonResponse JSON response containing appointments by outcome.
     */
    public function appointmentsByOutcome(GetAppointmentsRequest $request, int $outcomeId): JsonResponse
    {
        $createdById = $request->input('created_by_id', null);
        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);

        $appointments = $this->appointmentService->getAppointmentsByOutcome($outcomeId, $createdById, $perPage, $page);

        return successResponse(
            'Appointments by outcome retrieved successfully',
            new AppointmentCollection($appointments)
        );
    }

    /**
     * Get appointments within a date range.
     *
     * @param GetAppointmentsRequest $request
     * @return JsonResponse JSON response containing appointments within date range.
     */
    public function appointmentsByDateRange(GetAppointmentsRequest $request): JsonResponse
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $createdById = $request->input('created_by_id', null);
        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);

        $appointments = $this->appointmentService->getAppointmentsByDateRange($startDate, $endDate, $createdById, $perPage, $page);

        return successResponse(
            'Appointments by date range retrieved successfully',
            new AppointmentCollection($appointments)
        );
    }

    /**
     * Get all-day appointments.
     *
     * @param GetAppointmentsRequest $request
     * @return JsonResponse JSON response containing all-day appointments.
     */
    public function allDayAppointments(GetAppointmentsRequest $request): JsonResponse
    {
        $createdById = $request->input('created_by_id', null);
        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);

        $appointments = $this->appointmentService->getAllDayAppointments($createdById, $perPage, $page);

        return successResponse(
            'All-day appointments retrieved successfully',
            new AppointmentCollection($appointments)
        );
    }

    /**
     * Get timed appointments.
     *
     * @param GetAppointmentsRequest $request
     * @return JsonResponse JSON response containing timed appointments.
     */
    public function timedAppointments(GetAppointmentsRequest $request): JsonResponse
    {
        $createdById = $request->input('created_by_id', null);
        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);

        $appointments = $this->appointmentService->getTimedAppointments($createdById, $perPage, $page);

        return successResponse(
            'Timed appointments retrieved successfully',
            new AppointmentCollection($appointments)
        );
    }

    /**
     * Get appointments for a specific user.
     *
     * @param GetAppointmentsRequest $request
     * @param int $userId The ID of the user.
     * @return JsonResponse JSON response containing appointments for the user.
     */
    public function appointmentsForUser(GetAppointmentsRequest $request, int $userId): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);

        $appointments = $this->appointmentService->getAppointmentsForUser($userId, $perPage, $page);

        return successResponse(
            'Appointments for user retrieved successfully',
            new AppointmentCollection($appointments)
        );
    }

    /**
     * Get appointments with specific invitees.
     *
     * @param GetAppointmentsRequest $request
     * @param string $inviteeType The type of invitee (user or person).
     * @param mixed $inviteeId The ID of the invitee.
     * @return JsonResponse JSON response containing appointments with the invitee.
     */
    public function appointmentsWithInvitee(GetAppointmentsRequest $request, string $inviteeType, $inviteeId): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);

        $appointments = $this->appointmentService->getAppointmentsWithInvitee($inviteeType, $inviteeId, $perPage, $page);

        return successResponse(
            'Appointments with invitee retrieved successfully',
            new AppointmentCollection($appointments)
        );
    }

    /**
     * Get appointment statistics for a user.
     *
     * @param GetAppointmentsRequest $request
     * @param int $userId The ID of the user.
     * @return JsonResponse JSON response containing appointment statistics.
     */
    public function appointmentStatistics(GetAppointmentsRequest $request, int $userId): JsonResponse
    {
        $statistics = $this->appointmentService->getAppointmentStatistics($userId);

        return successResponse(
            'Appointment statistics retrieved successfully',
            $statistics
        );
    }

    /**
     * Check for appointment conflicts.
     *
     * @param GetAppointmentsRequest $request
     * @return JsonResponse JSON response containing conflict check results.
     */
    public function checkConflicts(GetAppointmentsRequest $request): JsonResponse
    {
        $start = $request->input('start');
        $end = $request->input('end');
        $excludeId = $request->input('exclude_id', null);
        $createdById = $request->input('created_by_id', null);

        $conflicts = $this->appointmentService->checkForConflicts($start, $end, $excludeId, $createdById);

        return successResponse(
            'Conflict check completed',
            [
                'has_conflicts' => !empty($conflicts),
                'conflicts' => AppointmentResource::collection($conflicts)
            ]
        );
    }
}
