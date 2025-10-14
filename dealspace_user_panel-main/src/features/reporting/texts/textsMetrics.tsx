"use client"

import type { TextsTotals, SummaryStats } from "./textsApi"

interface TextsMetricsProps {
  totals: TextsTotals
  summaryStats: SummaryStats
}

export default function TextsMetrics({ totals, summaryStats }: TextsMetricsProps) {
  const formatPercentage = (value: number) => `${value?.toFixed(1)}%`

  const metrics = [
    {
      title: "Total Texts Sent",
      value: totals.texts_sent.toLocaleString(),
      icon: "📱",
      color: "bg-blue-50 text-blue-700",
    },
    {
      title: "Texts Delivered",
      value: totals.texts_delivered.toLocaleString(),
      icon: "✅",
      color: "bg-green-50 text-green-700",
    },
    {
      title: "Texts Received",
      value: totals.texts_received.toLocaleString(),
      icon: "📥",
      color: "bg-purple-50 text-purple-700",
    },
    {
      title: "Active Conversations",
      value: totals.conversations_active.toLocaleString(),
      icon: "💬",
      color: "bg-orange-50 text-orange-700",
    },
    {
      title: "Delivery Rate",
      value: formatPercentage(totals.delivery_rate),
      icon: "🎯",
      color: "bg-emerald-50 text-emerald-700",
    },
    {
      title: "Response Rate",
      value: formatPercentage(totals.response_rate),
      icon: "📈",
      color: "bg-indigo-50 text-indigo-700",
    },
    {
      title: "Engagement Rate",
      value: formatPercentage(totals.engagement_rate),
      icon: "🤝",
      color: "bg-cyan-50 text-cyan-700",
    },
    {
      title: "Unique Contacts",
      value: totals.unique_contacts_texted.toLocaleString(),
      icon: "👥",
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
          {summaryStats.top_performer ? (
            <div className="space-y-2">
              <div className="flex justify-between">
                <span className="text-gray-600">Agent:</span>
                <span className="font-medium">{summaryStats.top_performer?.agent_name}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-600">Texts Sent:</span>
                <span className="font-medium">{summaryStats.top_performer?.texts_sent}</span>
              </div>
            </div>
          ) : (
            <p className="text-gray-500">No activity in this period</p>
          )}
        </div>

        {/* Team Averages */}
        <div className="bg-white rounded-lg shadow p-6">
          <h3 className="text-lg font-semibold text-gray-900 mb-4">📊 Team Averages</h3>
          <div className="space-y-2">
            <div className="flex justify-between">
              <span className="text-gray-600">Texts per Agent:</span>
              <span className="font-medium">{summaryStats.team_averages?.avg_texts_per_agent?.toFixed(1)}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-gray-600">Delivery Rate:</span>
              <span className="font-medium">{formatPercentage(summaryStats.team_averages?.avg_delivery_rate)}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-gray-600">Response Rate:</span>
              <span className="font-medium">{formatPercentage(summaryStats.team_averages?.avg_response_rate)}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-gray-600">Engagement Rate:</span>
              <span className="font-medium">{formatPercentage(summaryStats.team_averages?.avg_engagement_rate)}</span>
            </div>
          </div>
        </div>
      </div>

      {/* Error Summary */}
      {(totals.opt_outs > 0 || totals.carrier_filtered > 0 || totals.other_errors > 0) && (
        <div className="bg-white rounded-lg shadow p-6">
          <h3 className="text-lg font-semibold text-gray-900 mb-4">⚠️ Delivery Issues</h3>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div className="text-center">
              <div className="text-2xl font-bold text-red-600">{totals.opt_outs}</div>
              <div className="text-sm text-gray-600">Opt Outs</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-orange-600">{totals.carrier_filtered}</div>
              <div className="text-sm text-gray-600">Carrier Filtered</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-yellow-600">{totals.other_errors}</div>
              <div className="text-sm text-gray-600">Other Errors</div>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}
