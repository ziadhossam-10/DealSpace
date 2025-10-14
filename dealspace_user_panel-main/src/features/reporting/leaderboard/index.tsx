"use client"

import { useState } from "react"
import { useGetLeaderboardQuery, useGetLeaderboardOptionsQuery } from "./leaderboard-api"
import LeaderboardFilters from "./leaderboard-filters"
import LeaderboardCard from "./leaderboard-card"
import LeaderboardSummary from "./leaderboard-summary"

export default function LeaderboardReport() {
  const [timeframe, setTimeframe] = useState("this_month")
  const [limit, setLimit] = useState(10)
  const [selectedStage, setSelectedStage] = useState<number | undefined>()
  const [selectedType, setSelectedType] = useState<number | undefined>()
  const [selectedTeam, setSelectedTeam] = useState<number | undefined>()

  const queryParams = {
    timeframe,
    limit,
    stage_id: selectedStage,
    type_id: selectedType,
    team_id: selectedTeam,
  }

  const { data: leaderboardData, isLoading, error, refetch } = useGetLeaderboardQuery(queryParams)
  const { data: optionsData } = useGetLeaderboardOptionsQuery()

  if (error) {
    return (
      <div className="container mx-auto p-6">
        <div className="text-center py-12">
          <p className="text-red-600">Error loading leaderboard data. Please try again.</p>
          <button onClick={() => refetch()} className="mt-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
            Retry
          </button>
        </div>
      </div>
    )
  }

  return (
    <div className="container mx-auto p-6">
      <div className="space-y-6">
        {/* Header */}
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-bold text-gray-900">Deals Leaderboard</h1>
            <p className="text-gray-600">Top performing agents ranked by deals closed and revenue generated.</p>
          </div>
        </div>

        {/* Filters */}
        <LeaderboardFilters
          timeframe={timeframe}
          limit={limit}
          selectedStage={selectedStage}
          selectedType={selectedType}
          selectedTeam={selectedTeam}
          onTimeframeChange={setTimeframe}
          onLimitChange={setLimit}
          onStageChange={setSelectedStage}
          onTypeChange={setSelectedType}
          onTeamChange={setSelectedTeam}
          options={optionsData}
        />

        {/* Leaderboard Cards */}
        {isLoading ? (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {[...Array(6)].map((_, i) => (
              <div key={i} className="bg-white rounded-lg shadow p-6 animate-pulse">
                <div className="h-4 bg-gray-200 rounded w-3/4 mb-4"></div>
                <div className="h-8 bg-gray-200 rounded w-1/2 mb-4"></div>
                <div className="space-y-2">
                  <div className="h-3 bg-gray-200 rounded w-full"></div>
                  <div className="h-3 bg-gray-200 rounded w-2/3"></div>
                </div>
              </div>
            ))}
          </div>
        ) : leaderboardData?.leaderboard.length === 0 ? (
          <div className="text-center py-12">
            <p className="text-gray-500">No leaderboard data available for the selected period and filters.</p>
          </div>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            {leaderboardData?.leaderboard.map((agent) => (
              <LeaderboardCard key={agent.agent_id} agent={agent} />
            ))}
          </div>
        )}

        {/* Summary */}
        {leaderboardData?.summary && <LeaderboardSummary summary={leaderboardData.summary} />}

        {/* Additional Info */}
        {leaderboardData?.timeframe && (
          <div className="text-sm text-gray-500 text-center">
            Period: {leaderboardData.timeframe.display_name} ({leaderboardData.timeframe.start_date} to{" "}
            {leaderboardData.timeframe.end_date})
            {leaderboardData.last_updated && (
              <span className="ml-4">Last updated: {new Date(leaderboardData.last_updated).toLocaleString()}</span>
            )}
          </div>
        )}
      </div>
    </div>
  )
}
