"use client"

import { TrendingUp, Users, DollarSign, Target, Clock, AlertTriangle } from "lucide-react"
import type { LeaderboardSummary } from "./leaderboard-api"

interface LeaderboardSummaryProps {
  summary: LeaderboardSummary
}

export default function LeaderboardSummaryComponent({ summary }: LeaderboardSummaryProps) {
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

  const summaryCards = [
    {
      title: "Deals Closed",
      value: formatCurrency(summary.total_closed_value),
      subtitle: `${formatNumber(summary.total_deals_closed)} deals`,
      icon: DollarSign,
      color: "bg-green-500",
      textColor: "text-green-600",
      bgColor: "bg-green-50",
    },
    {
      title: "Commission",
      value: formatCurrency(summary.total_commission),
      subtitle: `${formatCurrency(summary.team_performance.avg_commission_per_agent)} avg per agent`,
      icon: TrendingUp,
      color: "bg-blue-500",
      textColor: "text-blue-600",
      bgColor: "bg-blue-50",
    },
    {
      title: "Closed Deals",
      value: formatNumber(summary.total_deals_closed),
      subtitle: `${formatNumber(summary.team_performance.avg_deals_per_agent)} avg per agent`,
      icon: Target,
      color: "bg-purple-500",
      textColor: "text-purple-600",
      bgColor: "bg-purple-50",
    },
    {
      title: "Average Deal",
      value: formatCurrency(summary.average_deal_size),
      subtitle: `${formatCurrency(summary.team_performance.avg_value_per_agent)} avg per agent`,
      icon: Users,
      color: "bg-orange-500",
      textColor: "text-orange-600",
      bgColor: "bg-orange-50",
    },
  ]

  return (
    <div className="space-y-6">
      {/* Main Summary Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {summaryCards.map((card) => {
          const Icon = card.icon
          return (
            <div key={card.title} className="bg-white rounded-lg shadow p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-gray-600">{card.title}</p>
                  <p className="text-2xl font-bold text-gray-900 mt-1">{card.value}</p>
                  {card.subtitle && <p className="text-sm text-gray-500 mt-1">{card.subtitle}</p>}
                </div>
                <div className={`${card.bgColor} p-3 rounded-full`}>
                  <Icon className={`w-6 h-6 ${card.textColor}`} />
                </div>
              </div>
            </div>
          )
        })}
      </div>

      {/* Additional Stats */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        {/* Pipeline Summary */}
        <div className="bg-white rounded-lg shadow p-6">
          <div className="flex items-center gap-2 mb-4">
            <Clock className="w-5 h-5 text-blue-600" />
            <h3 className="text-lg font-semibold text-gray-900">Pipeline</h3>
          </div>
          <div className="space-y-2">
            <div className="flex justify-between">
              <span className="text-sm text-gray-600">Total Deals:</span>
              <span className="font-medium">{formatNumber(summary.pipeline_summary.total_deals)}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-sm text-gray-600">Total Value:</span>
              <span className="font-medium">{formatCurrency(summary.pipeline_summary.total_value)}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-sm text-gray-600">Avg Deal Size:</span>
              <span className="font-medium">{formatCurrency(summary.pipeline_summary.average_deal_size)}</span>
            </div>
          </div>
        </div>

        {/* Overdue Summary */}
        <div className="bg-white rounded-lg shadow p-6">
          <div className="flex items-center gap-2 mb-4">
            <AlertTriangle className="w-5 h-5 text-red-600" />
            <h3 className="text-lg font-semibold text-gray-900">Overdue</h3>
          </div>
          <div className="space-y-2">
            <div className="flex justify-between">
              <span className="text-sm text-gray-600">Total Deals:</span>
              <span className="font-medium text-red-600">{formatNumber(summary.overdue_summary.total_deals)}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-sm text-gray-600">Total Value:</span>
              <span className="font-medium text-red-600">{formatCurrency(summary.overdue_summary.total_value)}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-sm text-gray-600">% of Pipeline:</span>
              <span className="font-medium text-red-600">{summary.overdue_summary.percentage_of_pipeline}%</span>
            </div>
          </div>
        </div>

        {/* Team Performance */}
        <div className="bg-white rounded-lg shadow p-6">
          <div className="flex items-center gap-2 mb-4">
            <Users className="w-5 h-5 text-green-600" />
            <h3 className="text-lg font-semibold text-gray-900">Team Stats</h3>
          </div>
          <div className="space-y-2">
            <div className="flex justify-between">
              <span className="text-sm text-gray-600">Total Agents:</span>
              <span className="font-medium">{formatNumber(summary.total_agents)}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-sm text-gray-600">Active Agents:</span>
              <span className="font-medium">{formatNumber(summary.active_agents)}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-sm text-gray-600">Avg Deals/Agent:</span>
              <span className="font-medium">{summary.team_performance.avg_deals_per_agent.toFixed(1)}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}
