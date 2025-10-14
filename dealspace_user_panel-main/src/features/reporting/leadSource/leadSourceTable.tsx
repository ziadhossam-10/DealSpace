"use client"

import { useState } from "react"
import { ChevronDown, Plus } from "lucide-react"
import type { LeadSourceData } from "./leadSourceApi"
import LeadSourceCustomizeColumnsModal from "./leadSourceCustomizeColumns"

interface LeadSourceTableProps {
  leadSources: LeadSourceData[]
  isLoading?: boolean
}

const DEFAULT_VISIBLE_COLUMNS = [
  "new_leads",
  "calls",
  "emails",
  "texts",
  "appointments",
  "deals_closed",
  "deal_value",
  "conversion_rate",
  "response_rate",
  "website_registrations",
]

export default function LeadSourceTable({ leadSources, isLoading }: LeadSourceTableProps) {
  const [visibleColumns, setVisibleColumns] = useState<string[]>(DEFAULT_VISIBLE_COLUMNS)
  const [isCustomizeModalOpen, setIsCustomizeModalOpen] = useState(false)
  const [sortConfig, setSortConfig] = useState<{ key: string; direction: "asc" | "desc" } | null>(null)

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

  const getColumnValue = (source: LeadSourceData, column: string) => {
    switch (column) {
      case "avg_speed_to_action":
      case "avg_speed_to_first_call":
      case "avg_speed_to_first_email":
      case "avg_speed_to_first_text":
        return formatTime(source[column as keyof LeadSourceData] as number)
      case "response_rate":
      case "phone_response_rate":
      case "email_response_rate":
      case "text_response_rate":
      case "conversion_rate":
        return formatPercentage(source[column as keyof LeadSourceData] as number)
      case "deal_value":
      case "deal_commission":
        return formatCurrency(source[column as keyof LeadSourceData] as number)
      default:
        return source[column as keyof LeadSourceData]?.toString() || "0"
    }
  }

  const getColumnHeader = (column: string) => {
    const headers: Record<string, string> = {
      new_leads: "New Leads",
      calls: "Calls",
      emails: "Emails",
      texts: "Texts",
      notes: "Notes",
      tasks_completed: "Tasks Completed",
      appointments: "Appointments",
      leads_not_acted_on: "Not Acted On",
      leads_not_called: "Not Called",
      leads_not_emailed: "Not Emailed",
      leads_not_texted: "Not Texted",
      avg_speed_to_action: "Avg. Speed to Action",
      avg_speed_to_first_call: "Avg. Speed to First Call",
      avg_speed_to_first_email: "Avg. Speed to First Email",
      avg_speed_to_first_text: "Avg. Speed to First Text",
      avg_contact_attempts: "Avg. Contact Attempts",
      avg_call_attempts: "Avg. Call Attempts",
      avg_email_attempts: "Avg. Email Attempts",
      avg_text_attempts: "Avg. Text Attempts",
      response_rate: "Response Rate",
      phone_response_rate: "Phone Response Rate",
      email_response_rate: "Email Response Rate",
      text_response_rate: "Text Response Rate",
      deals_closed: "Deals Closed",
      deal_value: "Deal Value",
      deal_commission: "Deal Commission",
      conversion_rate: "Conversion Rate",
      website_registrations: "Website Registrations",
      inquiries: "Inquiries",
      properties_viewed: "Properties Viewed",
      properties_saved: "Properties Saved",
      page_views: "Page Views",
    }
    return headers[column] || column
  }

  const handleSort = (column: string) => {
    setSortConfig((current) => ({
      key: column,
      direction: current?.key === column && current.direction === "asc" ? "desc" : "asc",
    }))
  }

  const sortedLeadSources = [...leadSources].sort((a, b) => {
    if (!sortConfig) return 0
    const aValue = a[sortConfig.key as keyof LeadSourceData] as number
    const bValue = b[sortConfig.key as keyof LeadSourceData] as number
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
          <p className="mt-4 text-gray-600">Loading lead source data...</p>
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
                  Lead Source
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
              {sortedLeadSources.map((source, index) => (
                <tr key={index} className="hover:bg-gray-50">
                  <td className="px-6 py-4 whitespace-nowrap sticky left-0 bg-white z-10">
                    <div className="text-sm font-medium text-gray-900">{source.lead_source}</div>
                  </td>
                  {visibleColumns.map((column) => (
                    <td key={column} className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {getColumnValue(source, column)}
                    </td>
                  ))}
                  <td className="px-6 py-4 whitespace-nowrap"></td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
      <LeadSourceCustomizeColumnsModal
        isOpen={isCustomizeModalOpen}
        onClose={() => setIsCustomizeModalOpen(false)}
        visibleColumns={visibleColumns}
        onUpdateColumns={setVisibleColumns}
      />
    </>
  )
}
