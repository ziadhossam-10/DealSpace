"use client"

import { useState } from "react"
import { ChevronDown, Plus } from "lucide-react"
import type { AppointmentAgent } from "./appointments-api"
import AppointmentsCustomizeColumnsModal from "./appointments-customize-columns"

interface AppointmentsTableProps {
  agents: AppointmentAgent[]
  isLoading?: boolean
}

const DEFAULT_VISIBLE_COLUMNS = [
  "total_appointments",
  "attended_appointments",
  "no_show_appointments",
  "attendance_rate",
  "conversion_rate",
  "total_appointment_value",
  "average_appointment_value",
  "upcoming_appointments",
]

export default function AppointmentsTable({ agents, isLoading }: AppointmentsTableProps) {
  const [visibleColumns, setVisibleColumns] = useState<string[]>(DEFAULT_VISIBLE_COLUMNS)
  const [isCustomizeModalOpen, setIsCustomizeModalOpen] = useState(false)
  const [sortConfig, setSortConfig] = useState<{ key: string; direction: "asc" | "desc" } | null>(null)

  const formatPercentage = (value: number) => `${value.toFixed(1)}%`
  const formatCurrency = (value: number) => {
    return new Intl.NumberFormat("en-US", {
      style: "currency",
      currency: "USD",
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }).format(value)
  }

  const getColumnValue = (agent: AppointmentAgent, column: string) => {
    const value = agent[column as keyof AppointmentAgent]

    switch (column) {
      case "attendance_rate":
      case "conversion_rate":
        return formatPercentage(value as number)
      case "total_appointment_value":
      case "average_appointment_value":
        return formatCurrency(value as number)
      case "appointment_types":
      case "outcomes":
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
      total_appointments: "Total Appointments",
      attended_appointments: "Attended",
      no_show_appointments: "No Show",
      rescheduled_appointments: "Rescheduled",
      canceled_appointments: "Canceled",
      attendance_rate: "Attendance Rate",
      conversion_rate: "Conversion Rate",
      total_appointment_value: "Total Value",
      average_appointment_value: "Avg Value",
      upcoming_appointments: "Upcoming",
      overdue_appointments: "Overdue",
      appointment_types: "Types Count",
      outcomes: "Outcomes Count",
    }
    return headers[column] || column
  }

  const getSortValue = (agent: AppointmentAgent, column: string): number => {
    const value = agent[column as keyof AppointmentAgent]

    if (column === "appointment_types" || column === "outcomes") {
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
          <p className="mt-4 text-gray-600">Loading appointments data...</p>
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
                      <div className="text-sm text-gray-500">{agent.agent_email}</div>
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
      <AppointmentsCustomizeColumnsModal
        isOpen={isCustomizeModalOpen}
        onClose={() => setIsCustomizeModalOpen(false)}
        visibleColumns={visibleColumns}
        onUpdateColumns={setVisibleColumns}
      />
    </>
  )
}
