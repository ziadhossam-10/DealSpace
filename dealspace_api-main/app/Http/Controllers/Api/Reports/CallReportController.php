<?php

namespace App\Http\Controllers\Api\Reports;

use App\Enums\RoleEnum;
use App\Exports\CallReportExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Person;
use App\Models\Call;
use App\Models\Team;
use Maatwebsite\Excel\Facades\Excel;

class CallReportController extends Controller
{
    public function index(Request $request)
    {
        $startDate = Carbon::parse($request->start_date ?? now()->startOfMonth());
        $endDate = Carbon::parse($request->end_date ?? now()->endOfMonth());
        $agentIds = $request->agent_ids ?? [];
        $teamId = $request->team_id;

        // Get filtered agent IDs based on team
        $filteredAgentIds = $this->getFilteredAgentIds($agentIds, $teamId);

        $report = [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d')
            ],
            'team_info' => $this->getTeamInfo($teamId),
            'agents' => $this->getCallMetrics($startDate, $endDate, $filteredAgentIds),
            'totals' => $this->getTotalCallMetrics($startDate, $endDate, $filteredAgentIds),
            'time_series' => $this->getCallTimeSeriesData($startDate, $endDate, $filteredAgentIds),
            'summary_stats' => $this->getSummaryStats($startDate, $endDate, $filteredAgentIds)
        ];

