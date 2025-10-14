<?php

namespace App\Http\Controllers\Api\Reports;

use App\Enums\RoleEnum;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Deal;
use App\Models\Team;
use App\Models\DealStage;

class DealsLeaderboardController extends Controller
{
    public function index(Request $request)
    {
        $timeframe = $request->timeframe ?? 'this_month'; // this_month, this_year, year_to_date, all_time, custom
        $stageId = $request->stage_id; // specific stage or null for all
        $typeId = $request->type_id; // specific type or null for all
        $teamId = $request->team_id; // specific team or null for everyone
        $excludeUserIds = $request->exclude_user_ids ?? []; // users to exclude from leaderboard
        $limit = $request->limit ?? 10; // number of top performers to show

        // Calculate date range based on timeframe
        $dateRange = $this->getDateRange($timeframe, $request->start_date, $request->end_date);

        // Get filtered agent IDs
        $agentIds = $this->getFilteredAgentIds($teamId, $excludeUserIds);

        // Get leaderboard data
        $leaderboard = $this->getLeaderboardData($agentIds, $dateRange, $stageId, $typeId, $limit);

        // Get summary statistics
        $summary = $this->getLeaderboardSummary($agentIds, $dateRange, $stageId, $typeId);

        // Get team information if team_id provided
        $teamInfo = $this->getTeamInfo($teamId);

        return response()->json([
            'timeframe' => [
                'type' => $timeframe,
                'start_date' => $dateRange['start']->format('Y-m-d'),
                'end_date' => $dateRange['end']->format('Y-m-d'),
                'display_name' => $this->getTimeframeDisplayName($timeframe, $dateRange)
            ],
            'filters' => [
                'stage_id' => $stageId,
                'type_id' => $typeId,
                'team_id' => $teamId,
                'excluded_users' => count($excludeUserIds),
                'limit' => $limit
            ],
            'team_info' => $teamInfo,
            'leaderboard' => $leaderboard,
            'summary' => $summary,
            'last_updated' => now()->format('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get date range based on timeframe
     */
    private function getDateRange($timeframe, $customStart = null, $customEnd = null)
    {
        $now = now();

        switch ($timeframe) {
            case 'this_month':
                return [
                    'start' => $now->copy()->startOfMonth(),
                    'end' => $now->copy()->endOfDay()
                ];

            case 'this_year':
                return [
                    'start' => $now->copy()->startOfYear(),
                    'end' => $now->copy()->endOfDay()
                ];

            case 'year_to_date':
                return [
                    'start' => $now->copy()->startOfYear(),
                    'end' => $now->copy()->endOfDay()
                ];

            case 'all_time':
                return [
                    'start' => Carbon::create(2000, 1, 1), // Far back start date
                    'end' => $now->copy()->endOfDay()
                ];

            case 'custom':
                return [
                    'start' => $customStart ? Carbon::parse($customStart) : $now->copy()->startOfMonth(),
                    'end' => $customEnd ? Carbon::parse($customEnd) : $now->copy()->endOfDay()
                ];

            default:
                return [
                    'start' => $now->copy()->startOfMonth(),
                    'end' => $now->copy()->endOfDay()
                ];
        }
    }

    /**
     * Get display name for timeframe
     */
    private function getTimeframeDisplayName($timeframe, $dateRange)
    {
        switch ($timeframe) {
            case 'this_month':
                return $dateRange['start']->format('F Y');
            case 'this_year':
            case 'year_to_date':
                return $dateRange['start']->format('Y');
            case 'all_time':
                return 'All Time';
            case 'custom':
                return $dateRange['start']->format('M j, Y') . ' - ' . $dateRange['end']->format('M j, Y');
            default:
                return 'Current Period';
        }
    }

    /**
     * Get filtered agent IDs based on team and exclusions
     */
    private function getFilteredAgentIds($teamId, $excludeUserIds)
    {
        $query = User::where('role', RoleEnum::AGENT);

        // Filter by team if specified
        if ($teamId) {
            $team = Team::find($teamId);
            if ($team) {
                $teamAgentIds = $team->agents()->pluck('users.id');
                $query->whereIn('id', $teamAgentIds);
            }
        }

        // Exclude specified users
        if (!empty($excludeUserIds)) {
            $query->whereNotIn('id', $excludeUserIds);
        }

        return $query->pluck('id');
    }

    /**
     * Get team information
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
     * Get main leaderboard data
     */
    private function getLeaderboardData($agentIds, $dateRange, $stageId, $typeId, $limit)
    {
        $agents = User::whereIn('id', $agentIds)->get();
        $leaderboardData = [];

        foreach ($agents as $agent) {
            $metrics = $this->getAgentLeaderboardMetrics($agent, $dateRange, $stageId, $typeId);

            if ($metrics['total_closed_value'] > 0 || $metrics['deals_closed'] > 0) {
                $leaderboardData[] = $metrics;
            }
        }

        // Sort by total closed value (primary ranking criteria)
        usort($leaderboardData, function ($a, $b) {
            if ($a['total_closed_value'] == $b['total_closed_value']) {
                // If same value, sort by number of deals closed
                return $b['deals_closed'] <=> $a['deals_closed'];
            }
            return $b['total_closed_value'] <=> $a['total_closed_value'];
        });

        // Add ranking and limit results
        $rankedData = array_slice($leaderboardData, 0, $limit);
        foreach ($rankedData as $index => &$data) {
            $data['rank'] = $index + 1;
        }

        return $rankedData;
    }

    /**
     * Get metrics for individual agent
     */
    private function getAgentLeaderboardMetrics($agent, $dateRange, $stageId, $typeId)
    {
        $baseQuery = Deal::whereHas('users', function ($query) use ($agent) {
            $query->where('users.id', $agent->id);
        });

        // Filter by stage if specified
        if ($stageId) {
            $baseQuery->where('stage_id', $stageId);
        }

        // Filter by type if specified
        if ($typeId) {
            $baseQuery->where('type_id', $typeId);
        }

        // Get closed deals within timeframe
        // A deal is considered closed if projected_close_date has passed and is within our date range
        $closedDealsQuery = $baseQuery->clone()
            ->whereNotNull('projected_close_date')
            ->where('projected_close_date', '<=', now())
            ->whereBetween('projected_close_date', [$dateRange['start'], $dateRange['end']]);

        $closedDeals = $closedDealsQuery->get();

        // Calculate metrics
        $dealsClosedCount = $closedDeals->count();
        $totalClosedValue = $closedDeals->sum('price');
        $totalCommission = $closedDeals->sum('commission_value');
        $agentCommission = $closedDeals->sum('agent_commission');
        $avgDealSize = $dealsClosedCount > 0 ? $totalClosedValue / $dealsClosedCount : 0;

        // Get current pipeline deals (deals with future or no projected close date)
        $currentPipelineQuery = $baseQuery->clone()
            ->where(function ($query) {
                $query->whereNull('projected_close_date')
                    ->orWhere('projected_close_date', '>', now());
            });

        $currentDeals = $currentPipelineQuery->count();
        $currentPipelineValue = $currentPipelineQuery->sum('price');

        // Get deals created in period
        $dealsCreatedInPeriod = $baseQuery->clone()
            ->whereBetween('deals.created_at', [$dateRange['start'], $dateRange['end']])
            ->count();

        // Get overdue deals (projected_close_date passed but still in pipeline)
        $overdueDealsQuery = $baseQuery->clone()
            ->whereNotNull('projected_close_date')
            ->where('projected_close_date', '<', now());

        $overdueDeals = $overdueDealsQuery->count();
        $overdueValue = $overdueDealsQuery->sum('price');

        return [
            'agent_id' => $agent->id,
            'agent_name' => $agent->name,
            'agent_email' => $agent->email,
            'agent_avatar' => $agent->avatar ?? null,
            'deals_closed' => $dealsClosedCount,
            'total_closed_value' => round($totalClosedValue, 2),
            'average_deal_size' => round($avgDealSize, 2),
            'total_commission' => round($totalCommission, 2),
            'agent_commission' => round($agentCommission, 2),
            'deals_in_pipeline' => $currentDeals,
            'pipeline_value' => round($currentPipelineValue, 2),
            'deals_created_in_period' => $dealsCreatedInPeriod,
            'overdue_deals' => $overdueDeals,
            'overdue_value' => round($overdueValue, 2),
            'performance_stats' => [
                'close_rate' => $this->calculateCloseRate($agent->id, $dateRange, $stageId, $typeId),
                'avg_days_to_close' => $this->calculateAvgDaysToClose($closedDeals),
                'momentum_score' => $this->calculateMomentumScore($agent->id, $dateRange, $stageId, $typeId),
                'on_time_close_rate' => $this->calculateOnTimeCloseRate($agent->id, $dateRange, $stageId, $typeId)
            ]
        ];
    }

    /**
     * Calculate close rate for agent
     */
    private function calculateCloseRate($agentId, $dateRange, $stageId, $typeId)
    {
        $baseQuery = Deal::whereHas('users', function ($query) use ($agentId) {
            $query->where('users.id', $agentId);
        })->whereBetween('deals.created_at', [$dateRange['start'], $dateRange['end']]);

        if ($stageId) {
            $baseQuery->where('deals.stage_id', $stageId);
        }

        if ($typeId) {
            $baseQuery->where('deals.type_id', $typeId);
        }

        $totalDeals = $baseQuery->count();

        $closedDeals = $baseQuery->clone()
            ->whereNotNull('deals.projected_close_date')
            ->where('deals.projected_close_date', '<=', now())
            ->count();

        return $totalDeals > 0 ? round(($closedDeals / $totalDeals) * 100, 1) : 0;
    }

    /**
     * Calculate average days to close
     */
    private function calculateAvgDaysToClose($closedDeals)
    {
        if ($closedDeals->count() == 0) {
            return 0;
        }

        $totalDays = 0;
        foreach ($closedDeals as $deal) {
            if ($deal->projected_close_date) {
                $days = $deal->created_at->diffInDays($deal->projected_close_date);
                $totalDays += $days;
            }
        }

        return $closedDeals->count() > 0 ? round($totalDays / $closedDeals->count(), 1) : 0;
    }

    /**
     * Calculate on-time close rate (deals closed by or before projected date)
     */
    private function calculateOnTimeCloseRate($agentId, $dateRange, $stageId, $typeId)
    {
        $baseQuery = Deal::whereHas('users', function ($query) use ($agentId) {
            $query->where('users.id', $agentId);
        })->whereNotNull('deals.projected_close_date')
            ->where('deals.projected_close_date', '<=', now())
            ->whereBetween('deals.projected_close_date', [$dateRange['start'], $dateRange['end']]);

        if ($stageId) {
            $baseQuery->where('deals.stage_id', $stageId);
        }

        if ($typeId) {
            $baseQuery->where('deals.type_id', $typeId);
        }

        $totalClosedDeals = $baseQuery->count();

        // On-time deals are those where projected_close_date >= actual close date
        // Since we're using projected_close_date as the close indicator,
        // we consider "on-time" as deals closed on or before their projected date
        $onTimeDeals = $baseQuery->clone()
            ->where('deals.projected_close_date', '>=', now()->subDays(30)) // Adjust logic as needed
            ->count();

        return $totalClosedDeals > 0 ? round(($onTimeDeals / $totalClosedDeals) * 100, 1) : 0;
    }

    /**
     * Calculate momentum score (recent activity indicator)
     */
    private function calculateMomentumScore($agentId, $dateRange, $stageId, $typeId)
    {
        // Get deals closed in last 30 days vs previous 30 days
        $last30Days = now()->subDays(30);
        $previous30Days = now()->subDays(60);

        $baseQuery = Deal::whereHas('users', function ($query) use ($agentId) {
            $query->where('users.id', $agentId);
        })->whereNotNull('deals.projected_close_date')
            ->where('deals.projected_close_date', '<=', now());

        if ($stageId) {
            $baseQuery->where('deals.stage_id', $stageId);
        }

        if ($typeId) {
            $baseQuery->where('deals.type_id', $typeId);
        }

        $recentDeals = $baseQuery->clone()
            ->whereBetween('deals.projected_close_date', [$last30Days, now()])
            ->sum('deals.price');

        $previousDeals = $baseQuery->clone()
            ->whereBetween('deals.projected_close_date', [$previous30Days, $last30Days])
            ->sum('deals.price');

        if ($previousDeals == 0) {
            return $recentDeals > 0 ? 100 : 0;
        }

        $changePercent = (($recentDeals - $previousDeals) / $previousDeals) * 100;
        return round(max(-100, min(100, $changePercent)), 1);
    }

    /**
     * Get summary statistics
     */
    private function getLeaderboardSummary($agentIds, $dateRange, $stageId, $typeId)
    {
        $baseQuery = Deal::whereHas('users', function ($query) use ($agentIds) {
            $query->whereIn('users.id', $agentIds);
        });

        if ($stageId) {
            $baseQuery->where('stage_id', $stageId);
        }

        if ($typeId) {
            $baseQuery->where('type_id', $typeId);
        }

        // Total closed deals in period
        $closedDealsQuery = $baseQuery->clone()
            ->whereNotNull('projected_close_date')
            ->where('projected_close_date', '<=', now())
            ->whereBetween('projected_close_date', [$dateRange['start'], $dateRange['end']]);

        $totalClosedDeals = $closedDealsQuery->count();
        $totalClosedValue = $closedDealsQuery->sum('price');
        $totalCommission = $closedDealsQuery->sum('commission_value');

        // Current pipeline
        $currentPipelineQuery = $baseQuery->clone()
            ->where(function ($query) {
                $query->whereNull('projected_close_date')
                    ->orWhere('projected_close_date', '>', now());
            });

        $totalPipelineDeals = $currentPipelineQuery->count();
        $totalPipelineValue = $currentPipelineQuery->sum('price');

        // Overdue deals
        $overdueQuery = $baseQuery->clone()
            ->whereNotNull('projected_close_date')
            ->where('projected_close_date', '<', now());

        $totalOverdueDeals = $overdueQuery->count();
        $totalOverdueValue = $overdueQuery->sum('price');

        // Active agents (agents with any activity in period)
        $activeAgents = User::whereIn('id', $agentIds)
            ->whereHas('deals', function ($query) use ($dateRange, $stageId, $typeId) {
                $query->whereBetween('deals.created_at', [$dateRange['start'], $dateRange['end']]);
                if ($stageId) {
                    $query->where('deals.stage_id', $stageId);
                }
                if ($typeId) {
                    $query->where('deals.type_id', $typeId);
                }
            })
            ->count();

        return [
            'total_agents' => count($agentIds),
            'active_agents' => $activeAgents,
            'total_deals_closed' => $totalClosedDeals,
            'total_closed_value' => round($totalClosedValue, 2),
            'total_commission' => round($totalCommission, 2),
            'average_deal_size' => $totalClosedDeals > 0 ? round($totalClosedValue / $totalClosedDeals, 2) : 0,
            'pipeline_summary' => [
                'total_deals' => $totalPipelineDeals,
                'total_value' => round($totalPipelineValue, 2),
                'average_deal_size' => $totalPipelineDeals > 0 ? round($totalPipelineValue / $totalPipelineDeals, 2) : 0
            ],
            'overdue_summary' => [
                'total_deals' => $totalOverdueDeals,
                'total_value' => round($totalOverdueValue, 2),
                'percentage_of_pipeline' => $totalPipelineDeals > 0 ? round(($totalOverdueDeals / $totalPipelineDeals) * 100, 1) : 0
            ],
            'team_performance' => [
                'avg_deals_per_agent' => $activeAgents > 0 ? round($totalClosedDeals / $activeAgents, 1) : 0,
                'avg_value_per_agent' => $activeAgents > 0 ? round($totalClosedValue / $activeAgents, 2) : 0,
                'avg_commission_per_agent' => $activeAgents > 0 ? round($totalCommission / $activeAgents, 2) : 0
            ]
        ];
    }

    /**
     * Get available timeframe options
     */
    public function getTimeframeOptions()
    {
        return response()->json([
            'timeframes' => [
                ['value' => 'this_month', 'label' => 'This Month'],
                ['value' => 'this_year', 'label' => 'This Year'],
                ['value' => 'year_to_date', 'label' => 'Year to Date'],
                ['value' => 'all_time', 'label' => 'All Time'],
                ['value' => 'custom', 'label' => 'Custom Range']
            ]
        ]);
    }

    /**
     * Get available stages and types for filtering
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

        $stages = \App\Models\DealStage::select('id', 'name')->get();
        $types = \App\Models\DealType::select('id', 'name')->get();

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
            'stages' => $stages,
            'types' => $types
        ]);
    }
}
