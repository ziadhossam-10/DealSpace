"use client"

import { useState, useEffect } from "react"
import { Calendar, Download, Filter, ChevronDown, Search, X } from "lucide-react"
import { useGetDealsQuery, useDownloadDealsExcelMutation } from "./dealsApi"
import DealsPredefinedReportsSelector, {
  PREDEFINED_REPORTS,
  type PredefinedReport,
} from "./DealsPredefinedReportsSelector"
import DealsChart from "./DealsChart"
import DealsMetrics from "./DealsMetrics"
import DealsTable from "./DealsTable"

const DATE_FILTER_OPTIONS = [
  { value: "today", label: "Today" },
  { value: "yesterday", label: "Yesterday" },
  { value: "last_7_days", label: "Last 7 Days" },
  { value: "last_14_days", label: "Last 14 Days" },
  { value: "last_30_days", label: "Last 30 Days" },
  { value: "this_month", label: "This Month" },
  { value: "last_month", label: "Last Month" },
  { value: "this_year", label: "This Year" },
  { value: "custom", label: "Custom Range" },
]

const STATUS_OPTIONS = [
  { value: "all", label: "All Deals" },
  { value: "current", label: "Current Deals" },
  { value: "archived", label: "Archived Deals" },
]

export default function DealsReport() {
  const [dateFilter, setDateFilter] = useState("last_30_days")
  const [customDateRange, setCustomDateRange] = useState({
    start: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split("T")[0],
    end: new Date().toISOString().split("T")[0],
  })

  // Filters
  const [selectedAgents, setSelectedAgents] = useState<number[]>([])
  const [selectedTeam, setSelectedTeam] = useState<number | undefined>()
  const [selectedStage, setSelectedStage] = useState<number | undefined>()
  const [selectedType, setSelectedType] = useState<number | undefined>()
  const [selectedStatus, setSelectedStatus] = useState<"all" | "current" | "archived">("all")

  // Dropdown states
  const [agentDropdownOpen, setAgentDropdownOpen] = useState(false)
  const [stageDropdownOpen, setStageDropdownOpen] = useState(false)
  const [typeDropdownOpen, setTypeDropdownOpen] = useState(false)

  // Search states
  const [agentSearchTerm, setAgentSearchTerm] = useState("")

  const [selectedReport, setSelectedReport] = useState<PredefinedReport>(PREDEFINED_REPORTS[0])
  const [downloadDealsExcel] = useDownloadDealsExcelMutation()

  const queryParams = {
    start_date: dateFilter === "custom" ? customDateRange.start : undefined,
    end_date: dateFilter === "custom" ? customDateRange.end : undefined,
    agent_ids: selectedAgents.length > 0 ? selectedAgents : undefined,
    team_id: selectedTeam,
    stage_id: selectedStage,
    type_id: selectedType,
    status: selectedStatus,
  }

  const { data, isLoading, error, refetch } = useGetDealsQuery(queryParams)

  const reportData = data

  // Get unique values from the data for filtering
  const availableAgents = reportData?.agents || []
  const availableStages = Array.from(
    new Set(reportData?.stage_averages?.map((stage) => ({ id: stage.stage_id, name: stage.stage_name })) || []),
  )
  const availableTypes = Array.from(new Set(reportData?.deals_list?.map((deal) => deal.type) || []))

  const filteredAgents = availableAgents.filter((agent) =>
    agent.agent_name.toLowerCase().includes(agentSearchTerm.toLowerCase()),
  )

  const handleDateRangeChange = (field: "start" | "end", value: string) => {
    setCustomDateRange((prev) => ({ ...prev, [field]: value }))
  }

  const handleAgentToggle = (agentId: number) => {
    setSelectedAgents((prev) => (prev.includes(agentId) ? prev.filter((id) => id !== agentId) : [...prev, agentId]))
  }

  const handleExport = async () => {
    try {
      await downloadDealsExcel(queryParams)
    } catch (err) {
      console.error("Failed to download Excel:", err)
    }
  }

  const getSelectedAgentsText = () => {
    if (selectedAgents.length === 0) return "All agents"
    if (selectedAgents.length === 1) {
      const agent = availableAgents.find((a) => a.agent_id === selectedAgents[0])
      return agent?.agent_name || "Unknown agent"
    }
    return `${selectedAgents.length} Agents`
  }

  // Close dropdowns when clicking outside
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      const target = event.target as Element
      if (!target.closest(".agent-dropdown")) {
        setAgentDropdownOpen(false)
      }
      if (!target.closest(".stage-dropdown")) {
        setStageDropdownOpen(false)
      }
      if (!target.closest(".type-dropdown")) {
        setTypeDropdownOpen(false)
      }
    }
    document.addEventListener("mousedown", handleClickOutside)
    return () => document.removeEventListener("mousedown", handleClickOutside)
  }, [])

  if (error) {
    return (
      <div className="container mx-auto p-6">
        <div className="text-center py-12">
          <p className="text-red-600">Error loading deals data. Please try again.</p>
          <button onClick={() => refetch()} className="mt-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
            Retry
          </button>
        </div>
      </div>
    )
  }

  return (
    <div className="container mx-auto p-6">
      <div className="space-y-6">
        {/* Header */}
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-bold text-gray-900">Deals Report</h1>
            <p className="text-gray-600">Analyze deal performance, agent metrics, and pipeline health.</p>
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
            {/* Date Filter */}
            <div className="flex items-center gap-2">
              <Calendar className="w-4 h-4 text-gray-500" />
              <select
                value={dateFilter}
                onChange={(e) => setDateFilter(e.target.value)}
                className="border border-gray-300 rounded px-3 py-2 text-sm"
              >
                {DATE_FILTER_OPTIONS.map((option) => (
                  <option key={option.value} value={option.value}>
                    {option.label}
                  </option>
                ))}
              </select>
            </div>

            {/* Custom Date Range */}
            {dateFilter === "custom" && (
              <>
                <input
                  type="date"
                  value={customDateRange.start}
                  onChange={(e) => handleDateRangeChange("start", e.target.value)}
                  className="border border-gray-300 rounded px-3 py-2 text-sm"
                />
                <span className="text-gray-500">to</span>
                <input
                  type="date"
                  value={customDateRange.end}
                  onChange={(e) => handleDateRangeChange("end", e.target.value)}
                  className="border border-gray-300 rounded px-3 py-2 text-sm"
                />
              </>
            )}

            {/* Status Filter */}
            <select
              value={selectedStatus}
              onChange={(e) => setSelectedStatus(e.target.value as "all" | "current" | "archived")}
              className="border border-gray-300 rounded px-3 py-2 text-sm"
            >
              {STATUS_OPTIONS.map((option) => (
                <option key={option.value} value={option.value}>
                  {option.label}
                </option>
              ))}
            </select>

            {/* Agent Selector */}
            <div className="relative agent-dropdown">
              <button
                onClick={() => setAgentDropdownOpen(!agentDropdownOpen)}
                className="flex items-center gap-2 border border-gray-300 rounded px-3 py-2 text-sm hover:bg-gray-50"
              >
                <Filter className="w-4 h-4 text-gray-500" />
                {getSelectedAgentsText()}
                <ChevronDown className="w-4 h-4 text-gray-500" />
              </button>
              {agentDropdownOpen && (
                <div className="absolute top-full left-0 mt-1 w-64 bg-white border border-gray-300 rounded-md shadow-lg z-10 max-h-60 overflow-hidden">
                  <div className="p-3 border-b">
                    <div className="relative">
                      <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
                      <input
                        type="text"
                        placeholder="Search agents..."
                        value={agentSearchTerm}
                        onChange={(e) => setAgentSearchTerm(e.target.value)}
                        className="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                      />
                    </div>
                  </div>
                  <div className="max-h-40 overflow-y-auto p-2">
                    <label className="flex items-center p-2 hover:bg-gray-50 rounded">
                      <input
                        type="checkbox"
                        checked={selectedAgents.length === 0}
                        onChange={() => setSelectedAgents([])}
                        className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                      />
                      <span className="ml-2 text-sm">All agents</span>
                    </label>
                    {filteredAgents.map((agent) => (
                      <label key={agent.agent_id} className="flex items-center p-2 hover:bg-gray-50 rounded">
                        <input
                          type="checkbox"
                          checked={selectedAgents.includes(agent.agent_id)}
                          onChange={() => handleAgentToggle(agent.agent_id)}
                          className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        />
                        <span className="ml-2 text-sm">{agent.agent_name}</span>
                      </label>
                    ))}
                  </div>
                </div>
              )}
            </div>
          </div>

          {/* Selected Filters Display */}
          {selectedAgents.length > 0 && (
            <div className="flex items-center gap-2 mt-4">
              {selectedAgents.map((agentId) => {
                const agent = availableAgents.find((a) => a.agent_id === agentId)
                return agent ? (
                  <div
                    key={agentId}
                    className="flex items-center bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-sm"
                  >
                    Agent: {agent.agent_name}
                    <button
                      onClick={() => handleAgentToggle(agentId)}
                      className="ml-1 hover:bg-blue-200 rounded-full p-0.5"
                    >
                      <X className="w-3 h-3" />
                    </button>
                  </div>
                ) : null
              })}
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
        <DealsPredefinedReportsSelector selectedReport={selectedReport} onReportChange={setSelectedReport} />

        {/* Chart Visualization */}
        <DealsChart
          agents={reportData?.agents || []}
          stageAverages={reportData?.stage_averages || []}
          timeSeries={reportData?.time_series || []}
          selectedReport={selectedReport}
          isLoading={isLoading}
        />

        {/* Metrics Cards */}
        {reportData && <DealsMetrics totals={reportData?.totals} />}

        {/* Data Table */}
        <DealsTable agents={reportData?.agents || []} isLoading={isLoading} />

        {/* Additional Info */}
        {reportData?.period && (
          <div className="text-sm text-gray-500 text-center">
            Period: {reportData?.period.start} to {reportData?.period.end}
          </div>
        )}
      </div>
    </div>
  )
}