        return response()->json($report);
    }

    public function export(Request $request)
    {
        $agentIds = $request->agent_ids ?? [];
        $teamId = $request->team_id;

        // Get filtered agent IDs based on team
        $filteredAgentIds = $this->getFilteredAgentIds($agentIds, $teamId);

        $params = [
            'start_date' => $request->start_date ?? now()->startOfMonth(),
            'end_date' => $request->end_date ?? now()->endOfMonth(),
            'agent_ids' => $filteredAgentIds ?? []
        ];

        $fileName = 'call_report_' . Carbon::now()->format('Ymd_His') . '.xlsx';
        return Excel::download(new CallReportExport($params), $fileName);
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

    private function getCallMetrics($startDate, $endDate, $agentIds)
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

                // Core Call Metrics
                'calls_made' => $this->getCallsMade($agent->id, $startDate, $endDate),
                'calls_connected' => $this->getCallsConnected($agent->id, $startDate, $endDate),
                'conversations' => $this->getConversations($agent->id, $startDate, $endDate),
                'calls_received' => $this->getCallsReceived($agent->id, $startDate, $endDate),
                'calls_missed' => $this->getCallsMissed($agent->id, $startDate, $endDate),

                // Duration Metrics
                'total_talk_time' => $this->getTotalTalkTime($agent->id, $startDate, $endDate),
                'avg_call_duration' => $this->getAverageCallDuration($agent->id, $startDate, $endDate),
                'avg_conversation_duration' => $this->getAverageConversationDuration($agent->id, $startDate, $endDate),

                // Response Time Metrics
                'avg_answer_time' => $this->getAverageAnswerTime($agent->id, $startDate, $endDate),

                // Performance Ratios
                'connection_rate' => $this->getConnectionRate($agent->id, $startDate, $endDate),
                'conversation_rate' => $this->getConversationRate($agent->id, $startDate, $endDate),
                'answer_rate' => $this->getAnswerRate($agent->id, $startDate, $endDate),

                // Contact Metrics
                'unique_contacts_called' => $this->getUniqueContactsCalled($agent->id, $startDate, $endDate),
                'contacts_reached' => $this->getContactsReached($agent->id, $startDate, $endDate),

                // Daily Averages
                'avg_calls_per_day' => $this->getAverageCallsPerDay($agent->id, $startDate, $endDate),
                'avg_talk_time_per_day' => $this->getAverageTalkTimePerDay($agent->id, $startDate, $endDate),

                // Call Outcomes Distribution
                'outcomes' => $this->getCallOutcomesDistribution($agent->id, $startDate, $endDate)
            ];
        }

        return $metrics;
    }

    // Core Call Metrics
    private function getCallsMade($agentId, $startDate, $endDate)
    {
        return Call::where('user_id', $agentId)
            ->where('is_incoming', false)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
    }

    private function getCallsConnected($agentId, $startDate, $endDate)
    {
        return Call::where('user_id', $agentId)
            ->where('is_incoming', false)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('duration', '>=', 60) // 1 minute or more
            ->count();
    }

    private function getConversations($agentId, $startDate, $endDate)
    {
        return Call::where('user_id', $agentId)
            ->where('is_incoming', false)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('duration', '>=', 120) // 2 minutes or more
            ->count();
    }

    private function getCallsReceived($agentId, $startDate, $endDate)
    {
        return Call::where('user_id', $agentId)
            ->where('is_incoming', true)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
    }

    private function getCallsMissed($agentId, $startDate, $endDate)
    {
        return Call::where('user_id', $agentId)
            ->where('is_incoming', true)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('duration', 0) // No duration means missed
            ->count();
    }

    // Duration Metrics
    private function getTotalTalkTime($agentId, $startDate, $endDate)
    {
        $totalSeconds = Call::where('user_id', $agentId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('duration');

        return [
            'seconds' => $totalSeconds,
            'formatted' => $this->formatDuration($totalSeconds)
        ];
    }

    private function getAverageCallDuration($agentId, $startDate, $endDate)
    {
        $avgSeconds = Call::where('user_id', $agentId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('duration', '>', 0)
            ->avg('duration');

        return [
            'seconds' => round($avgSeconds ?? 0, 2),
            'formatted' => $this->formatDuration($avgSeconds ?? 0)
        ];
    }

    private function getAverageConversationDuration($agentId, $startDate, $endDate)
    {
        $avgSeconds = Call::where('user_id', $agentId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('duration', '>=', 120) // Conversations only
            ->avg('duration');

        return [
            'seconds' => round($avgSeconds ?? 0, 2),
            'formatted' => $this->formatDuration($avgSeconds ?? 0)
        ];
    }

    // Response Time Metrics
    private function getAverageAnswerTime($agentId, $startDate, $endDate)
    {
        // This would require additional tracking in your system
        // For now, returning placeholder - you'd need to track when calls are answered
        return [
            'seconds' => 0,
            'formatted' => '00:00'
        ];
    }

    // Performance Ratios
    private function getConnectionRate($agentId, $startDate, $endDate)
    {
        $totalCalls = $this->getCallsMade($agentId, $startDate, $endDate);
        $connectedCalls = $this->getCallsConnected($agentId, $startDate, $endDate);

        return $totalCalls > 0 ? round(($connectedCalls / $totalCalls) * 100, 2) : 0;
    }

    private function getConversationRate($agentId, $startDate, $endDate)
    {
        $totalCalls = $this->getCallsMade($agentId, $startDate, $endDate);
        $conversations = $this->getConversations($agentId, $startDate, $endDate);

        return $totalCalls > 0 ? round(($conversations / $totalCalls) * 100, 2) : 0;
    }

    private function getAnswerRate($agentId, $startDate, $endDate)
    {
        $receivedCalls = $this->getCallsReceived($agentId, $startDate, $endDate);
        $missedCalls = $this->getCallsMissed($agentId, $startDate, $endDate);
        $answeredCalls = $receivedCalls - $missedCalls;

        return $receivedCalls > 0 ? round(($answeredCalls / $receivedCalls) * 100, 2) : 0;
    }

    // Contact Metrics
    private function getUniqueContactsCalled($agentId, $startDate, $endDate)
    {
        return Call::where('user_id', $agentId)
            ->where('is_incoming', false)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->distinct('person_id')
            ->count('person_id');
    }

    private function getContactsReached($agentId, $startDate, $endDate)
    {
        return Call::where('user_id', $agentId)
            ->where('is_incoming', false)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('duration', '>=', 60) // Connected calls
            ->distinct('person_id')
            ->count('person_id');
    }

    // Daily Averages
    private function getAverageCallsPerDay($agentId, $startDate, $endDate)
    {
        $totalCalls = $this->getCallsMade($agentId, $startDate, $endDate);
        $daysDiff = $startDate->diffInDays($endDate) + 1;

        return round($totalCalls / $daysDiff, 2);
    }

    private function getAverageTalkTimePerDay($agentId, $startDate, $endDate)
    {
        $totalTalkTime = $this->getTotalTalkTime($agentId, $startDate, $endDate)['seconds'];
        $daysDiff = $startDate->diffInDays($endDate) + 1;

        $avgSeconds = $totalTalkTime / $daysDiff;

        return [
            'seconds' => round($avgSeconds, 2),
            'formatted' => $this->formatDuration($avgSeconds)
        ];
    }

    // Call Outcomes Distribution
    private function getCallOutcomesDistribution($agentId, $startDate, $endDate)
    {
        $outcomes = Call::where('user_id', $agentId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('outcome, COUNT(*) as count')
            ->groupBy('outcome')
            ->pluck('count', 'outcome');

        return $outcomes->toArray();
    }

    // Total Metrics
    private function getTotalCallMetrics($startDate, $endDate, $agentIds)
    {
        $agentQuery = User::query();
        $agentQuery->where('role', RoleEnum::AGENT);

        if (!empty($agentIds)) {
            $agentQuery->whereIn('id', $agentIds);
        }

        $agents = $agentQuery->pluck('id');

        $totals = [
            'calls_made' => 0,
            'calls_connected' => 0,
            'conversations' => 0,
            'calls_received' => 0,
            'calls_missed' => 0,
            'total_talk_time' => ['seconds' => 0, 'formatted' => '00:00:00'],
            'unique_contacts_called' => 0,
            'contacts_reached' => 0,
            'connection_rate' => 0,
            'conversation_rate' => 0,
            'answer_rate' => 0
        ];

        foreach ($agents as $agentId) {
            $totals['calls_made'] += $this->getCallsMade($agentId, $startDate, $endDate);
            $totals['calls_connected'] += $this->getCallsConnected($agentId, $startDate, $endDate);
            $totals['conversations'] += $this->getConversations($agentId, $startDate, $endDate);
            $totals['calls_received'] += $this->getCallsReceived($agentId, $startDate, $endDate);
            $totals['calls_missed'] += $this->getCallsMissed($agentId, $startDate, $endDate);

            $agentTalkTime = $this->getTotalTalkTime($agentId, $startDate, $endDate);
            $totals['total_talk_time']['seconds'] += $agentTalkTime['seconds'];
        }

        // Format total talk time
        $totals['total_talk_time']['formatted'] = $this->formatDuration($totals['total_talk_time']['seconds']);

        // Calculate team-wide unique contacts
        $totals['unique_contacts_called'] = Call::whereIn('user_id', $agents)
            ->where('is_incoming', false)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->distinct('person_id')
            ->count('person_id');

        $totals['contacts_reached'] = Call::whereIn('user_id', $agents)
            ->where('is_incoming', false)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('duration', '>=', 60)
            ->distinct('person_id')
            ->count('person_id');

        // Calculate overall rates
        $totals['connection_rate'] = $totals['calls_made'] > 0 ?
            round(($totals['calls_connected'] / $totals['calls_made']) * 100, 2) : 0;

        $totals['conversation_rate'] = $totals['calls_made'] > 0 ?
            round(($totals['conversations'] / $totals['calls_made']) * 100, 2) : 0;

        $totals['answer_rate'] = $totals['calls_received'] > 0 ?
            round((($totals['calls_received'] - $totals['calls_missed']) / $totals['calls_received']) * 100, 2) : 0;

        return $totals;
    }

    // Time Series Data
    private function getCallTimeSeriesData($startDate, $endDate, $agentIds)
    {
        $timeSeriesData = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dayStart = $currentDate->copy()->startOfDay();
            $dayEnd = $currentDate->copy()->endOfDay();

            $dayData = [
                'date' => $currentDate->format('Y-m-d'),
                'calls_made' => $this->getCallsMadeForDay($dayStart, $dayEnd, $agentIds),
                'calls_connected' => $this->getCallsConnectedForDay($dayStart, $dayEnd, $agentIds),
                'conversations' => $this->getConversationsForDay($dayStart, $dayEnd, $agentIds),
                'calls_received' => $this->getCallsReceivedForDay($dayStart, $dayEnd, $agentIds),
                'total_talk_time' => $this->getTotalTalkTimeForDay($dayStart, $dayEnd, $agentIds),
            ];

            $timeSeriesData[] = $dayData;
            $currentDate->addDay();
        }

        return $timeSeriesData;
    }

    private function getCallsMadeForDay($dayStart, $dayEnd, $agentIds)
    {
        $query = Call::where('is_incoming', false)
            ->whereBetween('created_at', [$dayStart, $dayEnd]);

        if (!empty($agentIds)) {
            $query->whereIn('user_id', $agentIds);
        }

        return $query->count();
    }

    private function getCallsConnectedForDay($dayStart, $dayEnd, $agentIds)
    {
        $query = Call::where('is_incoming', false)
            ->whereBetween('created_at', [$dayStart, $dayEnd])
            ->where('duration', '>=', 60);

        if (!empty($agentIds)) {
            $query->whereIn('user_id', $agentIds);
        }

        return $query->count();
    }

    private function getConversationsForDay($dayStart, $dayEnd, $agentIds)
    {
        $query = Call::where('is_incoming', false)
            ->whereBetween('created_at', [$dayStart, $dayEnd])
            ->where('duration', '>=', 120);

        if (!empty($agentIds)) {
            $query->whereIn('user_id', $agentIds);
        }

        return $query->count();
    }

    private function getCallsReceivedForDay($dayStart, $dayEnd, $agentIds)
    {
        $query = Call::where('is_incoming', true)
            ->whereBetween('created_at', [$dayStart, $dayEnd]);

        if (!empty($agentIds)) {
            $query->whereIn('user_id', $agentIds);
        }

        return $query->count();
    }

    private function getTotalTalkTimeForDay($dayStart, $dayEnd, $agentIds)
    {
        $query = Call::whereBetween('created_at', [$dayStart, $dayEnd]);

        if (!empty($agentIds)) {
            $query->whereIn('user_id', $agentIds);
        }

        $totalSeconds = $query->sum('duration');

        return [
            'seconds' => $totalSeconds,
            'formatted' => $this->formatDuration($totalSeconds)
        ];
    }

    // Summary Statistics
    private function getSummaryStats($startDate, $endDate, $agentIds)
    {
        $agentQuery = User::query();
        $agentQuery->where('role', RoleEnum::AGENT);

        if (!empty($agentIds)) {
            $agentQuery->whereIn('id', $agentIds);
        }

        $agents = $agentQuery->get();
        $agentCount = $agents->count();

        if ($agentCount === 0) {
            return [
                'top_performer' => null,
                'team_averages' => []
            ];
        }

        // Find top performer by calls made
        $topPerformer = null;
        $maxCalls = 0;

        foreach ($agents as $agent) {
            $calls = $this->getCallsMade($agent->id, $startDate, $endDate);
            if ($calls > $maxCalls) {
                $maxCalls = $calls;
                $topPerformer = [
                    'agent_name' => $agent->name,
                    'calls_made' => $calls
                ];
            }
        }

        // Calculate team averages
        $totalCalls = $this->getTotalCallMetrics($startDate, $endDate, $agentIds);

        $teamAverages = [
            'avg_calls_per_agent' => round($totalCalls['calls_made'] / $agentCount, 2),
            'avg_connection_rate' => $totalCalls['connection_rate'],
            'avg_conversation_rate' => $totalCalls['conversation_rate'],
            'avg_talk_time_per_agent' => [
                'seconds' => round($totalCalls['total_talk_time']['seconds'] / $agentCount, 2),
                'formatted' => $this->formatDuration($totalCalls['total_talk_time']['seconds'] / $agentCount)
            ]
        ];

        return [
            'top_performer' => $topPerformer,
            'team_averages' => $teamAverages
        ];
    }

    // Helper Methods
    private function formatDuration($seconds)
    {
        if (!$seconds) {
            return '00:00:00';
        }

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
    }
}
