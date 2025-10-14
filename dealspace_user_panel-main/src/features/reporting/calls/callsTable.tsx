"use client"

import { useState } from "react"
import { ChevronDown, Plus } from "lucide-react"
import type { CallsAgentData, TimeData } from "./callsApi"
import CallsCustomizeColumnsModal from "./callsCustomizeColumns"

interface CallsTableProps {
  agents: CallsAgentData[]
  isLoading?: boolean
}

const DEFAULT_VISIBLE_COLUMNS = [
  "calls_made",
  "calls_connected",
  "conversations",
  "total_talk_time",
  "connection_rate",
  "conversation_rate",
  "avg_call_duration",
  "unique_contacts_called",
  "contacts_reached",
]

export default function CallsTable({ agents, isLoading }: CallsTableProps) {
  const [visibleColumns, setVisibleColumns] = useState<string[]>(DEFAULT_VISIBLE_COLUMNS)
  const [isCustomizeModalOpen, setIsCustomizeModalOpen] = useState(false)
  const [sortConfig, setSortConfig] = useState<{ key: string; direction: "asc" | "desc" } | null>(null)

  const formatPercentage = (value: number) => `${value.toFixed(1)}%`

  const getColumnValue = (agent: CallsAgentData, column: string) => {
    const value = agent[column as keyof CallsAgentData]

    switch (column) {
      case "connection_rate":
      case "conversation_rate":
      case "answer_rate":
        return formatPercentage(value as number)
      case "total_talk_time":
      case "avg_call_duration":
      case "avg_conversation_duration":
      case "avg_answer_time":
      case "avg_talk_time_per_day":
        return (value as TimeData)?.formatted || "00:00:00"
      case "avg_calls_per_day":
        return (value as number).toFixed(2)
      case "outcomes":
        if (Array.isArray(value) && value.length === 0) return "0"
        if (typeof value === "object" && value !== null) {
          const total = Object.values(value as Record<string, number>).reduce((sum, count) => sum + count, 0)
          return total.toString()
        }
        return "0"
      default:
        return value?.toString() || "0"
    }
  }

  const getColumnHeader = (column: string) => {
    const headers: Record<string, string> = {
      calls_made: "Calls Made",
      calls_connected: "Calls Connected",
      conversations: "Conversations",
      calls_received: "Calls Received",
      calls_missed: "Calls Missed",
      total_talk_time: "Total Talk Time",
      avg_call_duration: "Avg Call Duration",
      avg_conversation_duration: "Avg Conversation Duration",
      avg_answer_time: "Avg Answer Time",
      connection_rate: "Connection Rate",
      conversation_rate: "Conversation Rate",
      answer_rate: "Answer Rate",
      unique_contacts_called: "Unique Contacts Called",
      contacts_reached: "Contacts Reached",
      avg_calls_per_day: "Avg Calls Per Day",
      avg_talk_time_per_day: "Avg Talk Time Per Day",
      outcomes: "Total Outcomes",
    }
    return headers[column] || column
  }

  const getSortValue = (agent: CallsAgentData, column: string): number => {
    const value = agent[column as keyof CallsAgentData]

    if (column.includes("time") || column.includes("duration")) {
      const timeValue = value as TimeData
      return typeof timeValue.seconds === "string" ? Number.parseFloat(timeValue.seconds) : timeValue.seconds
    }

    if (column === "outcomes") {
      if (Array.isArray(value) && value.length === 0) return 0
      if (typeof value === "object" && value !== null) {
        return Object.values(value as Record<string, number>).reduce((sum, count) => sum + count, 0)
      }
      return 0
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
          <p className="mt-4 text-gray-600">Loading calls data...</p>
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
      <CallsCustomizeColumnsModal
        isOpen={isCustomizeModalOpen}
        onClose={() => setIsCustomizeModalOpen(false)}
        visibleColumns={visibleColumns}
        onUpdateColumns={setVisibleColumns}
      />
    </>
  )
}
