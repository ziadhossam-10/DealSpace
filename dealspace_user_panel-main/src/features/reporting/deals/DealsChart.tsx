"use client"

import {
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
  PieChart,
  Pie,
  Cell,
  LineChart,
  Line,
} from "recharts"
import type { DealAgent, DealStageAverage, DealTimeSeriesData } from "./dealsApi"
import type { PredefinedReport } from "./DealsPredefinedReportsSelector"

interface DealsChartProps {
  agents: DealAgent[]
  stageAverages: DealStageAverage[]
  timeSeries: DealTimeSeriesData[]
  selectedReport: PredefinedReport
  isLoading: boolean
}

const COLORS = ["#3B82F6", "#10B981", "#8B5CF6", "#F59E0B", "#EF4444", "#06B6D4", "#84CC16", "#F97316"]

export default function DealsChart({ agents, stageAverages, timeSeries, selectedReport, isLoading }: DealsChartProps) {
  if (isLoading) {
    return (
      <div className="bg-white rounded-lg shadow p-6">
        <h3 className="text-lg font-semibold text-gray-900 mb-4">Deal Visualization</h3>
        <div className="h-80 flex items-center justify-center">
          <div className="animate-pulse text-gray-500">Loading chart...</div>
        </div>
      </div>
    )
  }

  if (agents.length === 0 && stageAverages.length === 0) {
    return (
      <div className="bg-white rounded-lg shadow p-6">
        <h3 className="text-lg font-semibold text-gray-900 mb-4">Deal Visualization</h3>
        <div className="h-80 flex items-center justify-center">
          <p className="text-gray-500">No data available for visualization.</p>
        </div>
      </div>
    )
  }

  const formatCurrency = (value: number) => {
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

  // Prepare data based on selected report
  let chartData: any[] = []
  let chartTitle = ""

  switch (selectedReport.key) {
    case "agent_performance":
      chartTitle = "Agent Performance"
      chartData = agents.map((agent) => ({
        name: agent.agent_name,
        deals_created: agent.deals_created,
        deals_closed: agent.deals_closed_won,
        total_value:
          typeof agent.total_deal_value === "string"
            ? Number.parseFloat(agent.total_deal_value)
            : agent.total_deal_value,
        close_rate: agent.close_rate,
      }))
      break

    case "pipeline_analysis":
      chartTitle = "Pipeline Analysis by Stage"
      chartData = stageAverages
        .filter((stage) => stage.deal_count > 0)
        .map((stage) => ({
          name: stage.stage_name,
          deal_count: stage.deal_count,
          total_value: stage.total_value,
          avg_value: stage.avg_deal_value,
        }))
      break

    case "deal_value_trends":
      chartTitle = "Deal Value Trends Over Time"
      chartData = timeSeries.map((item) => ({
        date: new Date(item.date).toLocaleDateString(),
        deals_created: item.deals_created,
        deals_closed: item.deals_closed_won,
        total_value: typeof item.total_value === "string" ? Number.parseFloat(item.total_value) : item.total_value,
        closed_value: item.closed_value,
      }))
      break

    case "conversion_rates":
      chartTitle = "Conversion Rates by Agent"
      chartData = agents.map((agent) => ({
        name: agent.agent_name,
        close_rate: agent.close_rate,
        win_rate: agent.win_rate,
        deals_created: agent.deals_created,
        deals_closed: agent.deals_closed_won,
      }))
      break

    default:
      chartTitle = "Deals Overview"
      chartData = agents.map((agent) => ({
        name: agent.agent_name.length > 15 ? agent.agent_name.substring(0, 15) + "..." : agent.agent_name,
        deals_created: agent.deals_created,
        deals_closed: agent.deals_closed_won,
        pipeline: agent.deals_in_pipeline,
        value:
          typeof agent.total_deal_value === "string"
            ? Number.parseFloat(agent.total_deal_value)
            : agent.total_deal_value,
      }))
  }

  // Render different chart types based on the report
  const renderChart = () => {
    if (selectedReport.key === "pipeline_analysis") {
      return (
        <ResponsiveContainer width="100%" height={300}>
          <PieChart>
            <Pie
              data={chartData}
              cx="50%"
              cy="50%"
              labelLine={false}
              label={({ name, value, percent }) => `${name}: ${value} (${((percent || 0) * 100).toFixed(0)}%)`}
              outerRadius={80}
              fill="#8884d8"
              dataKey="deal_count"
            >
              {chartData.map((entry, index) => (
                <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
              ))}
            </Pie>
            <Tooltip formatter={(value) => [formatNumber(value as number), "Deals"]} />
          </PieChart>
        </ResponsiveContainer>
      )
    }

    if (selectedReport.key === "deal_value_trends") {
      return (
        <ResponsiveContainer width="100%" height={300}>
          <LineChart data={chartData} margin={{ top: 20, right: 30, left: 20, bottom: 5 }}>
            <CartesianGrid strokeDasharray="3 3" />
            <XAxis dataKey="date" />
            <YAxis tickFormatter={formatCurrency} />
            <Tooltip
              formatter={(value, name) => {
                if (name === "total_value" || name === "closed_value")
                  return [formatCurrency(value as number), name === "total_value" ? "Total Value" : "Closed Value"]
                return [formatNumber(value as number), name === "deals_created" ? "Deals Created" : "Deals Closed"]
              }}
              labelFormatter={(label) => `Date: ${label}`}
            />
            <Line type="monotone" dataKey="total_value" stroke="#8B5CF6" name="total_value" />
            <Line type="monotone" dataKey="closed_value" stroke="#10B981" name="closed_value" />
          </LineChart>
        </ResponsiveContainer>
      )
    }

    if (selectedReport.key === "conversion_rates") {
      return (
        <ResponsiveContainer width="100%" height={300}>
          <BarChart data={chartData} margin={{ top: 20, right: 30, left: 20, bottom: 5 }}>
            <CartesianGrid strokeDasharray="3 3" />
            <XAxis dataKey="name" />
            <YAxis tickFormatter={(value) => `${value}%`} />
            <Tooltip
              formatter={(value, name) => [
                `${(value as number).toFixed(1)}%`,
                name === "close_rate" ? "Close Rate" : "Win Rate",
              ]}
              labelFormatter={(label) => `Agent: ${label}`}
            />
            <Bar dataKey="close_rate" fill="#10B981" name="close_rate" />
            <Bar dataKey="win_rate" fill="#3B82F6" name="win_rate" />
          </BarChart>
        </ResponsiveContainer>
      )
    }

    // Default multi-metric chart
    return (
      <ResponsiveContainer width="100%" height={300}>
        <BarChart data={chartData} margin={{ top: 20, right: 30, left: 20, bottom: 5 }}>
          <CartesianGrid strokeDasharray="3 3" />
          <XAxis dataKey="name" angle={-45} textAnchor="end" height={80} />
          <YAxis />
          <Tooltip
            formatter={(value, name) => {
              if (name === "value") return [formatCurrency(value as number), "Deal Value"]
              return [formatNumber(value as number), name]
            }}
            labelFormatter={(label) => `Agent: ${label}`}
          />
          <Bar dataKey="deals_created" fill="#3B82F6" name="Created" />
          <Bar dataKey="deals_closed" fill="#10B981" name="Closed" />
          <Bar dataKey="pipeline" fill="#8B5CF6" name="Pipeline" />
        </BarChart>
      </ResponsiveContainer>
    )
  }

  return (
    <div className="bg-white rounded-lg shadow p-6">
      <h3 className="text-lg font-semibold text-gray-900 mb-4">{chartTitle}</h3>
      {renderChart()}
    </div>
  )
}
