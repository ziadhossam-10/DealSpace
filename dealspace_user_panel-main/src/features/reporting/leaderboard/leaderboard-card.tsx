"use client"

import { ASSETS_URL } from "../../../utils/helpers"
import type { LeaderboardAgent } from "./leaderboard-api"

interface LeaderboardCardProps {
  agent: LeaderboardAgent
  showRank?: boolean
}

export default function LeaderboardCard({ agent, showRank = true }: LeaderboardCardProps) {
  const formatCurrency = (value: number) => {
    if (value >= 1000000) {
      return `$${(value / 1000000).toFixed(1)}M`
    }
    if (value >= 1000) {
      return `$${(value / 1000).toFixed(0)}K`
    }
    return new Intl.NumberFormat("en-US", {
      style: "currency",
      currency: "USD",
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }).format(value)
  }

  const formatNumber = (value: number) => {
    return new Intl.NumberFormat("en-US").format(value)
  }

  const getInitials = (name: string) => {
    return name
      .split(" ")
      .map((n) => n[0])
      .join("")
      .toUpperCase()
      .slice(0, 2)
  }

  const getRankColor = (rank: number) => {
    switch (rank) {
      case 1:
        return "bg-yellow-100 text-yellow-800 border-yellow-200"
      case 2:
        return "bg-gray-100 text-gray-800 border-gray-200"
      case 3:
        return "bg-orange-100 text-orange-800 border-orange-200"
      default:
        return "bg-blue-100 text-blue-800 border-blue-200"
    }
  }

  const getAvatarColor = (rank: number) => {
    switch (rank) {
      case 1:
        return "bg-yellow-500"
      case 2:
        return "bg-gray-500"
      case 3:
        return "bg-orange-500"
      default:
        return "bg-blue-500"
    }
  }

  return (
    <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
      <div className="flex items-center justify-between mb-4">
        <div className="flex items-center gap-3">
          {showRank && (
            <div
              className={`w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold border-2 ${getRankColor(agent.rank)}`}
            >
              {agent.rank}
            </div>
          )}
          <div className="relative w-12 h-12 rounded-full overflow-hidden">
            {agent.agent_avatar ? (
              <img
                src={`${ASSETS_URL}/storage/${agent?.agent_avatar}`}
                alt={agent.agent_name}
                className="w-full h-full object-cover"
              />
            ) : (
              <div
                className={`w-full h-full flex items-center justify-center text-white font-semibold ${getAvatarColor(agent.rank)}`}
              >
                {getInitials(agent.agent_name)}
              </div>
            )}
          </div>
          <div>
            <h3 className="font-semibold text-gray-900">{agent.agent_name}</h3>
            <p className="text-sm text-gray-500">{agent.agent_email}</p>
          </div>
        </div>
      </div>

      <div className="text-center mb-4">
        <div className="text-3xl font-bold text-green-600 mb-1">{formatCurrency(agent.total_closed_value)}</div>
        <div className="text-sm text-gray-500 uppercase tracking-wide">DEALS CLOSED</div>
      </div>

      <div className="grid grid-cols-3 gap-4 text-center">
        <div>
          <div className="flex items-center justify-center gap-1">
            <span className="text-green-600">💰</span>
            <span className="text-sm font-medium">{formatCurrency(agent.total_commission)}</span>
          </div>
          <div className="text-xs text-gray-500 uppercase">COMMISSION</div>
        </div>
        <div>
          <div className="flex items-center justify-center gap-1">
            <span className="text-blue-600">💎</span>
            <span className="text-sm font-medium">{formatCurrency(agent.average_deal_size)}</span>
          </div>
          <div className="text-xs text-gray-500 uppercase">DEAL AVG.</div>
        </div>
        <div>
          <div className="flex items-center justify-center gap-1">
            <span className="text-purple-600">🎯</span>
            <span className="text-sm font-medium">{formatNumber(agent.deals_closed)}</span>
          </div>
          <div className="text-xs text-gray-500 uppercase">CLOSED</div>
        </div>
      </div>

      {/* Performance Stats */}
      <div className="mt-4 pt-4 border-t border-gray-100">
        <div className="grid grid-cols-2 gap-2 text-xs">
          <div className="flex justify-between">
            <span className="text-gray-500">Close Rate:</span>
            <span className="font-medium">{agent.performance_stats.close_rate}%</span>
          </div>
          <div className="flex justify-between">
            <span className="text-gray-500">Avg Days:</span>
            <span className="font-medium">{agent.performance_stats.avg_days_to_close.toFixed(1)}</span>
          </div>
        </div>
      </div>
    </div>
  )
}
