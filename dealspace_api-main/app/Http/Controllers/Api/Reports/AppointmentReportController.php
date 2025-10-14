<?php

namespace App\Http\Controllers\Api\Reports;

use App\Enums\RoleEnum;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Appointment;
use App\Models\Team;
use App\Models\AppointmentType;
use App\Models\AppointmentOutcome;

class AppointmentReportController extends Controller
{
    public function index(Request $request)
    {
        $startDate = Carbon::parse($request->start_date ?? now()->startOfMonth());
        $endDate = Carbon::parse($request->end_date ?? now()->endOfMonth());
        $agentIds = $request->agent_ids ?? [];
        $teamId = $request->team_id;
        $appointmentTypeId = $request->appointment_type_id;
        $outcomeId = $request->outcome_id;
        $status = $request->status ?? 'all'; // all, completed, upcoming, current

        // Get filtered agent IDs based on team
        $filteredAgentIds = $this->getFilteredAgentIds($agentIds, $teamId);

        $report = [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
                'display_name' => $this->getPeriodDisplayName($startDate, $endDate)
            ],
            'team_info' => $this->getTeamInfo($teamId),
            'filters' => [
                'appointment_type_id' => $appointmentTypeId,
                'outcome_id' => $outcomeId,
                'status' => $status
            ],
            'agents' => $this->getAppointmentMetrics($startDate, $endDate, $filteredAgentIds, $appointmentTypeId, $outcomeId, $status),
            'totals' => $this->getTotalAppointmentMetrics($startDate, $endDate, $filteredAgentIds, $appointmentTypeId, $outcomeId, $status),
            'time_series' => $this->getAppointmentTimeSeriesData($startDate, $endDate, $filteredAgentIds, $appointmentTypeId, $outcomeId, $status),
            'summary_stats' => $this->getSummaryStats($startDate, $endDate, $filteredAgentIds, $appointmentTypeId, $outcomeId, $status),
            'type_breakdown' => $this->getTypeBreakdown($startDate, $endDate, $filteredAgentIds, $outcomeId, $status),
            'outcome_breakdown' => $this->getOutcomeBreakdown($startDate, $endDate, $filteredAgentIds, $appointmentTypeId, $status),
            'appointments_list' => $this->getAppointmentsList($startDate, $endDate, $filteredAgentIds, $appointmentTypeId, $outcomeId, $status)
        ];

