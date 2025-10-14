"use client"

import { useState } from "react"
import { ChevronDown, Plus } from "lucide-react"
import type { TextsAgentData, ResponseTimeData, MessageLengthDistribution } from "./textsApi"
import TextsCustomizeColumnsModal from "./textsCustomizeColumns"

interface TextsTableProps {
  agents: TextsAgentData[]
  isLoading?: boolean
}

const DEFAULT_VISIBLE_COLUMNS = [
  "texts_sent",
  "texts_delivered",
  "texts_received",
  "delivery_rate",
  "response_rate",
  "engagement_rate",
  "unique_contacts_texted",
  "contacts_responded",
  "conversations_initiated",
]

export default function TextsTable({ agents, isLoading }: TextsTableProps) {
  const [visibleColumns, setVisibleColumns] = useState<string[]>(DEFAULT_VISIBLE_COLUMNS)
  const [isCustomizeModalOpen, setIsCustomizeModalOpen] = useState(false)
  const [sortConfig, setSortConfig] = useState<{ key: string; direction: "asc" | "desc" } | null>(null)

  const formatPercentage = (value: number) => `${value.toFixed(1)}%`

  const getColumnValue = (agent: TextsAgentData, column: string) => {
    const value = agent[column as keyof TextsAgentData]

    switch (column) {
      case "delivery_rate":
      case "response_rate":
      case "engagement_rate":
        return formatPercentage(value as number)
      case "avg_response_time":
        return (value as ResponseTimeData)?.formatted || "00:00"
      case "avg_texts_per_day":
      case "avg_responses_per_day":
      case "avg_message_length":
        return (value as number).toFixed(2)
      case "message_length_distribution":
        const dist = value as MessageLengthDistribution
        return `S:${dist.short} M:${dist.medium} L:${dist.long} XL:${dist.very_long}`
      case "texts_by_hour":
        const hourlyData = value as number[]
        const peak = Math.max(...hourlyData)
        const peakHour = hourlyData.indexOf(peak)
        return peak > 0 ? `Peak: ${peakHour}:00 (${peak})` : "No activity"
      default:
        return value?.toString() || "0"
    }
  }

  const getColumnHeader = (column: string) => {
    const headers: Record<string, string> = {
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
      texts_by_hour: "Peak Hour",
      avg_message_length: "Avg Message Length",
      message_length_distribution: "Message Length Dist.",
    }
    return headers[column] || column
  }

  const getSortValue = (agent: TextsAgentData, column: string): number => {
    const value = agent[column as keyof TextsAgentData]

    if (column === "avg_response_time") {
      const timeValue = value as ResponseTimeData
      return timeValue.minutes
    }

    if (column === "texts_by_hour") {
      const hourlyData = value as number[]
      return Math.max(...hourlyData)
    }

    if (column === "message_length_distribution") {
      const dist = value as MessageLengthDistribution
      return dist.short + dist.medium + dist.long + dist.very_long
    }

    return typeof value === "number" ? value : 0
  }

  const handleSort = (column: string) => {
    setSortConfig((current) => ({
      key: column,
      direction: current?.key === column && current.direction === "asc" ? "desc" : "asc",
    }))
  }

  const sortedAgents = [...agents].sort((a, b) => {
    if (!sortConfig) return 0
    const aValue = getSortValue(a, sortConfig.key)
    const bValue = getSortValue(b, sortConfig.key)
    if (sortConfig.direction === "asc") {
      return aValue - bValue
    }
    return bValue - aValue
  })

  if (isLoading) {
    return (
      <div className="bg-white rounded-lg shadow">
        <div className="p-6 text-center">
          <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
          <p className="mt-4 text-gray-600">Loading texts data...</p>
        </div>
      </div>
    )
  }

  return (
    <>
      <div className="bg-white rounded-lg shadow overflow-hidden">
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sticky left-0 bg-gray-50 z-10">
                  Agent
                </th>
                {visibleColumns.map((column) => (
                  <th
                    key={column}
                    className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                    onClick={() => handleSort(column)}
                  >
                    <div className="flex items-center">
                      {getColumnHeader(column)}
                      {sortConfig?.key === column && (
                        <ChevronDown
                          className={`ml-1 w-4 h-4 ${sortConfig.direction === "desc" ? "transform rotate-180" : ""}`}
                        />
                      )}
                    </div>
                  </th>
                ))}
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  <button
                    onClick={() => setIsCustomizeModalOpen(true)}
                    className="flex items-center text-blue-600 hover:text-blue-800"
                  >
                    <Plus className="w-4 h-4 mr-1" />
                    Add Columns
                  </button>
                </th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {sortedAgents.map((agent) => (
                <tr key={agent.agent_id} className="hover:bg-gray-50">
                  <td className="px-6 py-4 whitespace-nowrap sticky left-0 bg-white z-10">
                    <div>
                      <div className="text-sm font-medium text-gray-900">{agent.agent_name}</div>
                      <div className="text-sm text-gray-500">{agent.email}</div>
                    </div>
                  </td>
                  {visibleColumns.map((column) => (
                    <td key={column} className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {getColumnValue(agent, column)}
                    </td>
                  ))}
                  <td className="px-6 py-4 whitespace-nowrap"></td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
      <TextsCustomizeColumnsModal
        isOpen={isCustomizeModalOpen}
        onClose={() => setIsCustomizeModalOpen(false)}
        visibleColumns={visibleColumns}
        onUpdateColumns={setVisibleColumns}
      />
    </>
  )
}
