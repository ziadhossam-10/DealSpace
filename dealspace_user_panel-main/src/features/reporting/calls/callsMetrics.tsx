"use client"

import type { CallsTotals, SummaryStats } from "./callsApi"

interface CallsMetricsProps {
  totals: CallsTotals
  summaryStats: SummaryStats
}

export default function CallsMetrics({ totals, summaryStats }: CallsMetricsProps) {
  const formatPercentage = (value: number) => `${value?.toFixed(1)}%`

  const metrics = [
    {
      title: "Total Calls Made",
      value: totals.calls_made.toLocaleString(),
      icon: "📞",
      color: "bg-blue-50 text-blue-700",
    },
    {
      title: "Calls Connected",
      value: totals.calls_connected.toLocaleString(),
      icon: "✅",
      color: "bg-green-50 text-green-700",
    },
    {
      title: "Conversations",
      value: totals.conversations.toLocaleString(),
      icon: "💬",
      color: "bg-purple-50 text-purple-700",
    },
    {
      title: "Total Talk Time",
      value: totals.total_talk_time.formatted,
      icon: "⏱️",
      color: "bg-orange-50 text-orange-700",
    },
    {
      title: "Connection Rate",
      value: formatPercentage(totals.connection_rate),
      icon: "🎯",
      color: "bg-emerald-50 text-emerald-700",
    },
    {
      title: "Conversation Rate",
      value: formatPercentage(totals.conversation_rate),
      icon: "📈",
      color: "bg-indigo-50 text-indigo-700",
    },
    {
      title: "Unique Contacts",
      value: totals.unique_contacts_called.toLocaleString(),
      icon: "👥",
      color: "bg-cyan-50 text-cyan-700",
    },
    {
      title: "Contacts Reached",
      value: totals.contacts_reached.toLocaleString(),
      icon: "🤝",
      color: "bg-red-50 text-red-700",
    },
  ]

  return (
    <div className="space-y-6">
      {/* Main Metrics */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        {metrics.map((metric, index) => (
          <div key={index} className="bg-white rounded-lg shadow p-6">
            <div className="flex items-center">
              <div className={`rounded-lg p-3 ${metric.color}`}>
                <span className="text-2xl">{metric.icon}</span>
              </div>
              <div className="ml-4">
                <p className="text-sm font-medium text-gray-600">{metric.title}</p>
                <p className="text-2xl font-bold text-gray-900">{metric.value}</p>
              </div>
            </div>
          </div>
        ))}
      </div>

      {/* Summary Stats */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        {/* Top Performer */}
        <div className="bg-white rounded-lg shadow p-6">
          <h3 className="text-lg font-semibold text-gray-900 mb-4">🏆 Top Performer</h3>
          <div className="space-y-2">
            <div className="flex justify-between">
              <span className="text-gray-600">Agent:</span>
              <span className="font-medium">{summaryStats.top_performer?.agent_name}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-gray-600">Calls Made:</span>
              <span className="font-medium">{summaryStats.top_performer?.calls_made}</span>
            </div>
          </div>
        </div>

        {/* Team Averages */}
        <div className="bg-white rounded-lg shadow p-6">
          <h3 className="text-lg font-semibold text-gray-900 mb-4">📊 Team Averages</h3>
          <div className="space-y-2">
            <div className="flex justify-between">
              <span className="text-gray-600">Calls per Agent:</span>
              <span className="font-medium">{summaryStats.team_averages?.avg_calls_per_agent?.toFixed(1)}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-gray-600">Connection Rate:</span>
              <span className="font-medium">{formatPercentage(summaryStats.team_averages?.avg_connection_rate)}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-gray-600">Conversation Rate:</span>
              <span className="font-medium">{formatPercentage(summaryStats.team_averages?.avg_conversation_rate)}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-gray-600">Avg Talk Time:</span>
              <span className="font-medium">{summaryStats.team_averages?.avg_talk_time_per_agent?.formatted}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}
