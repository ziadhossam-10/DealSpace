"use client"

import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, PieChart, Pie, Cell } from "recharts"
import type { MarketingCampaignData } from "./marketingApi"
import type { PredefinedReport } from "./MarketingPredefinedReportsSelector"

interface MarketingChartProps {
  campaigns: MarketingCampaignData[]
  selectedReport: PredefinedReport
  isLoading: boolean
}

const COLORS = ["#3B82F6", "#10B981", "#8B5CF6", "#F59E0B", "#EF4444", "#06B6D4", "#84CC16", "#F97316"]

export default function MarketingChart({ campaigns, selectedReport, isLoading }: MarketingChartProps) {
  if (isLoading) {
    return (
      <div className="bg-white rounded-lg shadow p-6">
        <h3 className="text-lg font-semibold text-gray-900 mb-4">Campaign Visualization</h3>
        <div className="h-80 flex items-center justify-center">
          <div className="animate-pulse text-gray-500">Loading chart...</div>
        </div>
      </div>
    )
  }

  if (campaigns.length === 0) {
    return (
      <div className="bg-white rounded-lg shadow p-6">
        <h3 className="text-lg font-semibold text-gray-900 mb-4">Campaign Visualization</h3>
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
    case "leads_by_platform":
      chartTitle = "Leads by Platform"
      const platformData = campaigns.reduce(
        (acc, campaign) => {
          const existing = acc.find((item) => item.name === campaign.platform)
          if (existing) {
            existing.value += campaign.leads
          } else {
            acc.push({ name: campaign.platform, value: campaign.leads })
          }
          return acc
        },
        [] as { name: string; value: number }[],
      )
      chartData = platformData
      break

    case "conversion_by_source":
      chartTitle = "Conversion Rate by Source"
      chartData = campaigns.map((campaign) => ({
        name: campaign.source,
        conversion_rate: campaign.leads > 0 ? (campaign.closed_deals / campaign.leads) * 100 : 0,
        leads: campaign.leads,
        deals: campaign.closed_deals,
      }))
      break

    case "deal_value_by_campaign":
      chartTitle = "Deal Value by Campaign"
      chartData = campaigns.map((campaign) => ({
        name: campaign.campaign.length > 20 ? campaign.campaign.substring(0, 20) + "..." : campaign.campaign,
        deal_value: campaign.deal_value,
        deals: campaign.closed_deals,
      }))
      break

    case "appointment_rate":
      chartTitle = "Appointment Rate by Platform"
      chartData = campaigns.map((campaign) => ({
        name: campaign.platform,
        appointment_rate: campaign.leads > 0 ? (campaign.appointments / campaign.leads) * 100 : 0,
        appointments: campaign.appointments,
        leads: campaign.leads,
      }))
      break

    default:
      chartTitle = "Campaign Performance Overview"
      chartData = campaigns.map((campaign) => ({
        name: campaign.campaign.length > 15 ? campaign.campaign.substring(0, 15) + "..." : campaign.campaign,
        leads: campaign.leads,
        appointments: campaign.appointments,
        deals: campaign.closed_deals,
        value: campaign.deal_value,
      }))
  }

  // Render different chart types based on the report
  const renderChart = () => {
    if (selectedReport.key === "leads_by_platform") {
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
              dataKey="value"
            >
              {chartData.map((entry, index) => (
                <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
              ))}
            </Pie>
            <Tooltip formatter={(value) => [formatNumber(value as number), "Leads"]} />
          </PieChart>
        </ResponsiveContainer>
      )
    }

    if (selectedReport.key === "deal_value_by_campaign") {
      return (
        <ResponsiveContainer width="100%" height={300}>
          <BarChart data={chartData} margin={{ top: 20, right: 30, left: 20, bottom: 5 }}>
            <CartesianGrid strokeDasharray="3 3" />
            <XAxis dataKey="name" angle={-45} textAnchor="end" height={80} />
            <YAxis tickFormatter={formatCurrency} />
            <Tooltip
              formatter={(value) => [formatCurrency(value as number), "Deal Value"]}
              labelFormatter={(label) => `Campaign: ${label}`}
            />
            <Bar dataKey="deal_value" fill="#8B5CF6" />
          </BarChart>
        </ResponsiveContainer>
      )
    }

    if (selectedReport.key === "conversion_by_source" || selectedReport.key === "appointment_rate") {
      const dataKey = selectedReport.key === "conversion_by_source" ? "conversion_rate" : "appointment_rate"
      const label = selectedReport.key === "conversion_by_source" ? "Conversion Rate" : "Appointment Rate"

      return (
        <ResponsiveContainer width="100%" height={300}>
          <BarChart data={chartData} margin={{ top: 20, right: 30, left: 20, bottom: 5 }}>
            <CartesianGrid strokeDasharray="3 3" />
            <XAxis dataKey="name" />
            <YAxis tickFormatter={(value) => `${value}%`} />
            <Tooltip
              formatter={(value) => [`${(value as number).toFixed(1)}%`, label]}
              labelFormatter={(label) => `Source: ${label}`}
            />
            <Bar dataKey={dataKey} fill="#10B981" />
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
            labelFormatter={(label) => `Campaign: ${label}`}
          />
          <Bar dataKey="leads" fill="#3B82F6" name="Leads" />
          <Bar dataKey="appointments" fill="#10B981" name="Appointments" />
          <Bar dataKey="deals" fill="#8B5CF6" name="Deals" />
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
