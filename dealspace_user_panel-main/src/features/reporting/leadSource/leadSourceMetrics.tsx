"use client"

import type { LeadSourceTotals } from "./leadSourceApi"

interface LeadSourceMetricsProps {
  totals: LeadSourceTotals
}

export default function LeadSourceMetrics({ totals }: LeadSourceMetricsProps) {
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
  const formatCurrency = (value: number) => `$${value.toLocaleString()}`

  const metrics = [
    {
      title: "Total New Leads",
      value: totals.new_leads.toLocaleString(),
      icon: "👥",
      color: "bg-blue-50 text-blue-700",
    },
    {
      title: "Total Calls",
      value: totals.calls.toLocaleString(),
      icon: "📞",
      color: "bg-green-50 text-green-700",
    },
    {
      title: "Total Emails",
      value: totals.emails.toLocaleString(),
      icon: "📧",
      color: "bg-purple-50 text-purple-700",
    },
    {
      title: "Total Appointments",
      value: totals.appointments.toLocaleString(),
      icon: "📅",
      color: "bg-orange-50 text-orange-700",
    },
    {
      title: "Deals Closed",
      value: totals.deals_closed.toLocaleString(),
      icon: "💰",
      color: "bg-emerald-50 text-emerald-700",
    },
    {
      title: "Total Deal Value",
      value: formatCurrency(totals.deal_value),
      icon: "💵",
      color: "bg-green-50 text-green-700",
    },
    {
      title: "Avg Response Rate",
      value: formatPercentage(totals.response_rate),
      icon: "📈",
      color: "bg-indigo-50 text-indigo-700",
    },
    {
      title: "Conversion Rate",
      value: formatPercentage(totals.conversion_rate),
      icon: "🎯",
      color: "bg-red-50 text-red-700",
    },
  ]

  return (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
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
  )
}
