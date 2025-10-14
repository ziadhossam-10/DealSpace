"use client"

import { TrendingUp, TrendingDown } from "lucide-react"
import type { AgentActivityTotals } from "./agentActivityApi"

interface AgentActivityMetricsProps {
  totals: AgentActivityTotals
  previousTotals?: AgentActivityTotals
}

interface MetricCardProps {
  title: string
  value: number
  previousValue?: number
  format?: (value: number) => string
}

function MetricCard({ title, value, previousValue, format = (v) => v.toString() }: MetricCardProps) {
  const hasComparison = previousValue !== undefined
  const percentChange = hasComparison ? ((value - previousValue) / previousValue) * 100 : 0
  const isPositive = percentChange > 0
  const isNegative = percentChange < 0

  return (
    <div className="bg-white rounded-lg shadow p-6">
      <div className="flex items-center justify-between mb-2">
        <h3 className="text-sm font-medium text-gray-500 uppercase tracking-wide">{title}</h3>
      </div>
      <div className="flex items-baseline">
        <p className="text-3xl font-bold text-gray-900">{format(value)}</p>
        {hasComparison && (
          <div
            className={`ml-2 flex items-center text-sm ${
              isPositive ? "text-red-600" : isNegative ? "text-green-600" : "text-gray-500"
            }`}
          >
            {isPositive && <TrendingDown className="w-4 h-4 mr-1" />}
            {isNegative && <TrendingUp className="w-4 h-4 mr-1" />}
            <span>
              ({Math.abs(percentChange).toFixed(1)}%) vs {format(previousValue)}
            </span>
          </div>
        )}
      </div>
      {/* Mini chart placeholder */}
      <div className="mt-4 h-8">
        <div className="w-full h-full bg-gray-100 rounded"></div>
      </div>
    </div>
  )
}

export default function AgentActivityMetrics({ totals, previousTotals }: AgentActivityMetricsProps) {
  const formatTime = (seconds: number) => {
    if (seconds === 0) return "0s"
    const hours = Math.floor(seconds / 3600)
    const minutes = Math.floor((seconds % 3600) / 60)
    const secs = Math.floor(seconds % 60)

    if (hours > 0) return `${hours}h ${minutes}m`
    if (minutes > 0) return `${minutes}m ${secs}s`
    return `${secs}s`
  }

  const formatPercentage = (value: number) => `${value.toFixed(1)}%`

  return (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-8">
      <MetricCard title="New Leads" value={totals.new_leads} previousValue={previousTotals?.new_leads} />
      <MetricCard title="Calls" value={totals.calls} previousValue={previousTotals?.calls} />
      <MetricCard title="Emails" value={totals.emails} previousValue={previousTotals?.emails} />
      <MetricCard title="Texts" value={totals.texts} previousValue={previousTotals?.texts} />
      <MetricCard title="Notes" value={totals.notes} previousValue={previousTotals?.notes} />
      <MetricCard
        title="Tasks Completed"
        value={totals.tasks_completed}
        previousValue={previousTotals?.tasks_completed}
      />
    </div>
  )
}
