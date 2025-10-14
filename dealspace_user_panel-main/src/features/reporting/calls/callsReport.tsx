"use client"

import { useState, useEffect } from "react"
import { Calendar, Download, Users, ChevronDown, Search, X } from "lucide-react"
import ReportsLayout from "../../../layout/ReportLayout"
import { useGetCallsQuery, useDownloadCallsExcelMutation } from "./callsApi"
import { useGetUsersQuery } from "../../users/usersApi"
import { useGetTeamsQuery } from "../../teams/teamsApi"
import CallsMetrics from "./callsMetrics"
import CallsTable from "./callsTable"
import CallsPredefinedReportsSelector, {
  PREDEFINED_REPORTS,
  type PredefinedReport,
} from "./callsPredefinedReportsSelector"
import CallsChart from "./callsChart"

// Helper function to format date to DD-MM-YYYY
const formatDateForAPI = (dateString: string) => {
  const date = new Date(dateString)
  const day = date.getDate().toString().padStart(2, "0")
  const month = (date.getMonth() + 1).toString().padStart(2, "0")
  const year = date.getFullYear()
  return `${day}-${month}-${year}`
}

export default function CallsReport() {
  const [dateRange, setDateRange] = useState({
    start: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split("T")[0],
    end: new Date().toISOString().split("T")[0],
  })

  // Team and Agent selection
  const [selectedTeam, setSelectedTeam] = useState<{ id: number; name: string } | null>(null)
  const [selectedAgents, setSelectedAgents] = useState<{ id: number; name: string; email: string }[]>([])

  // Dropdown states
  const [teamDropdownOpen, setTeamDropdownOpen] = useState(false)
  const [agentDropdownOpen, setAgentDropdownOpen] = useState(false)

  // Search states
  const [teamSearchTerm, setTeamSearchTerm] = useState("")
  const [agentSearchTerm, setAgentSearchTerm] = useState("")

  const [selectedReport, setSelectedReport] = useState<PredefinedReport>(PREDEFINED_REPORTS[0])
  const [downloadCallsExcel] = useDownloadCallsExcelMutation()

  // Get teams with search
  const { data: teamsData, isLoading: isLoadingTeams } = useGetTeamsQuery(
    {
      page: 1,
      per_page: 50,
      search: teamSearchTerm || undefined,
    },
    {
      skip: !teamDropdownOpen && !teamSearchTerm,
    },
  )

  // Get agents with search (users with role 2)
  const { data: usersData, isLoading: isLoadingUsers } = useGetUsersQuery(
    {
      page: 1,
      per_page: 50,
      role: 2, // Agent role
      search: agentSearchTerm || undefined,
    },
    {
      skip: !agentDropdownOpen && !agentSearchTerm,
    },
  )

  const teams = teamsData?.data?.items || []
  const agents = usersData?.data?.items || []

  const { data, isLoading, error, refetch } = useGetCallsQuery({
    start_date: formatDateForAPI(dateRange.start),
    end_date: formatDateForAPI(dateRange.end),
    team_id: selectedTeam?.id,
    agent_ids: selectedAgents.length > 0 ? selectedAgents.map((a) => a.id) : undefined,
  })

  const reportData = data

  const handleDateRangeChange = (field: "start" | "end", value: string) => {
    setDateRange((prev) => ({ ...prev, [field]: value }))
  }

  const handleTeamSelect = (team: { id: number; name: string }) => {
    setSelectedTeam(team)
    setSelectedAgents([]) // Clear individual agent selection when team is selected
    setTeamDropdownOpen(false)
    setTeamSearchTerm("")
  }

  const handleTeamClear = () => {
    setSelectedTeam(null)
  }

  const handleAgentToggle = (agent: { id: number; name: string; email: string }) => {
    setSelectedAgents((prev) => {
      const exists = prev.find((a) => a.id === agent.id)
      if (exists) {
        return prev.filter((a) => a.id !== agent.id)
      } else {
        return [...prev, agent]
      }
    })
    setSelectedTeam(null) // Clear team selection when individual agents are selected
  }

  const handleAgentRemove = (agentId: number) => {
    setSelectedAgents((prev) => prev.filter((a) => a.id !== agentId))
  }

  const handleExport = async () => {
    try {
      await downloadCallsExcel({
        start_date: formatDateForAPI(dateRange.start),
        end_date: formatDateForAPI(dateRange.end),
        team_id: selectedTeam?.id,
        agent_ids: selectedAgents.length > 0 ? selectedAgents.map((a) => a.id) : undefined,
      })
    } catch (err) {
      console.error("Failed to download Excel:", err)
    }
  }

  const getSelectedTeamOrAgentsText = () => {
    if (selectedTeam) {
      return selectedTeam.name
    }
    if (selectedAgents.length === 0) return "Everyone"
    if (selectedAgents.length === 1) {
      return selectedAgents[0].name
    }
    return `${selectedAgents.length} Agents`
  }

  // Close dropdowns when clicking outside
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      const target = event.target as Element
      if (!target.closest(".team-dropdown")) {
        setTeamDropdownOpen(false)
      }
      if (!target.closest(".agent-dropdown")) {
        setAgentDropdownOpen(false)
      }
    }
    document.addEventListener("mousedown", handleClickOutside)
    return () => document.removeEventListener("mousedown", handleClickOutside)
  }, [])

  if (error) {
    return (
      <ReportsLayout>
        <div className="text-center py-12">
          <p className="text-red-600">Error loading calls data. Please try again.</p>
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
            <h1 className="text-2xl font-bold text-gray-900">Calls Report</h1>
            <p className="text-gray-600">Analyze call performance, connection rates, and talk time metrics.</p>
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

            {/* Team/Agent Selector */}
            <div className="relative team-dropdown">
              <button
                onClick={() => setTeamDropdownOpen(!teamDropdownOpen)}
                className="flex items-center gap-2 border border-gray-300 rounded px-3 py-2 text-sm hover:bg-gray-50"
              >
                <Users className="w-4 h-4 text-gray-500" />
                {getSelectedTeamOrAgentsText()}
                <ChevronDown className="w-4 h-4 text-gray-500" />
              </button>
              {teamDropdownOpen && (
                <div className="absolute top-full left-0 mt-1 w-80 bg-white border border-gray-300 rounded-md shadow-lg z-10 max-h-96 overflow-hidden">
                  <div className="p-3 border-b">
                    <div className="relative">
                      <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
                      <input
                        type="text"
                        placeholder="Search teams..."
                        value={teamSearchTerm}
                        onChange={(e) => setTeamSearchTerm(e.target.value)}
                        className="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                      />
                    </div>
                  </div>
                  <div className="max-h-60 overflow-y-auto">
                    <div className="p-2">
                      <label className="flex items-center p-2 hover:bg-gray-50 rounded">
                        <input
                          type="radio"
                          checked={!selectedTeam && selectedAgents.length === 0}
                          onChange={() => {
                            setSelectedTeam(null)
                            setSelectedAgents([])
                          }}
                          className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        />
                        <span className="ml-2 text-sm">Everyone</span>
                      </label>

                      {/* Team Selection */}
                      <div className="mt-2 mb-2">
                        <div className="text-xs font-semibold text-gray-500 uppercase tracking-wide px-2 py-1">
                          Teams
                        </div>
                        {isLoadingTeams ? (
                          <div className="p-2 text-center text-gray-500">Loading teams...</div>
                        ) : (
                          teams.map((team) => (
                            <label key={team.id} className="flex items-center p-2 hover:bg-gray-50 rounded">
                              <input
                                type="radio"
                                checked={selectedTeam?.id === team.id}
                                onChange={() => handleTeamSelect(team)}
                                className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                              />
                              <span className="ml-2 text-sm">{team.name}</span>
                            </label>
                          ))
                        )}
                      </div>

                      {/* Individual Agent Selection */}
                      <div className="border-t pt-2">
                        <div className="text-xs font-semibold text-gray-500 uppercase tracking-wide px-2 py-1">
                          Individual Agents
                        </div>
                        <div className="relative mb-2">
                          <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
                          <input
                            type="text"
                            placeholder="Search agents..."
                            value={agentSearchTerm}
                            onChange={(e) => setAgentSearchTerm(e.target.value)}
                            className="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                          />
                        </div>
                        {isLoadingUsers ? (
                          <div className="p-2 text-center text-gray-500">Loading agents...</div>
                        ) : (
                          agents.map((agent) => (
                            <label key={agent.id} className="flex items-center p-2 hover:bg-gray-50 rounded">
                              <input
                                type="checkbox"
                                checked={selectedAgents.some((a) => a.id === agent.id)}
                                onChange={() => handleAgentToggle(agent)}
                                className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                              />
                              <div className="ml-2 flex-1">
                                <div className="text-sm font-medium">{agent.name}</div>
                                <div className="text-xs text-gray-500">{agent.email}</div>
                              </div>
                            </label>
                          ))
                        )}
                      </div>
                    </div>
                  </div>
                </div>
              )}
            </div>
          </div>

          {/* Selected Team/Agents Display */}
          {(selectedTeam || selectedAgents.length > 0) && (
            <div className="flex items-center gap-2 mt-4">
              {selectedTeam && (
                <div className="flex items-center bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-sm">
                  Team: {selectedTeam.name}
                  <button onClick={handleTeamClear} className="ml-1 hover:bg-blue-200 rounded-full p-0.5">
                    <X className="w-3 h-3" />
                  </button>
                </div>
              )}
              {selectedAgents.map((agent) => (
                <div
                  key={agent.id}
                  className="flex items-center bg-gray-100 text-gray-800 px-2 py-1 rounded-full text-sm"
                >
                  {agent.name}
                  <button
                    onClick={() => handleAgentRemove(agent.id)}
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
        <CallsPredefinedReportsSelector selectedReport={selectedReport} onReportChange={setSelectedReport} />

        {/* Chart Visualization */}
        <CallsChart agents={reportData?.agents || []} selectedReport={selectedReport} isLoading={isLoading} />

        {/* Metrics Cards */}
        {reportData && <CallsMetrics totals={reportData.totals} summaryStats={reportData.summary_stats} />}

        {/* Data Table */}
        <CallsTable agents={reportData?.agents || []} isLoading={isLoading} />

        {/* Additional Info */}
        <div className="text-sm text-gray-500 text-center">
          Period: {reportData?.period.start} to {reportData?.period.end}
        </div>
      </div>
    </ReportsLayout>
  )
}
