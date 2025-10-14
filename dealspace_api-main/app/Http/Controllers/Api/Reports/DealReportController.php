<?php

namespace App\Http\Controllers\Api\Reports;

use App\Enums\RoleEnum;
use App\Exports\DealReportExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Person;
use App\Models\Deal;
use App\Models\Team;
use App\Models\DealStage;
use App\Models\DealType;
use Maatwebsite\Excel\Facades\Excel;

class DealReportController extends Controller
{
    public function index(Request $request)
    {
        $startDate = Carbon::parse($request->start_date ?? now()->startOfMonth());
        $endDate = Carbon::parse($request->end_date ?? now()->endOfMonth());
        $agentIds = $request->agent_ids ?? [];
        $teamId = $request->team_id;
        $stageId = $request->stage_id;
        $typeId = $request->type_id;
        $status = $request->status ?? 'all'; // all, current, archived

        // Get filtered agent IDs based on team
        $filteredAgentIds = $this->getFilteredAgentIds($agentIds, $teamId);

        $report = [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d')
            ],
            'team_info' => $this->getTeamInfo($teamId),
            'filters' => [
                'stage_id' => $stageId,
                'type_id' => $typeId,
                'status' => $status
            ],
            'agents' => $this->getDealMetrics($startDate, $endDate, $filteredAgentIds, $stageId, $typeId, $status),
            'totals' => $this->getTotalDealMetrics($startDate, $endDate, $filteredAgentIds, $stageId, $typeId, $status),
            'time_series' => $this->getDealTimeSeriesData($startDate, $endDate, $filteredAgentIds, $stageId, $typeId, $status),
            'summary_stats' => $this->getSummaryStats($startDate, $endDate, $filteredAgentIds, $stageId, $typeId, $status),
            'stage_averages' => $this->getStageAverages($filteredAgentIds, $stageId, $typeId),
            'source_breakdown' => $this->getSourceBreakdown($startDate, $endDate, $filteredAgentIds, $stageId, $typeId, $status),
            'deals_list' => $this->getDealsList($startDate, $endDate, $filteredAgentIds, $stageId, $typeId, $status)
        ];

