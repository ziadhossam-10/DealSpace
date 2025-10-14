<?php

namespace App\Http\Controllers\Api\Reports;

use App\Enums\RoleEnum;
use App\Exports\AgentActivityReportExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Person;
use App\Models\Call;
use App\Models\Email;
use App\Models\Note;
use App\Models\Task;
use App\Models\Appointment;
use App\Models\Team;
use App\Models\TextMessage;
use Maatwebsite\Excel\Facades\Excel;

class AgentActivityReportController extends Controller
{
    public function index(Request $request)
    {
        $startDate = Carbon::parse($request->start_date ?? now()->startOfMonth());
        $endDate = Carbon::parse($request->end_date ?? now()->endOfMonth());
        $agentIds = $request->agent_ids ?? [];
        $leadTypes = $request->lead_types ?? ['all'];
        $teamId = $request->team_id; // New parameter for team filtering

        // Get filtered agent IDs based on team
        $filteredAgentIds = $this->getFilteredAgentIds($agentIds, $teamId);

        $report = [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d')
            ],
            'team_info' => $this->getTeamInfo($teamId),
            'agents' => $this->getAgentMetrics($startDate, $endDate, $filteredAgentIds, $leadTypes),
            'totals' => $this->getTotalMetrics($startDate, $endDate, $filteredAgentIds, $leadTypes),
            'time_series' => $this->getTimeSeriesData($startDate, $endDate, $filteredAgentIds, $leadTypes)
        ];

