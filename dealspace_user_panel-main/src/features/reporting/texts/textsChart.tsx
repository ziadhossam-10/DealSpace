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
import type { TextsAgentData, ResponseTimeData } from "./textsApi"
import type { PredefinedReport } from "./textsPredefinedReportsSelector"

interface TextsChartProps {
  agents: TextsAgentData[]
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
    texts_sent: "Texts Sent",
    texts_received: "Texts Received",
    texts_delivered: "Texts Delivered",
    texts_failed: "Texts Failed",
    unique_contacts_texted: "Unique Contacts Texted",
    contacts_responded: "Contacts Responded",
    conversations_initiated: "Conversations Initiated",
    conversations_active: "Active Conversations",
    delivery_rate: "Delivery Rate",
    response_rate: "Response Rate",
    engagement_rate: "Engagement Rate",
    opt_outs: "Opt Outs",
    carrier_filtered: "Carrier Filtered",
    other_errors: "Other Errors",
    avg_texts_per_day: "Avg Texts Per Day",
    avg_responses_per_day: "Avg Responses Per Day",
    avg_response_time: "Avg Response Time",
    avg_message_length: "Avg Message Length",
  }
  return names[metric] || metric
}

const formatValue = (value: any, metric: string) => {
  if (metric.includes("rate")) {
    return `${value.toFixed(1)}%`
  }
  if (metric === "avg_response_time") {
    if (typeof value === "object" && value.formatted) {
      return value.formatted
    }
    return value.toString()
  }
  if (typeof value === "number") {
    return value.toFixed(2)
  }
  return value.toString()
}

const getChartValue = (agent: TextsAgentData, metric: string): number => {
  const value = agent[metric as keyof TextsAgentData]

  if (metric === "avg_response_time") {
    if (typeof value === "object" && (value as ResponseTimeData).minutes) {
      return (value as ResponseTimeData).minutes
    }
  }

  return typeof value === "number" ? value : 0
}

export default function TextsChart({ agents, selectedReport, isLoading }: TextsChartProps) {
  const chartData = useMemo(() => {
    return agents.map((agent) => ({
      name: agent.agent_name,
      ...selectedReport.metrics.reduce(
        (acc, metric) => {
          acc[metric] = getChartValue(agent, metric)
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
            <div className="text-gray-400 mb-2">💬</div>
            <p className="text-gray-600">No text data available for the selected period</p>
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