        return response()->json($report);
    }

    public function export(Request $request)
    {
        $agentIds = $request->agent_ids ?? [];
        $teamId = $request->team_id;
        $stageId = $request->stage_id;
        $typeId = $request->type_id;
        $status = $request->status ?? 'all';

        // Get filtered agent IDs based on team
        $filteredAgentIds = $this->getFilteredAgentIds($agentIds, $teamId);

        $params = [
            'start_date' => $request->start_date ?? now()->startOfMonth(),
            'end_date' => $request->end_date ?? now()->endOfMonth(),
            'agent_ids' => $filteredAgentIds ?? [],
            'stage_id' => $stageId,
            'type_id' => $typeId,
            'status' => $status
        ];

        $fileName = 'deal_report_' . Carbon::now()->format('Ymd_His') . '.xlsx';
        return Excel::download(new DealReportExport($params), $fileName);
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

    private function getDealMetrics($startDate, $endDate, $agentIds, $stageId = null, $typeId = null, $status = 'all')
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

                // Core Deal Metrics
                'deals_created' => $this->getDealsCreated($agent->id, $startDate, $endDate, $stageId, $typeId, $status),
                'deals_closed_won' => $this->getDealsClosedWon($agent->id, $startDate, $endDate, $stageId, $typeId),
                'deals_closed_lost' => $this->getDealsClosedLost($agent->id, $startDate, $endDate, $stageId, $typeId),
                'deals_in_pipeline' => $this->getDealsInPipeline($agent->id, $stageId, $typeId),

                // Value Metrics
                'total_deal_value' => $this->getTotalDealValue($agent->id, $startDate, $endDate, $stageId, $typeId, $status),
                'closed_deal_value' => $this->getClosedDealValue($agent->id, $startDate, $endDate, $stageId, $typeId),
                'pipeline_value' => $this->getPipelineValue($agent->id, $stageId, $typeId),
                'avg_deal_size' => $this->getAverageDealSize($agent->id, $startDate, $endDate, $stageId, $typeId, $status),

                // Commission Metrics
                'total_commission' => $this->getTotalCommission($agent->id, $startDate, $endDate, $stageId, $typeId),
                'agent_commission' => $this->getAgentCommission($agent->id, $startDate, $endDate, $stageId, $typeId),
                'team_commission' => $this->getTeamCommission($agent->id, $startDate, $endDate, $stageId, $typeId),

                // Performance Ratios
                'close_rate' => $this->getCloseRate($agent->id, $startDate, $endDate, $stageId, $typeId),
                'win_rate' => $this->getWinRate($agent->id, $startDate, $endDate, $stageId, $typeId),

                // Time Metrics
                'avg_time_to_close' => $this->getAverageTimeToClose($agent->id, $startDate, $endDate, $stageId, $typeId),
                'avg_time_in_current_stage' => $this->getAverageTimeInCurrentStage($agent->id, $stageId, $typeId),

                // Daily/Period Averages
                'avg_deals_per_day' => $this->getAverageDealsPerDay($agent->id, $startDate, $endDate, $stageId, $typeId, $status),
                'avg_value_per_day' => $this->getAverageValuePerDay($agent->id, $startDate, $endDate, $stageId, $typeId, $status),

                // Deal Type/Stage Distribution
                'deals_by_stage' => $this->getDealsByStage($agent->id, $startDate, $endDate, $typeId, $status),
                'deals_by_type' => $this->getDealsByType($agent->id, $startDate, $endDate, $stageId, $status)
            ];
        }

        return $metrics;
    }

    // Core Deal Metrics
    private function getDealsCreated($agentId, $startDate, $endDate, $stageId = null, $typeId = null, $status = 'all')
    {
        $query = Deal::whereHas('users', function ($query) use ($agentId) {
            $query->where('users.id', $agentId);
        })->whereBetween('created_at', [$startDate, $endDate]);

        if ($stageId) {
            $query->where('stage_id', $stageId);
        }

        if ($typeId) {
            $query->where('type_id', $typeId);
        }

        // Apply status filter if needed (assuming you have archived deals logic)
        if ($status === 'current') {
            // Add logic for current deals only
        } elseif ($status === 'archived') {
            // Add logic for archived deals only
        }

        return $query->count();
    }

    private function getDealsClosedWon($agentId, $startDate, $endDate, $stageId = null, $typeId = null)
    {
        $query = Deal::whereHas('users', function ($query) use ($agentId) {
            $query->where('users.id', $agentId);
        })->whereHas('stage', function ($query) {
            $query->where('name', 'LIKE', '%won%')->orWhere('name', 'LIKE', '%closed%')->orWhere('name', 'LIKE', '%success%');
        })->whereBetween('updated_at', [$startDate, $endDate]);

        if ($stageId) {
            $query->where('stage_id', $stageId);
        }

        if ($typeId) {
            $query->where('type_id', $typeId);
        }

        return $query->count();
    }

    private function getDealsClosedLost($agentId, $startDate, $endDate, $stageId = null, $typeId = null)
    {
        $query = Deal::whereHas('users', function ($query) use ($agentId) {
            $query->where('users.id', $agentId);
        })->whereHas('stage', function ($query) {
            $query->where('name', 'LIKE', '%lost%')->orWhere('name', 'LIKE', '%rejected%')->orWhere('name', 'LIKE', '%cancelled%');
        })->whereBetween('updated_at', [$startDate, $endDate]);

        if ($stageId) {
            $query->where('stage_id', $stageId);
        }

        if ($typeId) {
            $query->where('type_id', $typeId);
        }

        return $query->count();
    }

    private function getDealsInPipeline($agentId, $stageId = null, $typeId = null)
    {
        $query = Deal::whereHas('users', function ($query) use ($agentId) {
            $query->where('users.id', $agentId);
        })->whereHas('stage', function ($query) {
            $query->where('name', 'NOT LIKE', '%won%')
                ->where('name', 'NOT LIKE', '%lost%')
                ->where('name', 'NOT LIKE', '%closed%')
                ->where('name', 'NOT LIKE', '%rejected%')
                ->where('name', 'NOT LIKE', '%cancelled%');
        });

        if ($stageId) {
            $query->where('stage_id', $stageId);
        }

        if ($typeId) {
            $query->where('type_id', $typeId);
        }

        return $query->count();
    }

    // Value Metrics
    private function getTotalDealValue($agentId, $startDate, $endDate, $stageId = null, $typeId = null, $status = 'all')
    {
        $query = Deal::whereHas('users', function ($query) use ($agentId) {
            $query->where('users.id', $agentId);
        })->whereBetween('created_at', [$startDate, $endDate]);

        if ($stageId) {
            $query->where('stage_id', $stageId);
        }

        if ($typeId) {
            $query->where('type_id', $typeId);
        }

        return $query->sum('price') ?? 0;
    }

    private function getClosedDealValue($agentId, $startDate, $endDate, $stageId = null, $typeId = null)
    {
        $query = Deal::whereHas('users', function ($query) use ($agentId) {
            $query->where('users.id', $agentId);
        })->whereHas('stage', function ($query) {
            $query->where('name', 'LIKE', '%won%')->orWhere('name', 'LIKE', '%closed%')->orWhere('name', 'LIKE', '%success%');
        })->whereBetween('updated_at', [$startDate, $endDate]);

        if ($stageId) {
            $query->where('stage_id', $stageId);
        }

        if ($typeId) {
            $query->where('type_id', $typeId);
        }

        return $query->sum('price') ?? 0;
    }

    private function getPipelineValue($agentId, $stageId = null, $typeId = null)
    {
        $query = Deal::whereHas('users', function ($query) use ($agentId) {
            $query->where('users.id', $agentId);
        })->whereHas('stage', function ($query) {
            $query->where('name', 'NOT LIKE', '%won%')
                ->where('name', 'NOT LIKE', '%lost%')
                ->where('name', 'NOT LIKE', '%closed%')
                ->where('name', 'NOT LIKE', '%rejected%')
                ->where('name', 'NOT LIKE', '%cancelled%');
        });

        if ($stageId) {
            $query->where('stage_id', $stageId);
        }

        if ($typeId) {
            $query->where('type_id', $typeId);
        }

        return $query->sum('price') ?? 0;
    }

    private function getAverageDealSize($agentId, $startDate, $endDate, $stageId = null, $typeId = null, $status = 'all')
    {
        $query = Deal::whereHas('users', function ($qu) use ($agentId) {
            $qu->where('users.id', $agentId);
        })->whereBetween('created_at', [$startDate, $endDate])->where('price', '>', 0);

        if ($stageId) {
            $query->where('stage_id', $stageId);
        }

        if ($typeId) {
            $query->where('type_id', $typeId);
        }

        return round($query->avg('price') ?? 0, 2);
    }

    // Commission Metrics
    private function getTotalCommission($agentId, $startDate, $endDate, $stageId = null, $typeId = null)
    {
        $query = Deal::whereHas('users', function ($query) use ($agentId) {
            $query->where('users.id', $agentId);
        })->whereHas('stage', function ($query) {
            $query->where('name', 'LIKE', '%won%')->orWhere('name', 'LIKE', '%closed%')->orWhere('name', 'LIKE', '%success%');
        })->whereBetween('updated_at', [$startDate, $endDate]);

        if ($stageId) {
            $query->where('stage_id', $stageId);
        }

        if ($typeId) {
            $query->where('type_id', $typeId);
        }

        return $query->sum('commission_value') ?? 0;
    }

    private function getAgentCommission($agentId, $startDate, $endDate, $stageId = null, $typeId = null)
    {
        $query = Deal::whereHas('users', function ($query) use ($agentId) {
            $query->where('users.id', $agentId);
        })->whereHas('stage', function ($query) {
            $query->where('name', 'LIKE', '%won%')->orWhere('name', 'LIKE', '%closed%')->orWhere('name', 'LIKE', '%success%');
        })->whereBetween('updated_at', [$startDate, $endDate]);

        if ($stageId) {
            $query->where('stage_id', $stageId);
        }

        if ($typeId) {
            $query->where('type_id', $typeId);
        }

        return $query->sum('agent_commission') ?? 0;
    }

    private function getTeamCommission($agentId, $startDate, $endDate, $stageId = null, $typeId = null)
    {
        $query = Deal::whereHas('users', function ($query) use ($agentId) {
            $query->where('users.id', $agentId);
        })->whereHas('stage', function ($query) {
            $query->where('name', 'LIKE', '%won%')->orWhere('name', 'LIKE', '%closed%')->orWhere('name', 'LIKE', '%success%');
        })->whereBetween('updated_at', [$startDate, $endDate]);

        if ($stageId) {
            $query->where('stage_id', $stageId);
        }

        if ($typeId) {
            $query->where('type_id', $typeId);
        }

        return $query->sum('team_commission') ?? 0;
    }

    // Performance Ratios
    private function getCloseRate($agentId, $startDate, $endDate, $stageId = null, $typeId = null)
    {
        $totalDeals = $this->getDealsCreated($agentId, $startDate, $endDate, $stageId, $typeId);
        $closedDeals = $this->getDealsClosedWon($agentId, $startDate, $endDate, $stageId, $typeId);

        return $totalDeals > 0 ? round(($closedDeals / $totalDeals) * 100, 2) : 0;
    }

    private function getWinRate($agentId, $startDate, $endDate, $stageId = null, $typeId = null)
    {
        $closedWon = $this->getDealsClosedWon($agentId, $startDate, $endDate, $stageId, $typeId);
        $closedLost = $this->getDealsClosedLost($agentId, $startDate, $endDate, $stageId, $typeId);
        $totalClosed = $closedWon + $closedLost;

        return $totalClosed > 0 ? round(($closedWon / $totalClosed) * 100, 2) : 0;
    }

    // Time Metrics
    private function getAverageTimeToClose($agentId, $startDate, $endDate, $stageId = null, $typeId = null)
    {
        $query = Deal::whereHas('users', function ($query) use ($agentId) {
            $query->where('users.id', $agentId);
        })->whereHas('stage', function ($query) {
            $query->where('name', 'LIKE', '%won%')->orWhere('name', 'LIKE', '%closed%')->orWhere('name', 'LIKE', '%success%');
        })->whereBetween('updated_at', [$startDate, $endDate]);

        if ($stageId) {
            $query->where('stage_id', $stageId);
        }

        if ($typeId) {
            $query->where('type_id', $typeId);
        }

        $deals = $query->get();
        $totalDays = 0;
        $count = 0;

        foreach ($deals as $deal) {
            $days = $deal->created_at->diffInDays($deal->updated_at);
            $totalDays += $days;
            $count++;
        }

        return $count > 0 ? round($totalDays / $count, 1) : 0;
    }

    private function getAverageTimeInCurrentStage($agentId, $stageId = null, $typeId = null)
    {
        $query = Deal::whereHas('users', function ($query) use ($agentId) {
            $query->where('users.id', $agentId);
        })->whereHas('stage', function ($query) {
            $query->where('name', 'NOT LIKE', '%won%')
                ->where('name', 'NOT LIKE', '%lost%')
                ->where('name', 'NOT LIKE', '%closed%')
                ->where('name', 'NOT LIKE', '%rejected%')
                ->where('name', 'NOT LIKE', '%cancelled%');
        });

        if ($stageId) {
            $query->where('stage_id', $stageId);
        }

        if ($typeId) {
            $query->where('type_id', $typeId);
        }

        $deals = $query->get();
        $totalDays = 0;
        $count = 0;

        foreach ($deals as $deal) {
            $days = $deal->updated_at->diffInDays(now());
            $totalDays += $days;
            $count++;
        }

        return $count > 0 ? round($totalDays / $count, 1) : 0;
    }

    // Daily Averages
    private function getAverageDealsPerDay($agentId, $startDate, $endDate, $stageId = null, $typeId = null, $status = 'all')
    {
        $totalDeals = $this->getDealsCreated($agentId, $startDate, $endDate, $stageId, $typeId, $status);
        $daysDiff = $startDate->diffInDays($endDate) + 1;

        return round($totalDeals / $daysDiff, 2);
    }

    private function getAverageValuePerDay($agentId, $startDate, $endDate, $stageId = null, $typeId = null, $status = 'all')
    {
        $totalValue = $this->getTotalDealValue($agentId, $startDate, $endDate, $stageId, $typeId, $status);
        $daysDiff = $startDate->diffInDays($endDate) + 1;

        return round($totalValue / $daysDiff, 2);
    }

    // Distribution Methods
    private function getDealsByStage($agentId, $startDate, $endDate, $typeId = null, $status = 'all')
    {
        $query = Deal::whereHas('users', function ($query) use ($agentId) {
            $query->where('users.id', $agentId);
        })->whereBetween('created_at', [$startDate, $endDate])
            ->with('stage');

        if ($typeId) {
            $query->where('type_id', $typeId);
        }

        $deals = $query->get();
        $distribution = [];

        foreach ($deals as $deal) {
            $stageName = $deal->stage->name ?? 'Unknown';
            $distribution[$stageName] = ($distribution[$stageName] ?? 0) + 1;
        }

        return $distribution;
    }

    private function getDealsByType($agentId, $startDate, $endDate, $stageId = null, $status = 'all')
    {
        $query = Deal::whereHas('users', function ($query) use ($agentId) {
            $query->where('users.id', $agentId);
        })->whereBetween('created_at', [$startDate, $endDate])
            ->with('type');

        if ($stageId) {
            $query->where('stage_id', $stageId);
        }

        $deals = $query->get();
        $distribution = [];

        foreach ($deals as $deal) {
            $typeName = $deal->type->name ?? 'Unknown';
            $distribution[$typeName] = ($distribution[$typeName] ?? 0) + 1;
        }

        return $distribution;
    }

    // Total Metrics
    private function getTotalDealMetrics($startDate, $endDate, $agentIds, $stageId = null, $typeId = null, $status = 'all')
    {
        $agentQuery = User::query();
        $agentQuery->where('role', RoleEnum::AGENT);

        if (!empty($agentIds)) {
            $agentQuery->whereIn('id', $agentIds);
        }

        $agents = $agentQuery->pluck('id');

        $totals = [
            'deals_created' => 0,
            'deals_closed_won' => 0,
            'deals_closed_lost' => 0,
            'deals_in_pipeline' => 0,
            'total_deal_value' => 0,
            'closed_deal_value' => 0,
            'pipeline_value' => 0,
            'total_commission' => 0,
            'agent_commission' => 0,
            'team_commission' => 0,
            'close_rate' => 0,
            'win_rate' => 0
        ];

        foreach ($agents as $agentId) {
            $totals['deals_created'] += $this->getDealsCreated($agentId, $startDate, $endDate, $stageId, $typeId, $status);
            $totals['deals_closed_won'] += $this->getDealsClosedWon($agentId, $startDate, $endDate, $stageId, $typeId);
            $totals['deals_closed_lost'] += $this->getDealsClosedLost($agentId, $startDate, $endDate, $stageId, $typeId);
            $totals['deals_in_pipeline'] += $this->getDealsInPipeline($agentId, $stageId, $typeId);
            $totals['total_deal_value'] += $this->getTotalDealValue($agentId, $startDate, $endDate, $stageId, $typeId, $status);
            $totals['closed_deal_value'] += $this->getClosedDealValue($agentId, $startDate, $endDate, $stageId, $typeId);
            $totals['pipeline_value'] += $this->getPipelineValue($agentId, $stageId, $typeId);
            $totals['total_commission'] += $this->getTotalCommission($agentId, $startDate, $endDate, $stageId, $typeId);
            $totals['agent_commission'] += $this->getAgentCommission($agentId, $startDate, $endDate, $stageId, $typeId);
            $totals['team_commission'] += $this->getTeamCommission($agentId, $startDate, $endDate, $stageId, $typeId);
        }

        // Calculate overall rates
        $totals['close_rate'] = $totals['deals_created'] > 0 ?
            round(($totals['deals_closed_won'] / $totals['deals_created']) * 100, 2) : 0;

        $totalClosed = $totals['deals_closed_won'] + $totals['deals_closed_lost'];
        $totals['win_rate'] = $totalClosed > 0 ?
            round(($totals['deals_closed_won'] / $totalClosed) * 100, 2) : 0;

        return $totals;
    }

    // Time Series Data
    private function getDealTimeSeriesData($startDate, $endDate, $agentIds, $stageId = null, $typeId = null, $status = 'all')
    {
        $timeSeriesData = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dayStart = $currentDate->copy()->startOfDay();
            $dayEnd = $currentDate->copy()->endOfDay();

            $dayData = [
                'date' => $currentDate->format('Y-m-d'),
                'deals_created' => $this->getDealsCreatedForDay($dayStart, $dayEnd, $agentIds, $stageId, $typeId, $status),
                'deals_closed_won' => $this->getDealsClosedWonForDay($dayStart, $dayEnd, $agentIds, $stageId, $typeId),
                'total_value' => $this->getTotalValueForDay($dayStart, $dayEnd, $agentIds, $stageId, $typeId, $status),
                'closed_value' => $this->getClosedValueForDay($dayStart, $dayEnd, $agentIds, $stageId, $typeId),
            ];

            $timeSeriesData[] = $dayData;
            $currentDate->addDay();
        }

        return $timeSeriesData;
    }

    private function getDealsCreatedForDay($dayStart, $dayEnd, $agentIds, $stageId = null, $typeId = null, $status = 'all')
    {
        $query = Deal::whereHas('users', function ($query) use ($agentIds) {
            if (!empty($agentIds)) {
                $query->whereIn('users.id', $agentIds);
            }
        })->whereBetween('created_at', [$dayStart, $dayEnd]);

        if ($stageId) {
            $query->where('stage_id', $stageId);
        }

        if ($typeId) {
            $query->where('type_id', $typeId);
        }

        return $query->count();
    }

    private function getDealsClosedWonForDay($dayStart, $dayEnd, $agentIds, $stageId = null, $typeId = null)
    {
        $query = Deal::whereHas('users', function ($query) use ($agentIds) {
            if (!empty($agentIds)) {
                $query->whereIn('users.id', $agentIds);
            }
        })->whereHas('stage', function ($query) {
            $query->where('name', 'LIKE', '%won%')->orWhere('name', 'LIKE', '%closed%')->orWhere('name', 'LIKE', '%success%');
        })->whereBetween('updated_at', [$dayStart, $dayEnd]);

        if ($stageId) {
            $query->where('stage_id', $stageId);
        }

        if ($typeId) {
            $query->where('type_id', $typeId);
        }

        return $query->count();
    }

    private function getTotalValueForDay($dayStart, $dayEnd, $agentIds, $stageId = null, $typeId = null, $status = 'all')
    {
        $query = Deal::whereHas('users', function ($query) use ($agentIds) {
            if (!empty($agentIds)) {
                $query->whereIn('users.id', $agentIds);
            }
        })->whereBetween('created_at', [$dayStart, $dayEnd]);

        if ($stageId) {
            $query->where('stage_id', $stageId);
        }

        if ($typeId) {
            $query->where('type_id', $typeId);
        }

        return $query->sum('price') ?? 0;
    }

    private function getClosedValueForDay($dayStart, $dayEnd, $agentIds, $stageId = null, $typeId = null)
    {
        $query = Deal::whereHas('users', function ($query) use ($agentIds) {
            if (!empty($agentIds)) {
                $query->whereIn('users.id', $agentIds);
            }
        })->whereHas('stage', function ($query) {
            $query->where('name', 'LIKE', '%won%')->orWhere('name', 'LIKE', '%closed%')->orWhere('name', 'LIKE', '%success%');
        })->whereBetween('updated_at', [$dayStart, $dayEnd]);

        if ($stageId) {
            $query->where('stage_id', $stageId);
        }

        if ($typeId) {
            $query->where('type_id', $typeId);
        }

        return $query->sum('price') ?? 0;
    }

    // Stage Averages
    private function getStageAverages($agentIds, $stageId = null, $typeId = null)
    {
        $stageQuery = DealStage::query();

        if ($stageId) {
            $stageQuery->where('id', $stageId);
        }

        $stages = $stageQuery->get();
        $stageAverages = [];

        foreach ($stages as $stage) {
            $dealsInStage = Deal::whereHas('users', function ($query) use ($agentIds) {
                if (!empty($agentIds)) {
                    $query->whereIn('users.id', $agentIds);
                }
            })->where('stage_id', $stage->id);

            if ($typeId) {
                $dealsInStage->where('type_id', $typeId);
            }

            $deals = $dealsInStage->get();
            $totalDays = 0;
            $totalValue = 0;
            $count = $deals->count();

            foreach ($deals as $deal) {
                $days = $deal->created_at->diffInDays($deal->updated_at ?: now());
                $totalDays += $days;
                $totalValue += $deal->price;
            }

            $stageAverages[] = [
                'stage_id' => $stage->id,
                'stage_name' => $stage->name,
                'deal_count' => $count,
                'avg_time_in_stage' => $count > 0 ? round($totalDays / $count, 1) : 0,
                'avg_deal_value' => $count > 0 ? round($totalValue / $count, 2) : 0,
                'total_value' => $totalValue
            ];
        }

        return $stageAverages;
    }

    // Source Breakdown (if you have lead sources)
    private function getSourceBreakdown($startDate, $endDate, $agentIds, $stageId = null, $typeId = null, $status = 'all')
    {
        // This assumes you have a lead source tracking system
        // You might need to adjust this based on how you track deal sources
        $query = Deal::whereHas('users', function ($query) use ($agentIds) {
            if (!empty($agentIds)) {
                $query->whereIn('users.id', $agentIds);
            }
        })->whereBetween('created_at', [$startDate, $endDate])
            ->with('people'); // Assuming deals are connected to people who have sources

        if ($stageId) {
            $query->where('stage_id', $stageId);
        }

        if ($typeId) {
            $query->where('type_id', $typeId);
        }

        $deals = $query->get();
        $sourceBreakdown = [];

        foreach ($deals as $deal) {
            // This is a placeholder - you'd need to implement source tracking
            $source = 'Direct'; // Default source

            // If you have source tracking through people or other means, implement here
            // $source = $deal->people->first()->lead_source ?? 'Direct';

            if (!isset($sourceBreakdown[$source])) {
                $sourceBreakdown[$source] = [
                    'deals_count' => 0,
                    'total_value' => 0,
                    'closed_deals' => 0,
                    'closed_value' => 0
                ];
            }

            $sourceBreakdown[$source]['deals_count']++;
            $sourceBreakdown[$source]['total_value'] += $deal->price;

            // Check if deal is closed won
            if ($deal->stage && (
                stripos($deal->stage->name, 'won') !== false ||
                stripos($deal->stage->name, 'closed') !== false ||
                stripos($deal->stage->name, 'success') !== false
            )) {
                $sourceBreakdown[$source]['closed_deals']++;
                $sourceBreakdown[$source]['closed_value'] += $deal->price;
            }
        }

        // Calculate conversion rates
        foreach ($sourceBreakdown as $source => &$data) {
            $data['conversion_rate'] = $data['deals_count'] > 0 ?
                round(($data['closed_deals'] / $data['deals_count']) * 100, 2) : 0;
        }

        return $sourceBreakdown;
    }

    // Deals List (similar to the table in Follow Up Boss)
    private function getDealsList($startDate, $endDate, $agentIds, $stageId = null, $typeId = null, $status = 'all')
    {
        $query = Deal::whereHas('users', function ($query) use ($agentIds) {
            if (!empty($agentIds)) {
                $query->whereIn('users.id', $agentIds);
            }
        })->whereBetween('created_at', [$startDate, $endDate])
            ->with(['stage', 'type', 'users', 'people']);

        if ($stageId) {
            $query->where('stage_id', $stageId);
        }

        if ($typeId) {
            $query->where('type_id', $typeId);
        }

        $deals = $query->orderBy('created_at', 'desc')->get();

        return $deals->map(function ($deal) {
            return [
                'id' => $deal->id,
                'name' => $deal->name,
                'stage' => $deal->stage->name ?? 'Unknown',
                'type' => $deal->type->name ?? 'Unknown',
                'price' => $deal->price,
                'commission_value' => $deal->commission_value,
                'agent_commission' => $deal->agent_commission,
                'team_commission' => $deal->team_commission,
                'projected_close_date' => $deal->projected_close_date ? $deal->projected_close_date->format('Y-m-d') : null,
                'created_at' => $deal->created_at->format('Y-m-d'),
                'updated_at' => $deal->updated_at->format('Y-m-d'),
                'time_in_stage' => $deal->updated_at->diffInDays(now()),
                'time_to_close' => $deal->created_at->diffInDays($deal->updated_at),
                'agents' => $deal->users->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email
                    ];
                }),
                'people' => $deal->people->map(function ($person) {
                    return [
                        'id' => $person->id,
                        'name' => $person->name,
                        'email' => $person->email ?? null,
                        'phone' => $person->phone ?? null
                    ];
                })
            ];
        });
    }

    // Summary Statistics
    private function getSummaryStats($startDate, $endDate, $agentIds, $stageId = null, $typeId = null, $status = 'all')
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
                'top_performer_by_deals' => null,
                'top_performer_by_value' => null,
                'team_averages' => []
            ];
        }

        // Find top performer by deals closed
        $topPerformerByDeals = null;
        $maxDeals = 0;

        foreach ($agents as $agent) {
            $deals = $this->getDealsClosedWon($agent->id, $startDate, $endDate, $stageId, $typeId);
            if ($deals > $maxDeals) {
                $maxDeals = $deals;
                $topPerformerByDeals = [
                    'agent_name' => $agent->name,
                    'deals_closed' => $deals
                ];
            }
        }

        // Find top performer by deal value
        $topPerformerByValue = null;
        $maxValue = 0;

        foreach ($agents as $agent) {
            $value = $this->getClosedDealValue($agent->id, $startDate, $endDate, $stageId, $typeId);
            if ($value > $maxValue) {
                $maxValue = $value;
                $topPerformerByValue = [
                    'agent_name' => $agent->name,
                    'closed_value' => $value
                ];
            }
        }

        // Calculate team averages
        $totalMetrics = $this->getTotalDealMetrics($startDate, $endDate, $agentIds, $stageId, $typeId, $status);

        $teamAverages = [
            'avg_deals_per_agent' => round($totalMetrics['deals_created'] / $agentCount, 2),
            'avg_closed_deals_per_agent' => round($totalMetrics['deals_closed_won'] / $agentCount, 2),
            'avg_deal_value_per_agent' => round($totalMetrics['total_deal_value'] / $agentCount, 2),
            'avg_closed_value_per_agent' => round($totalMetrics['closed_deal_value'] / $agentCount, 2),
            'avg_commission_per_agent' => round($totalMetrics['total_commission'] / $agentCount, 2),
            'team_close_rate' => $totalMetrics['close_rate'],
            'team_win_rate' => $totalMetrics['win_rate']
        ];

        return [
            'top_performer_by_deals' => $topPerformerByDeals,
            'top_performer_by_value' => $topPerformerByValue,
            'team_averages' => $teamAverages
        ];
    }
}