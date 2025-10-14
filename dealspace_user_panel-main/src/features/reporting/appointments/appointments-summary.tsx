"use client"

import { Calendar, Users, CheckCircle, Clock } from "lucide-react"
import type { AppointmentsSummary } from "./appointments-api"

interface AppointmentsSummaryProps {
  summary: AppointmentsSummary
}

export default function AppointmentsSummaryComponent({ summary }: AppointmentsSummaryProps) {
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

  const formatPercentage = (value: number) => `${value.toFixed(1)}%`

  const summaryCards = [
    {
      title: "Total Appointments",
      value: formatNumber(summary.total_appointments),
      subtitle: `${formatNumber(summary.team_performance.avg_appointments_per_agent)} avg per agent`,
      icon: Calendar,
      color: "bg-blue-500",
      textColor: "text-blue-600",
      bgColor: "bg-blue-50",
    },
    {
      title: "Attendance Rate",
      value: formatPercentage(summary.overall_attendance_rate),
      subtitle: `${formatNumber(summary.attended_appointments)} attended`,
      icon: CheckCircle,
      color: "bg-green-500",
      textColor: "text-green-600",
      bgColor: "bg-green-50",
    },
    {
      title: "Conversion Rate",
      value: formatPercentage(summary.overall_conversion_rate),
      subtitle: `${formatPercentage(summary.team_performance.avg_conversion_rate)} avg per agent`,
      icon: Users,
      color: "bg-purple-500",
      textColor: "text-purple-600",
      bgColor: "bg-purple-50",
    },
    {
      title: "Total Value",
      value: formatCurrency(summary.total_appointment_value),
      subtitle: `${formatCurrency(summary.average_appointment_value)} avg per appointment`,
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
        {/* Appointment Status Breakdown */}
        <div className="bg-white rounded-lg shadow p-6">
          <div className="flex items-center gap-2 mb-4">
            <CheckCircle className="w-5 h-5 text-green-600" />
            <h3 className="text-lg font-semibold text-gray-900">Status Breakdown</h3>
          </div>
          <div className="space-y-2">
            <div className="flex justify-between">
              <span className="text-sm text-gray-600">Attended:</span>
              <span className="font-medium text-green-600">{formatNumber(summary.attended_appointments)}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-sm text-gray-600">No Show:</span>
              <span className="font-medium text-red-600">{formatNumber(summary.no_show_appointments)}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-sm text-gray-600">Rescheduled:</span>
              <span className="font-medium text-yellow-600">{formatNumber(summary.rescheduled_appointments)}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-sm text-gray-600">Canceled:</span>
              <span className="font-medium text-gray-600">{formatNumber(summary.canceled_appointments)}</span>
            </div>
          </div>
        </div>

        {/* Upcoming Summary */}
        <div className="bg-white rounded-lg shadow p-6">
          <div className="flex items-center gap-2 mb-4">
            <Clock className="w-5 h-5 text-blue-600" />
            <h3 className="text-lg font-semibold text-gray-900">Upcoming</h3>
          </div>
          <div className="space-y-2">
            <div className="flex justify-between">
              <span className="text-sm text-gray-600">Total Appointments:</span>
              <span className="font-medium">{formatNumber(summary.upcoming_summary.total_appointments)}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-sm text-gray-600">Total Value:</span>
              <span className="font-medium">{formatCurrency(summary.upcoming_summary.total_value)}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-sm text-gray-600">Avg Value:</span>
              <span className="font-medium">{formatCurrency(summary.upcoming_summary.average_value)}</span>
            </div>
          </div>
        </div>

        {/* Team Performance */}
        <div className="bg-white rounded-lg shadow p-6">
          <div className="flex items-center gap-2 mb-4">
            <Users className="w-5 h-5 text-purple-600" />
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
              <span className="text-sm text-gray-600">Avg Attendance:</span>
              <span className="font-medium">{formatPercentage(summary.team_performance.avg_attendance_rate)}</span>
            </div>
          </div>
        </div>
      </div>

      {/* Appointment Types & Outcomes */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        {/* Appointment Types */}
        <div className="bg-white rounded-lg shadow p-6">
          <h3 className="text-lg font-semibold text-gray-900 mb-4">Appointment Types</h3>
          <div className="space-y-2">
            {Object.entries(summary.appointment_types_breakdown).map(([type, count]) => (
              <div key={type} className="flex justify-between">
                <span className="text-sm text-gray-600">{type}:</span>
                <span className="font-medium">{formatNumber(count)}</span>
              </div>
            ))}
          </div>
        </div>

        {/* Outcomes */}
        <div className="bg-white rounded-lg shadow p-6">
          <h3 className="text-lg font-semibold text-gray-900 mb-4">Outcomes</h3>
          <div className="space-y-2">
            {Object.entries(summary.outcomes_breakdown).map(([outcome, count]) => (
              <div key={outcome} className="flex justify-between">
                <span className="text-sm text-gray-600">{outcome}:</span>
                <span className="font-medium">{formatNumber(count)}</span>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  )
}