        return response()->json($report);
    }

    /**
     * Get period display name
     */
    private function getPeriodDisplayName($startDate, $endDate)
    {
        if ($startDate->isSameMonth($endDate) && $startDate->isSameYear($endDate)) {
            return $startDate->format('F Y');
        }

        return $startDate->format('M j, Y') . ' - ' . $endDate->format('M j, Y');
    }

    /**
     * Get team information if team_id is provided
     */
    private function getTeamInfo($teamId)
    {
        if (!$teamId) {
            return null;
        }

        $team = Team::with(['agents' => function ($query) {
            $query->select('users.id', 'users.name', 'users.email')
                ->where('users.role', RoleEnum::AGENT);
        }])->find($teamId);

        if (!$team) {
            return null;
        }

        return [
            'id' => $team->id,
            'name' => $team->name,
            'agent_count' => $team->agents->count(),
            'agents' => $team->agents->map(function ($agent) {
                return [
                    'id' => $agent->id,
                    'name' => $agent->name,
                    'email' => $agent->email,
                ];
            }),
        ];
    }

    /**
     * Get filtered agent IDs based on team selection
     */
    private function getFilteredAgentIds($agentIds, $teamId)
    {
        if ($teamId) {
            $team = Team::find($teamId);
            if (!$team) {
                return collect();
            }

            $teamAgentIds = $team->agents()->pluck('users.id');

            if (!empty($agentIds)) {
                return $teamAgentIds->intersect($agentIds);
            }

            return $teamAgentIds;
        }

        if (!empty($agentIds)) {
            return collect($agentIds);
        }

        return User::where('role', RoleEnum::AGENT)->pluck('id');
    }

    /**
     * Get appointment metrics for each agent
     */
    private function getAppointmentMetrics($startDate, $endDate, $agentIds, $appointmentTypeId = null, $outcomeId = null, $status = 'all')
    {
        $agents = User::whereIn('id', $agentIds)->get();
        $metrics = [];

        foreach ($agents as $agent) {
            $metrics[] = [
                'agent_id' => $agent->id,
                'agent_name' => $agent->name,
                'email' => $agent->email,

                // Core Appointment Metrics
                'appointments_created' => $this->getAppointmentsCreated($agent->id, $startDate, $endDate, $appointmentTypeId, $outcomeId, $status),
                'appointments_completed' => $this->getAppointmentsCompleted($agent->id, $startDate, $endDate, $appointmentTypeId, $outcomeId),
                'appointments_upcoming' => $this->getAppointmentsUpcoming($agent->id, $startDate, $endDate, $appointmentTypeId, $outcomeId),
                'appointments_current' => $this->getAppointmentsCurrent($agent->id, $appointmentTypeId, $outcomeId),

                // Performance Metrics
                'completion_rate' => $this->getCompletionRate($agent->id, $startDate, $endDate, $appointmentTypeId, $outcomeId),

                // Time Metrics
                'avg_appointment_duration' => $this->getAverageAppointmentDuration($agent->id, $startDate, $endDate, $appointmentTypeId, $outcomeId),
                'total_appointment_time' => $this->getTotalAppointmentTime($agent->id, $startDate, $endDate, $appointmentTypeId, $outcomeId),

                // Daily Averages
                'avg_appointments_per_day' => $this->getAverageAppointmentsPerDay($agent->id, $startDate, $endDate, $appointmentTypeId, $outcomeId, $status),

                // Breakdown by Type and Outcome
                'appointments_by_type' => $this->getAppointmentsByType($agent->id, $startDate, $endDate, $outcomeId, $status),
                'appointments_by_outcome' => $this->getAppointmentsByOutcome($agent->id, $startDate, $endDate, $appointmentTypeId, $status),

                // Invitee Metrics
                'total_user_invitees' => $this->getTotalUserInvitees($agent->id, $startDate, $endDate, $appointmentTypeId, $outcomeId, $status),
                'total_person_invitees' => $this->getTotalPersonInvitees($agent->id, $startDate, $endDate, $appointmentTypeId, $outcomeId, $status),

                // Future appointments (not in date range)
                'future_appointments' => $this->getFutureAppointments($agent->id, $appointmentTypeId)
            ];
        }

        return $metrics;
    }

    // Core Appointment Metrics
    private function getAppointmentsCreated($agentId, $startDate, $endDate, $appointmentTypeId = null, $outcomeId = null, $status = 'all')
    {
        $query = Appointment::where('created_by_id', $agentId)
            ->betweenDates($startDate, $endDate);

        if ($appointmentTypeId) {
            $query->ofType($appointmentTypeId);
        }

        if ($outcomeId) {
            $query->withOutcome($outcomeId);
        }

        if ($status === 'completed') {
            $query->past();
        } elseif ($status === 'upcoming') {
            $query->upcoming();
        } elseif ($status === 'current') {
            $query->current();
        }

        return $query->count();
    }

    private function getAppointmentsCompleted($agentId, $startDate, $endDate, $appointmentTypeId = null, $outcomeId = null)
    {
        $query = Appointment::where('created_by_id', $agentId)
            ->betweenDates($startDate, $endDate)
            ->past();

        if ($appointmentTypeId) {
            $query->ofType($appointmentTypeId);
        }

        if ($outcomeId) {
            $query->withOutcome($outcomeId);
        }

        return $query->count();
    }

    private function getAppointmentsUpcoming($agentId, $startDate, $endDate, $appointmentTypeId = null, $outcomeId = null)
    {
        $query = Appointment::where('created_by_id', $agentId)
            ->betweenDates($startDate, $endDate)
            ->upcoming();

        if ($appointmentTypeId) {
            $query->ofType($appointmentTypeId);
        }

        if ($outcomeId) {
            $query->withOutcome($outcomeId);
        }

        return $query->count();
    }

    private function getAppointmentsCurrent($agentId, $appointmentTypeId = null, $outcomeId = null)
    {
        $query = Appointment::where('created_by_id', $agentId)
            ->current();

        if ($appointmentTypeId) {
            $query->ofType($appointmentTypeId);
        }

        if ($outcomeId) {
            $query->withOutcome($outcomeId);
        }

        return $query->count();
    }

    // Performance Metrics
    private function getCompletionRate($agentId, $startDate, $endDate, $appointmentTypeId = null, $outcomeId = null)
    {
        $totalAppointments = $this->getAppointmentsCreated($agentId, $startDate, $endDate, $appointmentTypeId, $outcomeId);
        $completedAppointments = $this->getAppointmentsCompleted($agentId, $startDate, $endDate, $appointmentTypeId, $outcomeId);

        return $totalAppointments > 0 ? round(($completedAppointments / $totalAppointments) * 100, 2) : 0;
    }

    // Time Metrics
    private function getAverageAppointmentDuration($agentId, $startDate, $endDate, $appointmentTypeId = null, $outcomeId = null)
    {
        $query = Appointment::where('created_by_id', $agentId)
            ->betweenDates($startDate, $endDate)
            ->past();

        if ($appointmentTypeId) {
            $query->ofType($appointmentTypeId);
        }

        if ($outcomeId) {
            $query->withOutcome($outcomeId);
        }

        $appointments = $query->get();
        $totalMinutes = 0;
        $count = 0;

        foreach ($appointments as $appointment) {
            $totalMinutes += $appointment->getDurationInMinutes();
            $count++;
        }

        return $count > 0 ? round($totalMinutes / $count, 1) : 0;
    }

    private function getTotalAppointmentTime($agentId, $startDate, $endDate, $appointmentTypeId = null, $outcomeId = null)
    {
        $query = Appointment::where('created_by_id', $agentId)
            ->betweenDates($startDate, $endDate)
            ->past();

        if ($appointmentTypeId) {
            $query->ofType($appointmentTypeId);
        }

        if ($outcomeId) {
            $query->withOutcome($outcomeId);
        }

        $appointments = $query->get();
        $totalMinutes = 0;

        foreach ($appointments as $appointment) {
            $totalMinutes += $appointment->getDurationInMinutes();
        }

        return $totalMinutes;
    }

    // Daily Averages
    private function getAverageAppointmentsPerDay($agentId, $startDate, $endDate, $appointmentTypeId = null, $outcomeId = null, $status = 'all')
    {
        $totalAppointments = $this->getAppointmentsCreated($agentId, $startDate, $endDate, $appointmentTypeId, $outcomeId, $status);
        $daysDiff = $startDate->diffInDays($endDate) + 1;

        return round($totalAppointments / $daysDiff, 2);
    }

    // Breakdown Methods
    private function getAppointmentsByType($agentId, $startDate, $endDate, $outcomeId = null, $status = 'all')
    {
        $query = Appointment::where('created_by_id', $agentId)
            ->betweenDates($startDate, $endDate)
            ->with('type');

        if ($outcomeId) {
            $query->withOutcome($outcomeId);
        }

        if ($status === 'completed') {
            $query->past();
        } elseif ($status === 'upcoming') {
            $query->upcoming();
        } elseif ($status === 'current') {
            $query->current();
        }

        $appointments = $query->get();
        $distribution = [];

        foreach ($appointments as $appointment) {
            $typeName = $appointment->type->name ?? 'Unknown';
            $distribution[$typeName] = ($distribution[$typeName] ?? 0) + 1;
        }

        return $distribution;
    }

    private function getAppointmentsByOutcome($agentId, $startDate, $endDate, $appointmentTypeId = null, $status = 'all')
    {
        $query = Appointment::where('created_by_id', $agentId)
            ->betweenDates($startDate, $endDate)
            ->with('outcome');

        if ($appointmentTypeId) {
            $query->ofType($appointmentTypeId);
        }

        if ($status === 'completed') {
            $query->past();
        } elseif ($status === 'upcoming') {
            $query->upcoming();
        } elseif ($status === 'current') {
            $query->current();
        }

        $appointments = $query->get();
        $distribution = [];

        foreach ($appointments as $appointment) {
            $outcomeName = $appointment->outcome->name ?? 'No Outcome';
            $distribution[$outcomeName] = ($distribution[$outcomeName] ?? 0) + 1;
        }

        return $distribution;
    }

    // Invitee Metrics
    private function getTotalUserInvitees($agentId, $startDate, $endDate, $appointmentTypeId = null, $outcomeId = null, $status = 'all')
    {
        $query = Appointment::where('created_by_id', $agentId)
            ->betweenDates($startDate, $endDate);

        if ($appointmentTypeId) {
            $query->ofType($appointmentTypeId);
        }

        if ($outcomeId) {
            $query->withOutcome($outcomeId);
        }

        if ($status === 'completed') {
            $query->past();
        } elseif ($status === 'upcoming') {
            $query->upcoming();
        } elseif ($status === 'current') {
            $query->current();
        }

        $appointments = $query->with('invitedUsers')->get();
        $totalInvitees = 0;

        foreach ($appointments as $appointment) {
            $totalInvitees += $appointment->invitedUsers->count();
        }

        return $totalInvitees;
    }

    private function getTotalPersonInvitees($agentId, $startDate, $endDate, $appointmentTypeId = null, $outcomeId = null, $status = 'all')
    {
        $query = Appointment::where('created_by_id', $agentId)
            ->betweenDates($startDate, $endDate);

        if ($appointmentTypeId) {
            $query->ofType($appointmentTypeId);
        }

        if ($outcomeId) {
            $query->withOutcome($outcomeId);
        }

        if ($status === 'completed') {
            $query->past();
        } elseif ($status === 'upcoming') {
            $query->upcoming();
        } elseif ($status === 'current') {
            $query->current();
        }

        $appointments = $query->with('invitedPeople')->get();
        $totalInvitees = 0;

        foreach ($appointments as $appointment) {
            $totalInvitees += $appointment->invitedPeople->count();
        }

        return $totalInvitees;
    }

    private function getFutureAppointments($agentId, $appointmentTypeId = null)
    {
        $query = Appointment::where('created_by_id', $agentId)
            ->upcoming();

        if ($appointmentTypeId) {
            $query->ofType($appointmentTypeId);
        }

        return $query->count();
    }

    // Total Metrics
    private function getTotalAppointmentMetrics($startDate, $endDate, $agentIds, $appointmentTypeId = null, $outcomeId = null, $status = 'all')
    {
        $totals = [
            'appointments_created' => 0,
            'appointments_completed' => 0,
            'appointments_upcoming' => 0,
            'appointments_current' => 0,
            'total_appointment_time' => 0,
            'total_user_invitees' => 0,
            'total_person_invitees' => 0,
            'completion_rate' => 0
        ];

        foreach ($agentIds as $agentId) {
            $totals['appointments_created'] += $this->getAppointmentsCreated($agentId, $startDate, $endDate, $appointmentTypeId, $outcomeId, $status);
            $totals['appointments_completed'] += $this->getAppointmentsCompleted($agentId, $startDate, $endDate, $appointmentTypeId, $outcomeId);
            $totals['appointments_upcoming'] += $this->getAppointmentsUpcoming($agentId, $startDate, $endDate, $appointmentTypeId, $outcomeId);
            $totals['appointments_current'] += $this->getAppointmentsCurrent($agentId, $appointmentTypeId, $outcomeId);
            $totals['total_appointment_time'] += $this->getTotalAppointmentTime($agentId, $startDate, $endDate, $appointmentTypeId, $outcomeId);
            $totals['total_user_invitees'] += $this->getTotalUserInvitees($agentId, $startDate, $endDate, $appointmentTypeId, $outcomeId, $status);
            $totals['total_person_invitees'] += $this->getTotalPersonInvitees($agentId, $startDate, $endDate, $appointmentTypeId, $outcomeId, $status);
        }

        // Calculate overall completion rate
        $totals['completion_rate'] = $totals['appointments_created'] > 0 ?
            round(($totals['appointments_completed'] / $totals['appointments_created']) * 100, 2) : 0;

        return $totals;
    }

    // Time Series Data
    private function getAppointmentTimeSeriesData($startDate, $endDate, $agentIds, $appointmentTypeId = null, $outcomeId = null, $status = 'all')
    {
        $timeSeriesData = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dayStart = $currentDate->copy()->startOfDay();
            $dayEnd = $currentDate->copy()->endOfDay();

            $dayData = [
                'date' => $currentDate->format('Y-m-d'),
                'appointments_created' => $this->getAppointmentsCreatedForDay($dayStart, $dayEnd, $agentIds, $appointmentTypeId, $outcomeId, $status),
                'appointments_completed' => $this->getAppointmentsCompletedForDay($dayStart, $dayEnd, $agentIds, $appointmentTypeId, $outcomeId),
                'total_duration' => $this->getTotalDurationForDay($dayStart, $dayEnd, $agentIds, $appointmentTypeId, $outcomeId),
            ];

            $timeSeriesData[] = $dayData;
            $currentDate->addDay();
        }

        return $timeSeriesData;
    }

    private function getAppointmentsCreatedForDay($dayStart, $dayEnd, $agentIds, $appointmentTypeId = null, $outcomeId = null, $status = 'all')
    {
        $query = Appointment::whereIn('created_by_id', $agentIds)
            ->betweenDates($dayStart, $dayEnd);

        if ($appointmentTypeId) {
            $query->ofType($appointmentTypeId);
        }

        if ($outcomeId) {
            $query->withOutcome($outcomeId);
        }

        if ($status === 'completed') {
            $query->past();
        } elseif ($status === 'upcoming') {
            $query->upcoming();
        } elseif ($status === 'current') {
            $query->current();
        }

        return $query->count();
    }

    private function getAppointmentsCompletedForDay($dayStart, $dayEnd, $agentIds, $appointmentTypeId = null, $outcomeId = null)
    {
        $query = Appointment::whereIn('created_by_id', $agentIds)
            ->betweenDates($dayStart, $dayEnd)
            ->past();

        if ($appointmentTypeId) {
            $query->ofType($appointmentTypeId);
        }

        if ($outcomeId) {
            $query->withOutcome($outcomeId);
        }

        return $query->count();
    }

    private function getTotalDurationForDay($dayStart, $dayEnd, $agentIds, $appointmentTypeId = null, $outcomeId = null)
    {
        $query = Appointment::whereIn('created_by_id', $agentIds)
            ->betweenDates($dayStart, $dayEnd)
            ->past();

        if ($appointmentTypeId) {
            $query->ofType($appointmentTypeId);
        }

        if ($outcomeId) {
            $query->withOutcome($outcomeId);
        }

        $appointments = $query->get();
        $totalMinutes = 0;

        foreach ($appointments as $appointment) {
            $totalMinutes += $appointment->getDurationInMinutes();
        }

        return $totalMinutes;
    }

    // Type and Outcome Breakdowns
    private function getTypeBreakdown($startDate, $endDate, $agentIds, $outcomeId = null, $status = 'all')
    {
        $query = Appointment::whereIn('created_by_id', $agentIds)
            ->betweenDates($startDate, $endDate)
            ->with('type');

        if ($outcomeId) {
            $query->withOutcome($outcomeId);
        }

        if ($status === 'completed') {
            $query->past();
        } elseif ($status === 'upcoming') {
            $query->upcoming();
        } elseif ($status === 'current') {
            $query->current();
        }

        $appointments = $query->get();
        $typeBreakdown = [];

        foreach ($appointments as $appointment) {
            $typeName = $appointment->type->name ?? 'Unknown';

            if (!isset($typeBreakdown[$typeName])) {
                $typeBreakdown[$typeName] = [
                    'total' => 0,
                    'completed' => 0,
                    'upcoming' => 0,
                    'current' => 0,
                    'completion_rate' => 0,
                    'total_duration' => 0,
                    'avg_duration' => 0
                ];
            }

            $typeBreakdown[$typeName]['total']++;
            $typeBreakdown[$typeName]['total_duration'] += $appointment->getDurationInMinutes();

            if ($appointment->isPast()) {
                $typeBreakdown[$typeName]['completed']++;
            } elseif ($appointment->isCurrent()) {
                $typeBreakdown[$typeName]['current']++;
            } elseif ($appointment->isUpcoming()) {
                $typeBreakdown[$typeName]['upcoming']++;
            }
        }

        // Calculate completion rates and average durations
        foreach ($typeBreakdown as $type => &$data) {
            $data['completion_rate'] = $data['total'] > 0 ?
                round(($data['completed'] / $data['total']) * 100, 2) : 0;
            $data['avg_duration'] = $data['total'] > 0 ?
                round($data['total_duration'] / $data['total'], 1) : 0;
        }

        return $typeBreakdown;
    }

    private function getOutcomeBreakdown($startDate, $endDate, $agentIds, $appointmentTypeId = null, $status = 'all')
    {
        $query = Appointment::whereIn('created_by_id', $agentIds)
            ->betweenDates($startDate, $endDate)
            ->with('outcome');

        if ($appointmentTypeId) {
            $query->ofType($appointmentTypeId);
        }

        if ($status === 'completed') {
            $query->past();
        } elseif ($status === 'upcoming') {
            $query->upcoming();
        } elseif ($status === 'current') {
            $query->current();
        }

        $appointments = $query->get();
        $outcomeBreakdown = [];

        foreach ($appointments as $appointment) {
            $outcomeName = $appointment->outcome->name ?? 'No Outcome';
            $outcomeBreakdown[$outcomeName] = ($outcomeBreakdown[$outcomeName] ?? 0) + 1;
        }

        return $outcomeBreakdown;
    }

    // Appointments List
    private function getAppointmentsList($startDate, $endDate, $agentIds, $appointmentTypeId = null, $outcomeId = null, $status = 'all')
    {
        $query = Appointment::whereIn('created_by_id', $agentIds)
            ->betweenDates($startDate, $endDate)
            ->with(['createdBy', 'type', 'outcome', 'invitedUsers', 'invitedPeople']);

        if ($appointmentTypeId) {
            $query->ofType($appointmentTypeId);
        }

        if ($outcomeId) {
            $query->withOutcome($outcomeId);
        }

        if ($status === 'completed') {
            $query->past();
        } elseif ($status === 'upcoming') {
            $query->upcoming();
        } elseif ($status === 'current') {
            $query->current();
        }

        $appointments = $query->orderBy('start', 'desc')->get();

        return $appointments->map(function ($appointment) {
            return [
                'id' => $appointment->id,
                'title' => $appointment->title,
                'description' => $appointment->description,
                'start' => $appointment->start->format('Y-m-d H:i:s'),
                'end' => $appointment->end->format('Y-m-d H:i:s'),
                'all_day' => $appointment->all_day,
                'duration_minutes' => $appointment->getDurationInMinutes(),
                'duration_hours' => $appointment->getDurationInHours(),
                'location' => $appointment->location,
                'status' => $appointment->getStatusAttribute(),
                'type' => $appointment->type->name ?? 'Unknown',
                'outcome' => $appointment->outcome->name ?? 'No Outcome',
                'created_by' => [
                    'id' => $appointment->createdBy->id,
                    'name' => $appointment->createdBy->name,
                    'email' => $appointment->createdBy->email
                ],
                'invited_users' => $appointment->invitedUsers->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'response_status' => $user->pivot->response_status ?? 'pending',
                        'responded_at' => $user->pivot->responded_at
                    ];
                }),
                'invited_people' => $appointment->invitedPeople->map(function ($person) {
                    return [
                        'id' => $person->id,
                        'name' => $person->name,
                        'email' => $person->email ?? null,
                        'response_status' => $person->pivot->response_status ?? 'pending',
                        'responded_at' => $person->pivot->responded_at
                    ];
                }),
                'formatted_date_range' => $appointment->getFormattedDateRangeAttribute(),
                'is_today' => $appointment->isToday(),
                'is_tomorrow' => $appointment->isTomorrow(),
                'is_upcoming' => $appointment->isUpcoming(),
                'is_past' => $appointment->isPast(),
                'is_current' => $appointment->isCurrent(),
                'created_at' => $appointment->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $appointment->updated_at->format('Y-m-d H:i:s')
            ];
        });
    }

    // Summary Statistics
    private function getSummaryStats($startDate, $endDate, $agentIds, $appointmentTypeId = null, $outcomeId = null, $status = 'all')
    {
        $agents = User::whereIn('id', $agentIds)->get();
        $agentCount = $agents->count();

        if ($agentCount === 0) {
            return [
                'top_performer_by_appointments' => null,
                'top_performer_by_completion_rate' => null,
                'top_performer_by_total_time' => null,
                'team_averages' => []
            ];
        }

        // Find top performer by appointments
        $topPerformerByAppointments = null;
        $maxAppointments = 0;

        foreach ($agents as $agent) {
            $completed = $this->getAppointmentsCompleted($agent->id, $startDate, $endDate, $appointmentTypeId, $outcomeId);
            if ($completed > $maxAppointments) {
                $maxAppointments = $completed;
                $topPerformerByAppointments = [
                    'agent_name' => $agent->name,
                    'appointments_completed' => $completed
                ];
            }
        }

        // Find top performer by completion rate
        $topPerformerByCompletionRate = null;
        $maxCompletionRate = 0;

        foreach ($agents as $agent) {
            $completionRate = $this->getCompletionRate($agent->id, $startDate, $endDate, $appointmentTypeId, $outcomeId);
            if ($completionRate > $maxCompletionRate) {
                $maxCompletionRate = $completionRate;
                $topPerformerByCompletionRate = [
                    'agent_name' => $agent->name,
                    'completion_rate' => $completionRate
                ];
            }
        }

        // Find top performer by total time
        $topPerformerByTotalTime = null;
        $maxTotalTime = 0;

        foreach ($agents as $agent) {
            $totalTime = $this->getTotalAppointmentTime($agent->id, $startDate, $endDate, $appointmentTypeId, $outcomeId);
            if ($totalTime > $maxTotalTime) {
                $maxTotalTime = $totalTime;
                $topPerformerByTotalTime = [
                    'agent_name' => $agent->name,
                    'total_time_minutes' => $totalTime,
                    'total_time_hours' => round($totalTime / 60, 1)
                ];
            }
        }

        // Calculate team averages
        $totalMetrics = $this->getTotalAppointmentMetrics($startDate, $endDate, $agentIds, $appointmentTypeId, $outcomeId, $status);

        $teamAverages = [
            'avg_appointments_per_agent' => round($totalMetrics['appointments_created'] / $agentCount, 2),
            'avg_completed_per_agent' => round($totalMetrics['appointments_completed'] / $agentCount, 2),
            'avg_appointment_time_per_agent' => round($totalMetrics['total_appointment_time'] / $agentCount, 2),
            'avg_user_invitees_per_agent' => round($totalMetrics['total_user_invitees'] / $agentCount, 2),
            'avg_person_invitees_per_agent' => round($totalMetrics['total_person_invitees'] / $agentCount, 2),
            'team_completion_rate' => $totalMetrics['completion_rate']
        ];

        return [
            'top_performer_by_appointments' => $topPerformerByAppointments,
            'top_performer_by_completion_rate' => $topPerformerByCompletionRate,
            'top_performer_by_total_time' => $topPerformerByTotalTime,
            'team_averages' => $teamAverages
        ];
    }

    /**
     * Get available filter options
     */
    public function getFilterOptions()
    {
        $teams = Team::with(['agents' => function ($query) {
            $query->select('users.id', 'users.name', 'users.email')
                ->where('users.role', RoleEnum::AGENT);
        }])->get();

        $allAgents = User::where('role', RoleEnum::AGENT)
            ->select('id', 'name', 'email')
            ->get();

        $appointmentTypes = AppointmentType::select('id', 'name')->orderBy('sort')->get();
        $outcomes = AppointmentOutcome::select('id', 'name')->orderBy('sort')->get();

        return response()->json([
            'teams' => $teams->map(function ($team) {
                return [
                    'id' => $team->id,
                    'name' => $team->name,
                    'agent_count' => $team->agents->count(),
                    'agents' => $team->agents
                ];
            }),
            'agents' => $allAgents,
            'appointment_types' => $appointmentTypes,
            'outcomes' => $outcomes,
            'status_options' => [
                ['value' => 'all', 'label' => 'All Appointments'],
                ['value' => 'completed', 'label' => 'Completed'],
                ['value' => 'upcoming', 'label' => 'Upcoming'],
                ['value' => 'current', 'label' => 'In Progress']
            ]
        ]);
    }

    /**
     * Export appointments report
     */
    public function export(Request $request)
    {
        $startDate = Carbon::parse($request->start_date ?? now()->startOfMonth());
        $endDate = Carbon::parse($request->end_date ?? now()->endOfMonth());
        $agentIds = $request->agent_ids ?? [];
        $teamId = $request->team_id;
        $appointmentTypeId = $request->appointment_type_id;
        $outcomeId = $request->outcome_id;
        $status = $request->status ?? 'all';

        // Get filtered agent IDs based on team
        $filteredAgentIds = $this->getFilteredAgentIds($agentIds, $teamId);

        $appointments = $this->getAppointmentsList($startDate, $endDate, $filteredAgentIds, $appointmentTypeId, $outcomeId, $status);

        // Convert to CSV format
        $csvData = [];
        $csvData[] = [
            'ID',
            'Title',
            'Description',
            'Created By',
            'Created By Email',
            'Start Date/Time',
            'End Date/Time',
            'All Day',
            'Duration (minutes)',
            'Duration (hours)',
            'Location',
            'Status',
            'Type',
            'Outcome',
            'Invited Users',
            'Invited People',
            'Total Invitees',
            'Created At',
            'Updated At'
        ];

        foreach ($appointments as $appointment) {
            $invitedUserNames = collect($appointment['invited_users'])->pluck('name')->join(', ');
            $invitedPeopleNames = collect($appointment['invited_people'])->pluck('name')->join(', ');
            $totalInvitees = count($appointment['invited_users']) + count($appointment['invited_people']);

            $csvData[] = [
                $appointment['id'],
                $appointment['title'],
                $appointment['description'] ?? '',
                $appointment['created_by']['name'],
                $appointment['created_by']['email'],
                $appointment['start'],
                $appointment['end'],
                $appointment['all_day'] ? 'Yes' : 'No',
                $appointment['duration_minutes'],
                $appointment['duration_hours'],
                $appointment['location'] ?? '',
                $appointment['status'],
                $appointment['type'],
                $appointment['outcome'],
                $invitedUserNames,
                $invitedPeopleNames,
                $totalInvitees,
                $appointment['created_at'],
                $appointment['updated_at']
            ];
        }

        $fileName = 'appointment_report_' . $startDate->format('Ymd') . '_' . $endDate->format('Ymd') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ];

        $callback = function () use ($csvData) {
            $file = fopen('php://output', 'w');
            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
