"use client"

import { useMemo } from "react"
import {
  LineChart,
  Line,
  BarChart,
  Bar,
  AreaChart,
  Area,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  Legend,
  ResponsiveContainer,
} from "recharts"
import type { AgentActivityData } from "./agentActivityApi"
import type { PredefinedReport } from "./predefinedReportsSelector"

interface AgentActivityChartProps {
  agents: AgentActivityData[]
  selectedReport: PredefinedReport
  isLoading?: boolean
}

const COLORS = [
  "#3B82F6", // blue
  "#EF4444", // red
  "#10B981", // green
  "#F59E0B", // yellow
  "#8B5CF6", // purple
  "#06B6D4", // cyan
  "#F97316", // orange
  "#84CC16", // lime
]

const formatMetricName = (metric: string) => {
  const names: Record<string, string> = {
    new_leads: "New Leads",
    calls: "Calls",
    emails: "Emails",
    texts: "Texts",
    notes: "Notes",
    tasks_completed: "Tasks Completed",
    appointments: "Appointments",
    appointments_set: "Appointments Set",
    leads_not_acted_on: "Not Acted On",
    leads_not_called: "Not Called",
    leads_not_emailed: "Not Emailed",
    leads_not_texted: "Not Texted",
    avg_speed_to_action: "Avg Speed to Action",
    avg_speed_to_first_call: "Avg Speed to First Call",
    avg_speed_to_first_email: "Avg Speed to First Email",
    avg_contact_attempts: "Avg Contact Attempts",
    avg_call_attempts: "Avg Call Attempts",
    avg_email_attempts: "Avg Email Attempts",
    response_rate: "Response Rate",
    phone_response_rate: "Phone Response Rate",
    email_response_rate: "Email Response Rate",
  }
  return names[metric] || metric
}

const formatValue = (value: number, metric: string) => {
  if (metric.includes("speed_to")) {
    // Format time in seconds to readable format
    if (value === 0) return "0s"
    const hours = Math.floor(value / 3600)
    const minutes = Math.floor((value % 3600) / 60)
    const seconds = Math.floor(value % 60)

    if (hours > 0) return `${hours}h ${minutes}m`
    if (minutes > 0) return `${minutes}m ${seconds}s`
    return `${seconds}s`
  }
  if (metric.includes("rate")) {
    return `${value.toFixed(1)}%`
  }
  return value.toString()
}

export default function AgentActivityChart({ agents, selectedReport, isLoading }: AgentActivityChartProps) {
  const chartData = useMemo(() => {
    return agents.map((agent) => ({
      name: agent.agent_name,
      ...selectedReport.metrics.reduce(
        (acc, metric) => {
          acc[metric] = agent[metric as keyof AgentActivityData] as number
          return acc
        },
        {} as Record<string, number>,
      ),
    }))
  }, [agents, selectedReport])

  const CustomTooltip = ({ active, payload, label }: any) => {
    if (active && payload && payload.length) {
      return (
        <div className="bg-white p-3 border border-gray-300 rounded shadow-lg">
          <p className="font-medium">{label}</p>
          {payload.map((entry: any, index: number) => (
            <p key={index} style={{ color: entry.color }}>
              {formatMetricName(entry.dataKey)}: {formatValue(entry.value, entry.dataKey)}
            </p>
          ))}
        </div>
      )
    }
    return null
  }

  if (isLoading) {
    return (
      <div className="bg-white rounded-lg shadow p-6">
        <div className="h-96 flex items-center justify-center">
          <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
          <span className="ml-3 text-gray-600">Loading chart data...</span>
        </div>
      </div>
    )
  }

  if (agents.length === 0) {
    return (
      <div className="bg-white rounded-lg shadow p-6">
        <div className="h-96 flex items-center justify-center">
          <div className="text-center">
            <div className="text-gray-400 mb-2">📊</div>
            <p className="text-gray-600">No data available for the selected period</p>
          </div>
        </div>
      </div>
    )
  }

  const renderChart = () => {
    const commonProps = {
      data: chartData,
      margin: { top: 20, right: 30, left: 20, bottom: 5 },
    }

    switch (selectedReport.chartType) {
      case "line":
        return (
          <LineChart {...commonProps}>
            <CartesianGrid strokeDasharray="3 3" />
            <XAxis dataKey="name" />
            <YAxis />
            <Tooltip content={<CustomTooltip />} />
            <Legend />
            {selectedReport.metrics.map((metric, index) => (
              <Line
                key={metric}
                type="monotone"
                dataKey={metric}
                stroke={COLORS[index % COLORS.length]}
                strokeWidth={2}
                name={formatMetricName(metric)}
              />
            ))}
          </LineChart>
        )

      case "area":
        return (
          <AreaChart {...commonProps}>
            <CartesianGrid strokeDasharray="3 3" />
            <XAxis dataKey="name" />
            <YAxis />
            <Tooltip content={<CustomTooltip />} />
            <Legend />
            {selectedReport.metrics.map((metric, index) => (
              <Area
                key={metric}
                type="monotone"
                dataKey={metric}
                stackId="1"
                stroke={COLORS[index % COLORS.length]}
                fill={COLORS[index % COLORS.length]}
                fillOpacity={0.6}
                name={formatMetricName(metric)}
              />
            ))}
          </AreaChart>
        )

      case "bar":
      default:
        return (
          <BarChart {...commonProps}>
            <CartesianGrid strokeDasharray="3 3" />
            <XAxis dataKey="name" />
            <YAxis />
            <Tooltip content={<CustomTooltip />} />
            <Legend />
            {selectedReport.metrics.map((metric, index) => (
              <Bar key={metric} dataKey={metric} fill={COLORS[index % COLORS.length]} name={formatMetricName(metric)} />
            ))}
          </BarChart>
        )
    }
  }

  return (
    <div className="bg-white rounded-lg shadow p-6">
      <div className="mb-4">
        <h3 className="text-lg font-semibold text-gray-900">{selectedReport.question}</h3>
        <p className="text-sm text-gray-600">{selectedReport.description}</p>
      </div>
      <div className="h-96">
        <ResponsiveContainer width="100%" height="100%">
          {renderChart()}
        </ResponsiveContainer>
      </div>
    </div>
  )
}
