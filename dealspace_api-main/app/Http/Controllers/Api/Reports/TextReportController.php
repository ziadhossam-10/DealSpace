<?php

namespace App\Http\Controllers\Api\Reports;

use App\Enums\RoleEnum;
use App\Exports\TextReportExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Person;
use App\Models\TextMessage;
use App\Models\Team;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class TextReportController extends Controller
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
            'agents' => $this->getTextMetrics($startDate, $endDate, $filteredAgentIds),
            'totals' => $this->getTotalTextMetrics($startDate, $endDate, $filteredAgentIds),
            'time_series' => $this->getTextTimeSeriesData($startDate, $endDate, $filteredAgentIds),
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

        $fileName = 'text_report_' . Carbon::now()->format('Ymd_His') . '.xlsx';
        return Excel::download(new TextReportExport($params), $fileName);
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

    private function getTextMetrics($startDate, $endDate, $agentIds)
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

                // Core Text Metrics
                'texts_sent' => $this->getTextsSent($agent->id, $startDate, $endDate),
                'texts_received' => $this->getTextsReceived($agent->id, $startDate, $endDate),
                'texts_delivered' => $this->getTextsDelivered($agent->id, $startDate, $endDate),
                'texts_failed' => $this->getTextsFailed($agent->id, $startDate, $endDate),

                // Engagement Metrics
                'unique_contacts_texted' => $this->getUniqueContactsTexted($agent->id, $startDate, $endDate),
                'contacts_responded' => $this->getContactsResponded($agent->id, $startDate, $endDate),
                'conversations_initiated' => $this->getConversationsInitiated($agent->id, $startDate, $endDate),
                'conversations_active' => $this->getActiveConversations($agent->id, $startDate, $endDate),

                // Performance Ratios
                'delivery_rate' => $this->getDeliveryRate($agent->id, $startDate, $endDate),
                'response_rate' => $this->getResponseRate($agent->id, $startDate, $endDate),
                'engagement_rate' => $this->getEngagementRate($agent->id, $startDate, $endDate),

                // Quality Metrics
                'opt_outs' => $this->getOptOuts($agent->id, $startDate, $endDate),
                'carrier_filtered' => $this->getCarrierFiltered($agent->id, $startDate, $endDate),
                'other_errors' => $this->getOtherErrors($agent->id, $startDate, $endDate),

                // Daily Averages
                'avg_texts_per_day' => $this->getAverageTextsPerDay($agent->id, $startDate, $endDate),
                'avg_responses_per_day' => $this->getAverageResponsesPerDay($agent->id, $startDate, $endDate),

                // Time-based Metrics
                'avg_response_time' => $this->getAverageResponseTime($agent->id, $startDate, $endDate),
                'texts_by_hour' => $this->getTextsByHour($agent->id, $startDate, $endDate),

                // Message Length Metrics
                'avg_message_length' => $this->getAverageMessageLength($agent->id, $startDate, $endDate),
                'message_length_distribution' => $this->getMessageLengthDistribution($agent->id, $startDate, $endDate)
            ];
        }

        return $metrics;
    }

    // Core Text Metrics
    private function getTextsSent($agentId, $startDate, $endDate)
    {
        return TextMessage::where('user_id', $agentId)
            ->where('is_incoming', false)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
    }

    private function getTextsReceived($agentId, $startDate, $endDate)
    {
        return TextMessage::where('user_id', $agentId)
            ->where('is_incoming', true)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
    }

    private function getTextsDelivered($agentId, $startDate, $endDate)
    {
        return TextMessage::where('user_id', $agentId)
            ->where('is_incoming', false)
            ->whereBetween('created_at', [$startDate, $endDate])
            // ->whereNotIn('status', ['failed', 'undelivered', 'carrier_filtered'])
            ->count();
    }

    private function getTextsFailed($agentId, $startDate, $endDate)
    {
        return TextMessage::where('user_id', $agentId)
            ->where('is_incoming', false)
            ->whereBetween('created_at', [$startDate, $endDate])
            // ->whereIn('status', ['failed', 'undelivered'])
            ->count();
    }

    // Engagement Metrics
    private function getUniqueContactsTexted($agentId, $startDate, $endDate)
    {
        return TextMessage::where('user_id', $agentId)
            ->where('is_incoming', false)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->distinct('person_id')
            ->count('person_id');
    }

    private function getContactsResponded($agentId, $startDate, $endDate)
    {
        // Get unique contacts who sent at least one text back
        $contactsTexted = TextMessage::where('user_id', $agentId)
            ->where('is_incoming', false)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->pluck('person_id')
            ->unique();

        $contactsResponded = TextMessage::where('user_id', $agentId)
            ->where('is_incoming', true)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('person_id', $contactsTexted)
            ->distinct('person_id')
            ->count('person_id');

        return $contactsResponded;
    }

    private function getConversationsInitiated($agentId, $startDate, $endDate)
    {
        // Count distinct person_id where agent sent first text
        return TextMessage::where('user_id', $agentId)
            ->where('is_incoming', false)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotExists(function ($query) use ($agentId) {
                $query->select(DB::raw(1))
                    ->from('text_messages as tm2')
                    ->whereColumn('tm2.person_id', 'text_messages.person_id')
                    ->where('tm2.user_id', $agentId)
                    ->whereColumn('tm2.created_at', '<', 'text_messages.created_at');
            })
            ->distinct('person_id')
            ->count('person_id');
    }

    private function getActiveConversations($agentId, $startDate, $endDate)
    {
        // Conversations where both parties sent at least one message
        return TextMessage::where('user_id', $agentId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereExists(function ($query) use ($agentId) {
                $query->select(DB::raw(1))
                    ->from('text_messages as tm_out')
                    ->whereColumn('tm_out.person_id', 'text_messages.person_id')
                    ->where('tm_out.user_id', $agentId)
                    ->where('tm_out.is_incoming', false);
            })
            ->whereExists(function ($query) use ($agentId) {
                $query->select(DB::raw(1))
                    ->from('text_messages as tm_in')
                    ->whereColumn('tm_in.person_id', 'text_messages.person_id')
                    ->where('tm_in.user_id', $agentId)
                    ->where('tm_in.is_incoming', true);
            })
            ->distinct('person_id')
            ->count('person_id');
    }

    // Performance Ratios
    private function getDeliveryRate($agentId, $startDate, $endDate)
    {
        $totalSent = $this->getTextsSent($agentId, $startDate, $endDate);
        $delivered = $this->getTextsDelivered($agentId, $startDate, $endDate);

        return $totalSent > 0 ? round(($delivered / $totalSent) * 100, 2) : 0;
    }

    private function getResponseRate($agentId, $startDate, $endDate)
    {
        $contactsTexted = $this->getUniqueContactsTexted($agentId, $startDate, $endDate);
        $contactsResponded = $this->getContactsResponded($agentId, $startDate, $endDate);

        return $contactsTexted > 0 ? round(($contactsResponded / $contactsTexted) * 100, 2) : 0;
    }

    private function getEngagementRate($agentId, $startDate, $endDate)
    {
        $contactsTexted = $this->getUniqueContactsTexted($agentId, $startDate, $endDate);
        $activeConversations = $this->getActiveConversations($agentId, $startDate, $endDate);

        return $contactsTexted > 0 ? round(($activeConversations / $contactsTexted) * 100, 2) : 0;
    }

    // Quality Metrics
    private function getOptOuts($agentId, $startDate, $endDate)
    {
        return TextMessage::where('user_id', $agentId)
            ->where('is_incoming', true)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where(function ($query) {
                $query->where('message', 'like', '%STOP%')
                    ->orWhere('message', 'like', '%UNSUBSCRIBE%')
                    ->orWhere('message', 'like', '%OPT OUT%')
                    ->orWhere('message', 'like', '%OPTOUT%');
            })
            ->count();
    }

    private function getCarrierFiltered($agentId, $startDate, $endDate)
    {
        return TextMessage::where('user_id', $agentId)
            ->where('is_incoming', false)
            ->whereBetween('created_at', [$startDate, $endDate])
            // ->where('status', 'carrier_filtered')
            ->count();
    }

    private function getOtherErrors($agentId, $startDate, $endDate)
    {
        return TextMessage::where('user_id', $agentId)
            ->where('is_incoming', false)
            ->whereBetween('created_at', [$startDate, $endDate])
            // ->whereIn('status', ['invalid_number', 'landline', 'blocked'])
            ->count();
    }

    // Daily Averages
    private function getAverageTextsPerDay($agentId, $startDate, $endDate)
    {
        $totalTexts = $this->getTextsSent($agentId, $startDate, $endDate);
        $daysDiff = $startDate->diffInDays($endDate) + 1;

        return round($totalTexts / $daysDiff, 2);
    }

    private function getAverageResponsesPerDay($agentId, $startDate, $endDate)
    {
        $totalResponses = $this->getTextsReceived($agentId, $startDate, $endDate);
        $daysDiff = $startDate->diffInDays($endDate) + 1;

        return round($totalResponses / $daysDiff, 2);
    }

    // Time-based Metrics
    private function getAverageResponseTime($agentId, $startDate, $endDate)
    {
        // This would require more complex logic to track response times
        // For now, returning placeholder
        return [
            'minutes' => 0,
            'formatted' => '00:00'
        ];
    }

    private function getTextsByHour($agentId, $startDate, $endDate)
    {
        $textsByHour = TextMessage::where('user_id', $agentId)
            ->where('is_incoming', false)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('count', 'hour');

        // Fill in missing hours with 0
        $result = [];
        for ($i = 0; $i < 24; $i++) {
            $result[$i] = $textsByHour->get($i, 0);
        }

        return $result;
    }

    // Message Length Metrics
    private function getAverageMessageLength($agentId, $startDate, $endDate)
    {
        $avgLength = TextMessage::where('user_id', $agentId)
            ->where('is_incoming', false)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('AVG(CHAR_LENGTH(message)) as avg_length')
            ->value('avg_length');

        return round($avgLength ?? 0, 2);
    }

    private function getMessageLengthDistribution($agentId, $startDate, $endDate)
    {
        $messages = TextMessage::where('user_id', $agentId)
            ->where('is_incoming', false)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('
                CASE
                    WHEN CHAR_LENGTH(message) <= 50 THEN "short"
                    WHEN CHAR_LENGTH(message) <= 100 THEN "medium"
                    WHEN CHAR_LENGTH(message) <= 160 THEN "long"
                    ELSE "very_long"
                END as length_category,
                COUNT(*) as count
            ')
            ->groupBy('length_category')
            ->pluck('count', 'length_category');

        return [
            'short' => $messages->get('short', 0),     // <= 50 chars
            'medium' => $messages->get('medium', 0),   // 51-100 chars
            'long' => $messages->get('long', 0),       // 101-160 chars
            'very_long' => $messages->get('very_long', 0) // > 160 chars
        ];
    }

    // Total Metrics
    private function getTotalTextMetrics($startDate, $endDate, $agentIds)
    {
        $agentQuery = User::query();
        $agentQuery->where('role', RoleEnum::AGENT);

        if (!empty($agentIds)) {
            $agentQuery->whereIn('id', $agentIds);
        }

        $agents = $agentQuery->pluck('id');

        $totals = [
            'texts_sent' => 0,
            'texts_received' => 0,
            'texts_delivered' => 0,
            'texts_failed' => 0,
            'unique_contacts_texted' => 0,
            'contacts_responded' => 0,
            'conversations_initiated' => 0,
            'conversations_active' => 0,
            'opt_outs' => 0,
            'carrier_filtered' => 0,
            'other_errors' => 0,
            'delivery_rate' => 0,
            'response_rate' => 0,
            'engagement_rate' => 0
        ];

        foreach ($agents as $agentId) {
            $totals['texts_sent'] += $this->getTextsSent($agentId, $startDate, $endDate);
            $totals['texts_received'] += $this->getTextsReceived($agentId, $startDate, $endDate);
            $totals['texts_delivered'] += $this->getTextsDelivered($agentId, $startDate, $endDate);
            $totals['texts_failed'] += $this->getTextsFailed($agentId, $startDate, $endDate);
            $totals['conversations_initiated'] += $this->getConversationsInitiated($agentId, $startDate, $endDate);
            $totals['opt_outs'] += $this->getOptOuts($agentId, $startDate, $endDate);
            $totals['carrier_filtered'] += $this->getCarrierFiltered($agentId, $startDate, $endDate);
            $totals['other_errors'] += $this->getOtherErrors($agentId, $startDate, $endDate);
        }

        // Calculate team-wide unique contacts
        $totals['unique_contacts_texted'] = TextMessage::whereIn('user_id', $agents)
            ->where('is_incoming', false)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->distinct('person_id')
            ->count('person_id');

        // Calculate team-wide contacts who responded
        $contactsTexted = TextMessage::whereIn('user_id', $agents)
            ->where('is_incoming', false)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->pluck('person_id')
            ->unique();

        $totals['contacts_responded'] = TextMessage::whereIn('user_id', $agents)
            ->where('is_incoming', true)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('person_id', $contactsTexted)
            ->distinct('person_id')
            ->count('person_id');

        // Calculate team-wide active conversations
        $totals['conversations_active'] = TextMessage::whereIn('user_id', $agents)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereExists(function ($query) use ($agents) {
                $query->select(DB::raw(1))
                    ->from('text_messages as tm_out')
                    ->whereColumn('tm_out.person_id', 'text_messages.person_id')
                    ->whereIn('tm_out.user_id', $agents)
                    ->where('tm_out.is_incoming', false);
            })
            ->whereExists(function ($query) use ($agents) {
                $query->select(DB::raw(1))
                    ->from('text_messages as tm_in')
                    ->whereColumn('tm_in.person_id', 'text_messages.person_id')
                    ->whereIn('tm_in.user_id', $agents)
                    ->where('tm_in.is_incoming', true);
            })
            ->distinct('person_id')
            ->count('person_id');

        // Calculate overall rates
        $totals['delivery_rate'] = $totals['texts_sent'] > 0 ?
            round(($totals['texts_delivered'] / $totals['texts_sent']) * 100, 2) : 0;

        $totals['response_rate'] = $totals['unique_contacts_texted'] > 0 ?
            round(($totals['contacts_responded'] / $totals['unique_contacts_texted']) * 100, 2) : 0;

        $totals['engagement_rate'] = $totals['unique_contacts_texted'] > 0 ?
            round(($totals['conversations_active'] / $totals['unique_contacts_texted']) * 100, 2) : 0;

        return $totals;
    }

    // Time Series Data
    private function getTextTimeSeriesData($startDate, $endDate, $agentIds)
    {
        $timeSeriesData = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dayStart = $currentDate->copy()->startOfDay();
            $dayEnd = $currentDate->copy()->endOfDay();

            $dayData = [
                'date' => $currentDate->format('Y-m-d'),
                'texts_sent' => $this->getTextsSentForDay($dayStart, $dayEnd, $agentIds),
                'texts_received' => $this->getTextsReceivedForDay($dayStart, $dayEnd, $agentIds),
                'texts_delivered' => $this->getTextsDeliveredForDay($dayStart, $dayEnd, $agentIds),
                'unique_contacts_texted' => $this->getUniqueContactsTextedForDay($dayStart, $dayEnd, $agentIds),
                'delivery_rate' => 0 // Will be calculated after getting sent/delivered
            ];

            // Calculate delivery rate for the day
            if ($dayData['texts_sent'] > 0) {
                $dayData['delivery_rate'] = round(($dayData['texts_delivered'] / $dayData['texts_sent']) * 100, 2);
            }

            $timeSeriesData[] = $dayData;
            $currentDate->addDay();
        }

        return $timeSeriesData;
    }

    private function getTextsSentForDay($dayStart, $dayEnd, $agentIds)
    {
        $query = TextMessage::where('is_incoming', false)
            ->whereBetween('created_at', [$dayStart, $dayEnd]);

        if (!empty($agentIds)) {
            $query->whereIn('user_id', $agentIds);
        }

        return $query->count();
    }

    private function getTextsReceivedForDay($dayStart, $dayEnd, $agentIds)
    {
        $query = TextMessage::where('is_incoming', true)
            ->whereBetween('created_at', [$dayStart, $dayEnd]);

        if (!empty($agentIds)) {
            $query->whereIn('user_id', $agentIds);
        }

        return $query->count();
    }

    private function getTextsDeliveredForDay($dayStart, $dayEnd, $agentIds)
    {
        $query = TextMessage::where('is_incoming', false)
            ->whereBetween('created_at', [$dayStart, $dayEnd]);
        // ->whereNotIn('status', ['failed', 'undelivered', 'carrier_filtered']);

        if (!empty($agentIds)) {
            $query->whereIn('user_id', $agentIds);
        }

        return $query->count();
    }

    private function getUniqueContactsTextedForDay($dayStart, $dayEnd, $agentIds)
    {
        $query = TextMessage::where('is_incoming', false)
            ->whereBetween('created_at', [$dayStart, $dayEnd]);

        if (!empty($agentIds)) {
            $query->whereIn('user_id', $agentIds);
        }

        return $query->distinct('person_id')->count('person_id');
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

        // Find top performer by texts sent
        $topPerformer = null;
        $maxTexts = 0;

        foreach ($agents as $agent) {
            $texts = $this->getTextsSent($agent->id, $startDate, $endDate);
            if ($texts > $maxTexts) {
                $maxTexts = $texts;
                $topPerformer = [
                    'agent_name' => $agent->name,
                    'texts_sent' => $texts
                ];
            }
        }

        // Calculate team averages
        $totalTexts = $this->getTotalTextMetrics($startDate, $endDate, $agentIds);

        $teamAverages = [
            'avg_texts_per_agent' => round($totalTexts['texts_sent'] / $agentCount, 2),
            'avg_delivery_rate' => $totalTexts['delivery_rate'],
            'avg_response_rate' => $totalTexts['response_rate'],
            'avg_engagement_rate' => $totalTexts['engagement_rate']
        ];

        return [
            'top_performer' => $topPerformer,
            'team_averages' => $teamAverages
        ];
    }

    // Helper Methods
    private function formatTime($minutes)
    {
        if (!$minutes) {
            return '00:00';
        }

        $hours = floor($minutes / 60);
        $mins = $minutes % 60;

        return sprintf('%02d:%02d', $hours, $mins);
    }
}
