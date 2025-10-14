"use client"

import { useState, useEffect } from "react"
import { Calendar, Download, Filter, ChevronDown, Search, X } from "lucide-react"
import ReportsLayout from "../../../layout/ReportLayout"
import { useGetLeadSourceQuery, useDownloadLeadSourceExcelMutation } from "./leadSourceApi"
import { useGetUsersQuery } from "../../users/usersApi"
import { useGetStagesQuery } from "../../stages/stagesApi"
import LeadSourceMetrics from "./leadSourceMetrics"
import LeadSourceTable from "./leadSourceTable"
import LeadSourcePredefinedReportsSelector, {
  PREDEFINED_REPORTS,
  type PredefinedReport,
} from "./leadSourcePredefinedReportsSelector"
import LeadSourceChart from "./leadSourceChart"

// Helper function to format date to DD-MM-YYYY
const formatDateForAPI = (dateString: string) => {
  const date = new Date(dateString)
  const day = date.getDate().toString().padStart(2, "0")
  const month = (date.getMonth() + 1).toString().padStart(2, "0")
  const year = date.getFullYear()
  return `${day}-${month}-${year}`
}

export default function LeadSourceReport() {
  const [dateRange, setDateRange] = useState({
    start: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split("T")[0],
    end: new Date().toISOString().split("T")[0],
  })

  // Filters
  const [selectedLeadSources, setSelectedLeadSources] = useState<string[]>([])
  const [selectedUsers, setSelectedUsers] = useState<{ id: number; name: string; email: string }[]>([])
  const [selectedLeadTypes, setSelectedLeadTypes] = useState<number[]>([])

  // Dropdown states
  const [leadSourceDropdownOpen, setLeadSourceDropdownOpen] = useState(false)
  const [userDropdownOpen, setUserDropdownOpen] = useState(false)
  const [leadTypeDropdownOpen, setLeadTypeDropdownOpen] = useState(false)

  // Search states
  const [leadSourceSearchTerm, setLeadSourceSearchTerm] = useState("")
  const [userSearchTerm, setUserSearchTerm] = useState("")

  const [selectedReport, setSelectedReport] = useState<PredefinedReport>(PREDEFINED_REPORTS[0])
  const [downloadLeadSourceExcel] = useDownloadLeadSourceExcelMutation()

  // Get users with search
  const { data: usersData, isLoading: isLoadingUsers } = useGetUsersQuery(
    {
      page: 1,
      per_page: 50,
      search: userSearchTerm || undefined,
    },
    {
      skip: !userDropdownOpen && !userSearchTerm,
    },
  )

  // Get lead types (stages) - no search, load all
  const { data: stagesData } = useGetStagesQuery()

  const users = usersData?.data?.items || []
  const leadTypes = stagesData?.data || []

  const { data, isLoading, error, refetch } = useGetLeadSourceQuery({
    start_date: formatDateForAPI(dateRange.start),
    end_date: formatDateForAPI(dateRange.end),
    lead_sources: selectedLeadSources.length > 0 ? selectedLeadSources : undefined,
    user_ids: selectedUsers.length > 0 ? selectedUsers.map((u) => u.id) : undefined,
    lead_types: selectedLeadTypes.length > 0 ? selectedLeadTypes : undefined,
  })

  const reportData = data

  // Get unique lead sources from the data for filtering
  const availableLeadSources = reportData?.lead_sources.map((source) => source.lead_source) || []
  const filteredLeadSources = availableLeadSources.filter((source) =>
    source.toLowerCase().includes(leadSourceSearchTerm.toLowerCase()),
  )

  const handleDateRangeChange = (field: "start" | "end", value: string) => {
    setDateRange((prev) => ({ ...prev, [field]: value }))
  }

  const handleLeadSourceToggle = (leadSource: string) => {
    setSelectedLeadSources((prev) =>
      prev.includes(leadSource) ? prev.filter((ls) => ls !== leadSource) : [...prev, leadSource],
    )
  }

  const handleUserToggle = (user: { id: number; name: string; email: string }) => {
    setSelectedUsers((prev) => {
      const exists = prev.find((u) => u.id === user.id)
      if (exists) {
        return prev.filter((u) => u.id !== user.id)
      } else {
        return [...prev, user]
      }
    })
  }

  const handleUserRemove = (userId: number) => {
    setSelectedUsers((prev) => prev.filter((u) => u.id !== userId))
  }

  const handleLeadTypeToggle = (leadTypeId: number) => {
    setSelectedLeadTypes((prev) =>
      prev.includes(leadTypeId) ? prev.filter((id) => id !== leadTypeId) : [...prev, leadTypeId],
    )
  }

  const handleExport = async () => {
    try {
      await downloadLeadSourceExcel({
        start_date: formatDateForAPI(dateRange.start),
        end_date: formatDateForAPI(dateRange.end),
        lead_sources: selectedLeadSources.length > 0 ? selectedLeadSources : undefined,
        user_ids: selectedUsers.length > 0 ? selectedUsers.map((u) => u.id) : undefined,
        lead_types: selectedLeadTypes.length > 0 ? selectedLeadTypes : undefined,
      })
    } catch (err) {
      console.error("Failed to download Excel:", err)
    }
  }

  const getSelectedLeadSourcesText = () => {
    if (selectedLeadSources.length === 0) return "All sources"
    if (selectedLeadSources.length === 1) return selectedLeadSources[0]
    return `${selectedLeadSources.length} Sources`
  }

  const getSelectedUsersText = () => {
    if (selectedUsers.length === 0) return "All users"
    if (selectedUsers.length === 1) return selectedUsers[0].name
    return `${selectedUsers.length} Users`
  }

  const getSelectedLeadTypesText = () => {
    if (selectedLeadTypes.length === 0) return "All lead types"
    if (selectedLeadTypes.length === 1) {
      const leadType = leadTypes.find((lt) => lt.id === selectedLeadTypes[0])
      return leadType?.name || "1 Lead Type"
    }
    return `${selectedLeadTypes.length} Lead Types`
  }

  // Close dropdowns when clicking outside
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      const target = event.target as Element
      if (!target.closest(".leadsource-dropdown")) {
        setLeadSourceDropdownOpen(false)
      }
      if (!target.closest(".user-dropdown")) {
        setUserDropdownOpen(false)
      }
      if (!target.closest(".leadtype-dropdown")) {
        setLeadTypeDropdownOpen(false)
      }
    }
    document.addEventListener("mousedown", handleClickOutside)
    return () => document.removeEventListener("mousedown", handleClickOutside)
  }, [])

  if (error) {
    return (
      <ReportsLayout>
        <div className="text-center py-12">
          <p className="text-red-600">Error loading lead source data. Please try again.</p>
          <button onClick={() => refetch()} className="mt-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
            Retry
          </button>
        </div>
      </ReportsLayout>
    )
  }

  return (
    <ReportsLayout>
      <div className="space-y-6">
        {/* Header */}
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-bold text-gray-900">Lead Source Report</h1>
            <p className="text-gray-600">Analyze lead performance and conversion rates by source.</p>
          </div>
          <div className="flex items-center gap-3">
            <button
              onClick={handleExport}
              className="flex items-center px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
            >
              <Download className="w-4 h-4 mr-2" />
              Export
            </button>
          </div>
        </div>

        {/* Filters */}
        <div className="bg-white rounded-lg shadow p-6">
          <div className="flex flex-wrap items-center gap-4">
            <div className="flex items-center gap-2">
              <Calendar className="w-4 h-4 text-gray-500" />
              <input
                type="date"
                value={dateRange.start}
                onChange={(e) => handleDateRangeChange("start", e.target.value)}
                className="border border-gray-300 rounded px-3 py-2 text-sm"
              />
              <span className="text-gray-500">to</span>
              <input
                type="date"
                value={dateRange.end}
                onChange={(e) => handleDateRangeChange("end", e.target.value)}
                className="border border-gray-300 rounded px-3 py-2 text-sm"
              />
            </div>

            {/* Lead Source Selector */}
            <div className="relative leadsource-dropdown">
              <button
                onClick={() => setLeadSourceDropdownOpen(!leadSourceDropdownOpen)}
                className="flex items-center gap-2 border border-gray-300 rounded px-3 py-2 text-sm hover:bg-gray-50"
              >
                <Filter className="w-4 h-4 text-gray-500" />
                {getSelectedLeadSourcesText()}
                <ChevronDown className="w-4 h-4 text-gray-500" />
              </button>
              {leadSourceDropdownOpen && (
                <div className="absolute top-full left-0 mt-1 w-64 bg-white border border-gray-300 rounded-md shadow-lg z-10 max-h-60 overflow-hidden">
                  <div className="p-3 border-b">
                    <div className="relative">
                      <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
                      <input
                        type="text"
                        placeholder="Search lead sources..."
                        value={leadSourceSearchTerm}
                        onChange={(e) => setLeadSourceSearchTerm(e.target.value)}
                        className="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                      />
                    </div>
                  </div>
                  <div className="max-h-40 overflow-y-auto p-2">
                    <label className="flex items-center p-2 hover:bg-gray-50 rounded">
                      <input
                        type="checkbox"
                        checked={selectedLeadSources.length === 0}
                        onChange={() => setSelectedLeadSources([])}
                        className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                      />
                      <span className="ml-2 text-sm">All sources</span>
                    </label>
                    {filteredLeadSources.map((source) => (
                      <label key={source} className="flex items-center p-2 hover:bg-gray-50 rounded">
                        <input
                          type="checkbox"
                          checked={selectedLeadSources.includes(source)}
                          onChange={() => handleLeadSourceToggle(source)}
                          className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        />
                        <span className="ml-2 text-sm">{source}</span>
                      </label>
                    ))}
                  </div>
                </div>
              )}
            </div>

            {/* User Selector */}
            <div className="relative user-dropdown">
              <button
                onClick={() => setUserDropdownOpen(!userDropdownOpen)}
                className="flex items-center gap-2 border border-gray-300 rounded px-3 py-2 text-sm hover:bg-gray-50"
              >
                <Filter className="w-4 h-4 text-gray-500" />
                {getSelectedUsersText()}
                <ChevronDown className="w-4 h-4 text-gray-500" />
              </button>
              {userDropdownOpen && (
                <div className="absolute top-full left-0 mt-1 w-80 bg-white border border-gray-300 rounded-md shadow-lg z-10 max-h-60 overflow-hidden">
                  <div className="p-3 border-b">
                    <div className="relative">
                      <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
                      <input
                        type="text"
                        placeholder="Search users..."
                        value={userSearchTerm}
                        onChange={(e) => setUserSearchTerm(e.target.value)}
                        className="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                      />
                    </div>
                  </div>
                  <div className="max-h-40 overflow-y-auto p-2">
                    <label className="flex items-center p-2 hover:bg-gray-50 rounded">
                      <input
                        type="checkbox"
                        checked={selectedUsers.length === 0}
                        onChange={() => setSelectedUsers([])}
                        className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                      />
                      <span className="ml-2 text-sm">All users</span>
                    </label>
                    {isLoadingUsers ? (
                      <div className="p-2 text-center text-gray-500">Loading users...</div>
                    ) : (
                      users.map((user) => (
                        <label key={user.id} className="flex items-center p-2 hover:bg-gray-50 rounded">
                          <input
                            type="checkbox"
                            checked={selectedUsers.some((u) => u.id === user.id)}
                            onChange={() => handleUserToggle(user)}
                            className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                          />
                          <div className="ml-2 flex-1">
                            <div className="text-sm font-medium">{user.name}</div>
                            <div className="text-xs text-gray-500">{user.email}</div>
                          </div>
                        </label>
                      ))
                    )}
                  </div>
                </div>
              )}
            </div>

            {/* Lead Type Selector */}
            <div className="relative leadtype-dropdown">
              <button
                onClick={() => setLeadTypeDropdownOpen(!leadTypeDropdownOpen)}
                className="flex items-center gap-2 border border-gray-300 rounded px-3 py-2 text-sm hover:bg-gray-50"
              >
                <Filter className="w-4 h-4 text-gray-500" />
                {getSelectedLeadTypesText()}
                <ChevronDown className="w-4 h-4 text-gray-500" />
              </button>
              {leadTypeDropdownOpen && (
                <div className="absolute top-full left-0 mt-1 w-64 bg-white border border-gray-300 rounded-md shadow-lg z-10 max-h-60 overflow-y-auto">
                  <div className="p-2">
                    <label className="flex items-center p-2 hover:bg-gray-50 rounded">
                      <input
                        type="checkbox"
                        checked={selectedLeadTypes.length === 0}
                        onChange={() => setSelectedLeadTypes([])}
                        className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                      />
                      <span className="ml-2 text-sm">All lead types</span>
                    </label>
                    {leadTypes.map((leadType) => (
                      <label key={leadType.id} className="flex items-center p-2 hover:bg-gray-50 rounded">
                        <input
                          type="checkbox"
                          checked={selectedLeadTypes.includes(leadType.id)}
                          onChange={() => handleLeadTypeToggle(leadType.id)}
                          className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        />
                        <span className="ml-2 text-sm">{leadType.name}</span>
                      </label>
                    ))}
                  </div>
                </div>
              )}
            </div>
          </div>

          {/* Selected Filters Display */}
          {(selectedLeadSources.length > 0 || selectedUsers.length > 0 || selectedLeadTypes.length > 0) && (
            <div className="flex items-center gap-2 mt-4">
              {selectedLeadSources.map((source) => (
                <div
                  key={source}
                  className="flex items-center bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-sm"
                >
                  Source: {source}
                  <button
                    onClick={() => handleLeadSourceToggle(source)}
                    className="ml-1 hover:bg-blue-200 rounded-full p-0.5"
                  >
                    <X className="w-3 h-3" />
                  </button>
                </div>
              ))}
              {selectedUsers.map((user) => (
                <div
                  key={user.id}
                  className="flex items-center bg-gray-100 text-gray-800 px-2 py-1 rounded-full text-sm"
                >
                  {user.name}
                  <button
                    onClick={() => handleUserRemove(user.id)}
                    className="ml-1 hover:bg-gray-200 rounded-full p-0.5"
                  >
                    <X className="w-3 h-3" />
                  </button>
                </div>
              ))}
            </div>
          )}

          <div className="mt-4 text-sm text-gray-500">
            Reporting results may be cached for up to 10 minutes.
            <button className="text-blue-600 hover:underline ml-1" onClick={() => refetch()}>
              Refresh results.
            </button>
          </div>
        </div>

        {/* Predefined Reports Selector */}
        <LeadSourcePredefinedReportsSelector selectedReport={selectedReport} onReportChange={setSelectedReport} />

        {/* Chart Visualization */}
        <LeadSourceChart
          leadSources={reportData?.lead_sources || []}
          selectedReport={selectedReport}
          isLoading={isLoading}
        />

        {/* Metrics Cards */}
        {reportData && <LeadSourceMetrics totals={reportData.totals} />}

        {/* Data Table */}
        <LeadSourceTable leadSources={reportData?.lead_sources || []} isLoading={isLoading} />

        {/* Additional Info */}
        <div className="text-sm text-gray-500 text-center">
          Period: {reportData?.period.start} to {reportData?.period.end}
        </div>
      </div>
    </ReportsLayout>
  )
}