        return response()->json($report);
    }

    public function export(Request $request)
    {
        $agentIds = $request->agent_ids ?? [];
        $teamId = $request->team_id; // New parameter for team filtering

        // Get filtered agent IDs based on team
        $filteredAgentIds = $this->getFilteredAgentIds($agentIds, $teamId);

        $params = [
            'start_date' => $request->start_date ?? now()->startOfMonth(),
            'end_date' => $request->end_date ?? now()->endOfMonth(),
            'agent_ids' => $filteredAgentIds ?? [],
            'lead_types' => $request->lead_types ?? ['all']
        ];

        $fileName = 'agent_activity_report_' . Carbon::now()->format('Ymd_His') . '.xlsx';
        return Excel::download(new AgentActivityReportExport($params), $fileName);
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
            $query->select('users.id', 'users.name', 'users.email');
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
        // If team_id is provided, get agents from that team
        if ($teamId) {
            $team = Team::find($teamId);
            if (!$team) {
                return collect(); // Return empty collection if team not found
            }

            $teamAgentIds = $team->agents()->pluck('users.id');

            // If specific agent_ids are also provided, get intersection
            if (!empty($agentIds)) {
                return $teamAgentIds->intersect($agentIds);
            }

            return $teamAgentIds;
        }

        // If no team_id but agent_ids are provided, use those
        if (!empty($agentIds)) {
            return collect($agentIds);
        }

        // If neither team_id nor agent_ids are provided, return all agents
        return User::where('role', RoleEnum::AGENT)->pluck('id');
    }


    private function getAgentMetrics($startDate, $endDate, $agentIds, $leadTypes)
    {
        $agentQuery = User::query();

        $agentQuery->where('role', RoleEnum::AGENT);

        if (!empty($agentIds)) {
            $agentQuery->whereIn('id', $agentIds);
        }

        $agents = $agentQuery->get();
        $metrics = [];

        foreach ($agents as $agent) {
            $metrics[] = [
                'agent_id' => $agent->id,
                'agent_name' => $agent->name,
                'email' => $agent->email,

                // Lead Counts
                'new_leads' => $this->getNewLeadsCount($agent->id, $startDate, $endDate, $leadTypes),
                'initially_assigned_leads' => $this->getInitiallyAssignedLeadsCount($agent->id, $startDate, $endDate, $leadTypes),
                'currently_assigned_leads' => $this->getCurrentlyAssignedLeadsCount($agent->id, $leadTypes),

                // Activity Counts
                'calls' => $this->getCallsCount($agent->id, $startDate, $endDate),
                'emails' => $this->getEmailsCount($agent->id, $startDate, $endDate),
                'texts' => $this->getTextsCount($agent->id, $startDate, $endDate),
                'notes' => $this->getNotesCount($agent->id, $startDate, $endDate),
                'tasks_completed' => $this->getTasksCompletedCount($agent->id, $startDate, $endDate),
                'appointments' => $this->getAppointmentsCount($agent->id, $startDate, $endDate),
                'appointments_set' => $this->getAppointmentsSetCount($agent->id, $startDate, $endDate),

                // Response Tracking
                'leads_not_acted_on' => $this->getLeadsNotActedOn($agent->id, $startDate, $endDate, $leadTypes),
                'leads_not_called' => $this->getLeadsNotCalled($agent->id, $startDate, $endDate, $leadTypes),
                'leads_not_emailed' => $this->getLeadsNotEmailed($agent->id, $startDate, $endDate, $leadTypes),
                'leads_not_texted' => $this->getLeadsNotTexted($agent->id, $startDate, $endDate, $leadTypes),

                // Speed Metrics (in minutes)
                'avg_speed_to_action' => $this->getAverageSpeedToAction($agent->id, $startDate, $endDate, $leadTypes),
                'avg_speed_to_first_call' => $this->getAverageSpeedToFirstCall($agent->id, $startDate, $endDate, $leadTypes),
                'avg_speed_to_first_email' => $this->getAverageSpeedToFirstEmail($agent->id, $startDate, $endDate, $leadTypes),
                'avg_speed_to_first_text' => $this->getAverageSpeedToFirstText($agent->id, $startDate, $endDate, $leadTypes),

                // Contact Attempts
                'avg_contact_attempts' => $this->getAverageContactAttempts($agent->id, $startDate, $endDate, $leadTypes),
                'avg_call_attempts' => $this->getAverageCallAttempts($agent->id, $startDate, $endDate, $leadTypes),
                'avg_email_attempts' => $this->getAverageEmailAttempts($agent->id, $startDate, $endDate, $leadTypes),
                'avg_text_attempts' => $this->getAverageTextAttempts($agent->id, $startDate, $endDate, $leadTypes),

                // Response Rates (percentages)
                'response_rate' => $this->getResponseRate($agent->id, $startDate, $endDate, $leadTypes),
                'email_response_rate' => $this->getEmailResponseRate($agent->id, $startDate, $endDate, $leadTypes),
                'phone_response_rate' => $this->getPhoneResponseRate($agent->id, $startDate, $endDate, $leadTypes),
                'text_response_rate' => $this->getTextResponseRate($agent->id, $startDate, $endDate, $leadTypes),
            ];
        }

        return $metrics;
    }

    // Lead Count Calculations
    private function getNewLeadsCount($agentId, $startDate, $endDate, $leadTypes)
    {
        $query = Person::where('assigned_user_id', $agentId)
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($leadTypes !== ['all']) {
            $query->whereIn('stage_id', $leadTypes);
        }

        return $query->count();
    }

    private function getInitiallyAssignedLeadsCount($agentId, $startDate, $endDate, $leadTypes)
    {
        // Historical count of leads created during timeframe who was FIRST assigned to this agent
        $query = Person::where('initial_assigned_user_id', $agentId) // Using assigned_user_id as initial for now
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($leadTypes !== ['all']) {
            $query->whereIn('stage_id', $leadTypes);
        }

        return $query->count();
    }

    private function getCurrentlyAssignedLeadsCount($agentId, $leadTypes)
    {
        // People currently assigned to agent during timeframe
        $query = Person::where('assigned_user_id', $agentId);

        if ($leadTypes !== ['all']) {
            $query->whereIn('stage_id', $leadTypes);
        }

        return $query->count();
    }

    // Activity Count Calculations
    private function getCallsCount($agentId, $startDate, $endDate)
    {
        return Call::where('user_id', $agentId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
    }

    private function getEmailsCount($agentId, $startDate, $endDate)
    {
        return Email::where('user_id', $agentId)
            ->where('is_incoming', false) // Only outgoing emails
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
    }

    private function getTextsCount($agentId, $startDate, $endDate)
    {
        return TextMessage::where('user_id', $agentId)
            ->where('is_incoming', false) // Only outgoing emails
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
    }

    private function getNotesCount($agentId, $startDate, $endDate)
    {
        return Note::where('created_by', $agentId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
    }

    private function getTasksCompletedCount($agentId, $startDate, $endDate)
    {
        return Task::where('assigned_user_id', $agentId)
            ->where('is_completed', true)
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->count();
    }

    private function getAppointmentsCount($agentId, $startDate, $endDate)
    {
        // Appointments where agent is an attendee
        return Appointment::whereHas('invitedUsers', function ($q) use ($agentId) {
            $q->where('user_id', $agentId)
                ->where('role', RoleEnum::AGENT);
        })
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
    }

    private function getAppointmentsSetCount($agentId, $startDate, $endDate)
    {
        // Appointments created by the agent
        return Appointment::where('created_by_id', $agentId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
    }

    // Response Tracking Calculations
    private function getLeadsNotActedOn($agentId, $startDate, $endDate, $leadTypes)
    {
        $newLeadsQuery = Person::where('assigned_user_id', $agentId)
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($leadTypes !== ['all']) {
            $newLeadsQuery->whereIn('stage_id', $leadTypes);
        }

        $newLeadIds = $newLeadsQuery->pluck('id');

        // Count people with no calls, emails, or texts
        $actedOnPeople = Person::whereIn('id', $newLeadIds)
            ->where(function ($query) {
                $query->whereHas('calls')
                    ->orWhereHas('emails')
                    ->orWhereHas('texts');
            })
            ->count();

        return $newLeadIds->count() - $actedOnPeople;
    }

    private function getLeadsNotCalled($agentId, $startDate, $endDate, $leadTypes)
    {
        $newLeadsQuery = Person::where('assigned_user_id', $agentId)
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($leadTypes !== ['all']) {
            $newLeadsQuery->whereIn('stage_id', $leadTypes);
        }

        $newLeadIds = $newLeadsQuery->pluck('id');

        $calledLeads = Person::whereIn('id', $newLeadIds)
            ->whereHas('calls', function ($q) use ($agentId) {
                $q->where('user_id', $agentId);
            })
            ->count();

        return $newLeadIds->count() - $calledLeads;
    }

    private function getLeadsNotEmailed($agentId, $startDate, $endDate, $leadTypes)
    {
        $newLeadsQuery = Person::where('assigned_user_id', $agentId)
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($leadTypes !== ['all']) {
            $newLeadsQuery->whereIn('stage_id', $leadTypes);
        }

        $newLeadIds = $newLeadsQuery->pluck('id');

        $emailedLeads = Person::whereIn('id', $newLeadIds)
            ->whereHas('emails', function ($q) use ($agentId) {
                $q->where('user_id', $agentId)
                    ->where('is_incoming', false);
            })
            ->count();

        return $newLeadIds->count() - $emailedLeads;
    }

    private function getLeadsNotTexted($agentId, $startDate, $endDate, $leadTypes)
    {
        // Step 1: Get new leads for the agent in the date range
        $newLeadsQuery = Person::where('assigned_user_id', $agentId)
            ->whereBetween('created_at', [$startDate, $endDate]);

        // Step 2: Filter by lead types if not 'all'
        if ($leadTypes !== ['all']) {
            $newLeadsQuery->whereIn('stage_id', $leadTypes);
        }

        // Step 3: Get all matched lead IDs
        $newLeadIds = $newLeadsQuery->pluck('id');

        // Step 4: Count how many of those leads have been texted (outgoing messages)
        $textedLeads = Person::whereIn('id', $newLeadIds)
            ->whereHas('texts', function ($q) use ($agentId) {
                $q->where('user_id', $agentId)
                    ->where('is_incoming', false); // only outgoing messages
            })
            ->count();

        // Step 5: Return the count of new leads - leads already texted
        return $newLeadIds->count() - $textedLeads;
    }

    // Speed Calculation Methods
    private function getAverageSpeedToAction($agentId, $startDate, $endDate, $leadTypes)
    {
        $webLeads = Person::where('assigned_user_id', $agentId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $totalMinutes = 0;
        $count = 0;

        foreach ($webLeads as $lead) {
            $firstAction = $this->getFirstActionTime($lead->id, $agentId);
            if ($firstAction) {
                $leadCreated = Carbon::parse($lead->created_at);
                $firstActionTime = Carbon::parse($firstAction);
                $totalMinutes += $leadCreated->diffInMinutes($firstActionTime);
                $count++;
            }
        }

        return $count > 0 ? round($totalMinutes / $count, 2) : 0;
    }

    private function getFirstActionTime($personId, $agentId)
    {
        // Get the earliest action (call or email) for this person by this agent
        $firstCall = Call::where('person_id', $personId)
            ->where('user_id', $agentId)
            ->min('created_at');

        $firstEmail = Email::where('person_id', $personId)
            ->where('user_id', $agentId)
            ->where('is_incoming', false)
            ->min('created_at');

        $actions = array_filter([$firstCall, $firstEmail]);

        return !empty($actions) ? min($actions) : null;
    }

    private function getAverageSpeedToFirstCall($agentId, $startDate, $endDate, $leadTypes)
    {
        $webLeads = Person::where('assigned_user_id', $agentId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $totalMinutes = 0;
        $count = 0;

        foreach ($webLeads as $lead) {
            $firstCall = Call::where('person_id', $lead->id)
                ->where('user_id', $agentId)
                ->min('created_at');

            if ($firstCall) {
                $leadCreated = Carbon::parse($lead->created_at);
                $firstCallTime = Carbon::parse($firstCall);
                $totalMinutes += $leadCreated->diffInMinutes($firstCallTime);
                $count++;
            }
        }

        return $count > 0 ? round($totalMinutes / $count, 2) : 0;
    }

    private function getAverageSpeedToFirstEmail($agentId, $startDate, $endDate, $leadTypes)
    {
        $webLeads = Person::where('assigned_user_id', $agentId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $totalMinutes = 0;
        $count = 0;

        foreach ($webLeads as $lead) {
            $firstEmail = Email::where('person_id', $lead->id)
                ->where('user_id', $agentId)
                ->where('is_incoming', false)
                ->min('created_at');

            if ($firstEmail) {
                $leadCreated = Carbon::parse($lead->created_at);
                $firstEmailTime = Carbon::parse($firstEmail);
                $totalMinutes += $leadCreated->diffInMinutes($firstEmailTime);
                $count++;
            }
        }

        return $count > 0 ? round($totalMinutes / $count, 2) : 0;
    }

    private function getAverageSpeedToFirstText($agentId, $startDate, $endDate, $leadTypes)
    {
        $webLeads = Person::where('assigned_user_id', $agentId)
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($leadTypes !== ['all']) {
            $webLeads->whereIn('stage_id', $leadTypes);
        }

        $webLeads = $webLeads->get();

        $totalMinutes = 0;
        $count = 0;

        foreach ($webLeads as $lead) {
            $firstText = \App\Models\TextMessage::where('person_id', $lead->id)
                ->where('user_id', $agentId)
                ->where('is_incoming', false) // only outgoing texts
                ->min('created_at');

            if ($firstText) {
                $leadCreated = \Carbon\Carbon::parse($lead->created_at);
                $firstTextTime = \Carbon\Carbon::parse($firstText);
                $totalMinutes += $leadCreated->diffInMinutes($firstTextTime);
                $count++;
            }
        }

        return $count > 0 ? round($totalMinutes / $count, 2) : 0;
    }
    // Contact Attempts Calculations
    private function getAverageContactAttempts($agentId, $startDate, $endDate, $leadTypes)
    {
        $newLeadsQuery = Person::where('assigned_user_id', $agentId)
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($leadTypes !== ['all']) {
            $newLeadsQuery->whereIn('stage_id', $leadTypes);
        }

        $newLeads = $newLeadsQuery->get();
        $totalAttempts = 0;

        foreach ($newLeads as $lead) {
            $calls = Call::where('person_id', $lead->id)
                ->where('user_id', $agentId)
                ->count();

            $emails = Email::where('person_id', $lead->id)
                ->where('user_id', $agentId)
                ->where('is_incoming', false)
                ->count();

            $texts = TextMessage::where('person_id', $lead->id)
                ->where('user_id', $agentId)
                ->where('is_incoming', false)
                ->count();

            $totalAttempts += ($calls + $emails + $texts);
        }

        return $newLeads->count() > 0 ? round($totalAttempts / $newLeads->count(), 2) : 0;
    }
    private function getAverageCallAttempts($agentId, $startDate, $endDate, $leadTypes)
    {
        $newLeadsQuery = Person::where('assigned_user_id', $agentId)
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($leadTypes !== ['all']) {
            $newLeadsQuery->whereIn('stage_id', $leadTypes);
        }

        $newLeads = $newLeadsQuery->get();
        $totalCalls = 0;

        foreach ($newLeads as $lead) {
            $calls = Call::where('person_id', $lead->id)
                ->where('user_id', $agentId)
                ->count();

            $totalCalls += $calls;
        }

        return $newLeads->count() > 0 ? round($totalCalls / $newLeads->count(), 2) : 0;
    }

    private function getAverageEmailAttempts($agentId, $startDate, $endDate, $leadTypes)
    {
        $newLeadsQuery = Person::where('assigned_user_id', $agentId)
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($leadTypes !== ['all']) {
            $newLeadsQuery->whereIn('stage_id', $leadTypes);
        }

        $newLeads = $newLeadsQuery->get();
        $totalEmails = 0;

        foreach ($newLeads as $lead) {
            $emails = Email::where('person_id', $lead->id)
                ->where('user_id', $agentId)
                ->where('is_incoming', false)
                ->count();

            $totalEmails += $emails;
        }

        return $newLeads->count() > 0 ? round($totalEmails / $newLeads->count(), 2) : 0;
    }

    private function getAverageTextAttempts($agentId, $startDate, $endDate, $leadTypes)
    {
        $newLeadsQuery = Person::where('assigned_user_id', $agentId)
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($leadTypes !== ['all']) {
            $newLeadsQuery->whereIn('stage_id', $leadTypes);
        }

        $newLeads = $newLeadsQuery->get();
        $totalTexts = 0;

        foreach ($newLeads as $lead) {
            $texts = TextMessage::where('person_id', $lead->id)
                ->where('user_id', $agentId)
                ->where('is_incoming', false) // Only outgoing texts
                ->count();

            $totalTexts += $texts;
        }

        return $newLeads->count() > 0 ? round($totalTexts / $newLeads->count(), 2) : 0;
    }

    // Response Rate Calculations
    private function getResponseRate($agentId, $startDate, $endDate, $leadTypes)
    {
        $newLeadsQuery = Person::where('assigned_user_id', $agentId)
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($leadTypes !== ['all']) {
            $newLeadsQuery->whereIn('stage_id', $leadTypes);
        }

        $newLeads = $newLeadsQuery->get();
        $respondedCount = 0;

        foreach ($newLeads as $lead) {
            $responded = $this->hasResponseAfterOutgoing(\App\Models\Email::class, $lead->id, $agentId)
                || $this->hasResponseAfterOutgoing(\App\Models\Call::class, $lead->id, $agentId)
                || $this->hasResponseAfterOutgoing(\App\Models\TextMessage::class, $lead->id, $agentId);

            if ($responded) {
                $respondedCount++;
            }
        }

        return $newLeads->count() > 0 ? round(($respondedCount / $newLeads->count()) * 100, 2) : 0;
    }

    private function getEmailResponseRate($agentId, $startDate, $endDate, $leadTypes)
    {
        return $this->getChannelResponseRate(\App\Models\Email::class, $agentId, $startDate, $endDate, $leadTypes);
    }

    private function getPhoneResponseRate($agentId, $startDate, $endDate, $leadTypes)
    {
        return $this->getChannelResponseRate(\App\Models\Call::class, $agentId, $startDate, $endDate, $leadTypes);
    }

    private function getTextResponseRate($agentId, $startDate, $endDate, $leadTypes)
    {
        return $this->getChannelResponseRate(\App\Models\TextMessage::class, $agentId, $startDate, $endDate, $leadTypes);
    }

    private function getChannelResponseRate($modelClass, $agentId, $startDate, $endDate, $leadTypes)
    {
        $newLeadsQuery = Person::where('assigned_user_id', $agentId)
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($leadTypes !== ['all']) {
            $newLeadsQuery->whereIn('stage_id', $leadTypes);
        }

        $newLeads = $newLeadsQuery->get();
        $contactedLeads = 0;
        $respondedLeads = 0;

        foreach ($newLeads as $lead) {
            // Agent has reached out
            $hasOutgoing = $modelClass::where('person_id', $lead->id)
                ->where('user_id', $agentId)
                ->where('is_incoming', false)
                ->exists();

            if ($hasOutgoing) {
                $contactedLeads++;

                // Did the lead respond after the agent reached out?
                if ($this->hasResponseAfterOutgoing($modelClass, $lead->id, $agentId)) {
                    $respondedLeads++;
                }
            }
        }

        return $contactedLeads > 0 ? round(($respondedLeads / $contactedLeads) * 100, 2) : 0;
    }

    private function hasResponseAfterOutgoing($modelClass, $leadId, $agentId)
    {
        // Get the first outgoing communication timestamp
        $firstOutgoing = $modelClass::where('person_id', $leadId)
            ->where('user_id', $agentId)
            ->where('is_incoming', false)
            ->orderBy('created_at')
            ->first();

        if (!$firstOutgoing) {
            return false;
        }

        // Check for any incoming communication after the first outgoing
        return $modelClass::where('person_id', $leadId)
            ->where('is_incoming', true)
            ->where('created_at', '>', $firstOutgoing->created_at)
            ->exists();
    }

    // Total Metrics (sum across all agents)
    private function getTotalMetrics($startDate, $endDate, $agentIds, $leadTypes)
    {
        $agentQuery = User::query();

        $agentQuery->where('role', RoleEnum::AGENT);

        if (!empty($agentIds)) {
            $agentQuery->whereIn('id', $agentIds);
        }

        $agents = $agentQuery->pluck('id');

        $totals = [
            'new_leads' => 0,
            'initially_assigned_leads' => 0,
            'currently_assigned_leads' => 0,
            'calls' => 0,
            'emails' => 0,
            'texts' => 0,
            'notes' => 0,
            'tasks_completed' => 0,
            'appointments' => 0,
            'appointments_set' => 0,
            'leads_not_acted_on' => 0,
            'leads_not_called' => 0,
            'leads_not_emailed' => 0,
            'leads_not_texted' => 0,
            'avg_speed_to_action' => 0,
            'avg_speed_to_first_call' => 0,
            'avg_speed_to_first_email' => 0,
            'avg_speed_to_first_text' => 0,
            'avg_contact_attempts' => 0,
            'avg_call_attempts' => 0,
            'avg_email_attempts' => 0,
            'avg_text_attempts' => 0,
            'response_rate' => 0,
            'email_response_rate' => 0,
            'phone_response_rate' => 0,
            'text_response_rate' => 0,
        ];

        foreach ($agents as $agentId) {
            $totals['new_leads'] += $this->getNewLeadsCount($agentId, $startDate, $endDate, $leadTypes);
            $totals['initially_assigned_leads'] += $this->getInitiallyAssignedLeadsCount($agentId, $startDate, $endDate, $leadTypes);
            $totals['currently_assigned_leads'] += $this->getCurrentlyAssignedLeadsCount($agentId, $leadTypes);
            $totals['calls'] += $this->getCallsCount($agentId, $startDate, $endDate);
            $totals['emails'] += $this->getEmailsCount($agentId, $startDate, $endDate);
            $totals['texts'] += $this->getTextsCount($agentId, $startDate, $endDate);
            $totals['notes'] += $this->getNotesCount($agentId, $startDate, $endDate);
            $totals['tasks_completed'] += $this->getTasksCompletedCount($agentId, $startDate, $endDate);
            $totals['appointments'] += $this->getAppointmentsCount($agentId, $startDate, $endDate);
            $totals['appointments_set'] += $this->getAppointmentsSetCount($agentId, $startDate, $endDate);
            $totals['leads_not_acted_on'] += $this->getLeadsNotActedOn($agentId, $startDate, $endDate, $leadTypes);
            $totals['leads_not_called'] += $this->getLeadsNotCalled($agentId, $startDate, $endDate, $leadTypes);
            $totals['leads_not_emailed'] += $this->getLeadsNotEmailed($agentId, $startDate, $endDate, $leadTypes);
            $totals['leads_not_texted'] += $this->getLeadsNotTexted($agentId, $startDate, $endDate, $leadTypes);
        }

        // Calculate averages for speed and attempt metrics
        $agentCount = $agents->count();
        if ($agentCount > 0) {
            $speedSum = $attemptSum = $responseSum = 0;

            foreach ($agents as $agentId) {
                $speedSum += $this->getAverageSpeedToAction($agentId, $startDate, $endDate, $leadTypes);
                $attemptSum += $this->getAverageContactAttempts($agentId, $startDate, $endDate, $leadTypes);
                $responseSum += $this->getResponseRate($agentId, $startDate, $endDate, $leadTypes);
            }

            $totals['avg_speed_to_action'] = round($speedSum / $agentCount, 2);
            $totals['avg_contact_attempts'] = round($attemptSum / $agentCount, 2);
            $totals['response_rate'] = round($responseSum / $agentCount, 2);
        }

        return $totals;
    }

    // Time Series Data for Charts
    private function getTimeSeriesData($startDate, $endDate, $agentIds, $leadTypes)
    {
        $timeSeriesData = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dayStart = $currentDate->copy()->startOfDay();
            $dayEnd = $currentDate->copy()->endOfDay();

            $dayData = [
                'date' => $currentDate->format('Y-m-d'),
                'new_leads' => $this->getNewLeadsCountForDay($dayStart, $dayEnd, $agentIds, $leadTypes),
                'calls' => $this->getCallsCountForDay($dayStart, $dayEnd, $agentIds),
                'emails' => $this->getEmailsCountForDay($dayStart, $dayEnd, $agentIds),
                'texts' => $this->getTextsCountForDay($dayStart, $dayEnd, $agentIds),
                'appointments_set' => $this->getAppointmentsSetCountForDay($dayStart, $dayEnd, $agentIds),
            ];

            $timeSeriesData[] = $dayData;
            $currentDate->addDay();
        }

        return $timeSeriesData;
    }

    private function getNewLeadsCountForDay($dayStart, $dayEnd, $agentIds, $leadTypes)
    {
        $query = Person::whereBetween('created_at', [$dayStart, $dayEnd]);

        if (!empty($agentIds)) {
            $query->whereIn('assigned_user_id', $agentIds);
        }

        if ($leadTypes !== ['all']) {
            $query->whereIn('stage_id', $leadTypes);
        }

        return $query->count();
    }

    private function getCallsCountForDay($dayStart, $dayEnd, $agentIds)
    {
        $query = Call::whereBetween('created_at', [$dayStart, $dayEnd]);

        if (!empty($agentIds)) {
            $query->whereIn('user_id', $agentIds);
        }

        return $query->count();
    }

    private function getEmailsCountForDay($dayStart, $dayEnd, $agentIds)
    {
        $query = Email::where('is_incoming', false)
            ->whereBetween('created_at', [$dayStart, $dayEnd]);

        if (!empty($agentIds)) {
            $query->whereIn('user_id', $agentIds);
        }

        return $query->count();
    }

    private function getTextsCountForDay($dayStart, $dayEnd, $agentIds)
    {
        $query = \App\Models\TextMessage::where('is_incoming', false)
            ->whereBetween('created_at', [$dayStart, $dayEnd]);

        if (!empty($agentIds)) {
            $query->whereIn('user_id', $agentIds);
        }

        return $query->count();
    }

    private function getAppointmentsSetCountForDay($dayStart, $dayEnd, $agentIds)
    {
        $query = Appointment::whereBetween('created_at', [$dayStart, $dayEnd]);

        if (!empty($agentIds)) {
            $query->whereIn('created_by_id', $agentIds);
        }

        return $query->count();
    }
}
