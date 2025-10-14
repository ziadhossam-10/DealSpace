"use client"

import { useState, useEffect } from "react"
import { ChevronDown } from "lucide-react"
import type { AppointmentsOptionsResponse } from "./appointments-api"

const TIMEFRAME_OPTIONS = [
  { value: "today", label: "Today" },
  { value: "yesterday", label: "Yesterday" },
  { value: "this_week", label: "This Week" },
  { value: "last_week", label: "Last Week" },
  { value: "this_month", label: "This Month" },
  { value: "last_month", label: "Last Month" },
  { value: "this_quarter", label: "This Quarter" },
  { value: "last_quarter", label: "Last Quarter" },
  { value: "this_year", label: "This Year" },
  { value: "all_time", label: "All Time" },
]

const LIMIT_OPTIONS = [
  { value: 5, label: "Top 5" },
  { value: 10, label: "Top 10" },
  { value: 25, label: "Top 25" },
  { value: 50, label: "Top 50" },
  { value: 100, label: "All" },
]

interface AppointmentsFiltersProps {
  timeframe: string
  limit: number
  selectedAppointmentType?: number
  selectedOutcome?: number
  selectedTeam?: number
  selectedStatus?: string
  onTimeframeChange: (timeframe: string) => void
  onLimitChange: (limit: number) => void
  onAppointmentTypeChange: (typeId?: number) => void
  onOutcomeChange: (outcomeId?: number) => void
  onTeamChange: (teamId?: number) => void
  onStatusChange: (status?: string) => void
  options?: AppointmentsOptionsResponse
}

export default function AppointmentsFilters({
  timeframe,
  limit,
  selectedAppointmentType,
  selectedOutcome,
  selectedTeam,
  selectedStatus,
  onTimeframeChange,
  onLimitChange,
  onAppointmentTypeChange,
  onOutcomeChange,
  onTeamChange,
  onStatusChange,
  options,
}: AppointmentsFiltersProps) {
  const [appointmentTypeDropdownOpen, setAppointmentTypeDropdownOpen] = useState(false)
  const [outcomeDropdownOpen, setOutcomeDropdownOpen] = useState(false)
  const [teamDropdownOpen, setTeamDropdownOpen] = useState(false)
  const [statusDropdownOpen, setStatusDropdownOpen] = useState(false)

  // Close dropdowns when clicking outside
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      const target = event.target as Element
      if (!target.closest(".appointment-type-dropdown")) {
        setAppointmentTypeDropdownOpen(false)
      }
      if (!target.closest(".outcome-dropdown")) {
        setOutcomeDropdownOpen(false)
      }
      if (!target.closest(".team-dropdown")) {
        setTeamDropdownOpen(false)
      }
      if (!target.closest(".status-dropdown")) {
        setStatusDropdownOpen(false)
      }
    }
    document.addEventListener("mousedown", handleClickOutside)
    return () => document.removeEventListener("mousedown", handleClickOutside)
  }, [])

  const getSelectedAppointmentTypeText = () => {
    if (!selectedAppointmentType) return "All Types"
    const type = options?.appointment_types.find((t) => t.id === selectedAppointmentType)
    return type?.name || "Unknown Type"
  }

  const getSelectedOutcomeText = () => {
    if (!selectedOutcome) return "All Outcomes"
    const outcome = options?.outcomes.find((o) => o.id === selectedOutcome)
    return outcome?.name || "Unknown Outcome"
  }

  const getSelectedTeamText = () => {
    if (!selectedTeam) return "Everyone"
    const team = options?.teams.find((t) => t.id === selectedTeam)
    return team?.name || "Unknown Team"
  }

  const getSelectedStatusText = () => {
    if (!selectedStatus) return "All Status"
    const status = options?.status_options.find((s) => s.value === selectedStatus)
    return status?.label || "Unknown Status"
  }

  return (
    <div className="bg-white rounded-lg shadow p-6">
      <div className="flex flex-wrap items-center gap-4">
        {/* Timeframe Filter */}
        <select
          value={timeframe}
          onChange={(e) => onTimeframeChange(e.target.value)}
          className="border border-gray-300 rounded px-3 py-2 text-sm"
        >
          {TIMEFRAME_OPTIONS.map((option) => (
            <option key={option.value} value={option.value}>
              {option.label}
            </option>
          ))}
        </select>

        {/* Limit Filter */}
        <select
          value={limit}
          onChange={(e) => onLimitChange(Number(e.target.value))}
          className="border border-gray-300 rounded px-3 py-2 text-sm"
        >
          {LIMIT_OPTIONS.map((option) => (
            <option key={option.value} value={option.value}>
              {option.label}
            </option>
          ))}
        </select>

        {/* Appointment Type Filter */}
        <div className="relative appointment-type-dropdown">
          <button
            onClick={() => setAppointmentTypeDropdownOpen(!appointmentTypeDropdownOpen)}
            className="flex items-center gap-2 border border-gray-300 rounded px-3 py-2 text-sm hover:bg-gray-50"
          >
            {getSelectedAppointmentTypeText()}
            <ChevronDown className="w-4 h-4 text-gray-500" />
          </button>
          {appointmentTypeDropdownOpen && (
            <div className="absolute top-full left-0 mt-1 w-48 bg-white border border-gray-300 rounded-md shadow-lg z-10 max-h-60 overflow-y-auto">
              <div className="p-2">
                <button
                  onClick={() => {
                    onAppointmentTypeChange(undefined)
                    setAppointmentTypeDropdownOpen(false)
                  }}
                  className={`w-full text-left p-2 hover:bg-gray-50 rounded text-sm ${
                    !selectedAppointmentType ? "bg-blue-50 text-blue-600" : ""
                  }`}
                >
                  All Types
                </button>
                {options?.appointment_types.map((type) => (
                  <button
                    key={type.id}
                    onClick={() => {
                      onAppointmentTypeChange(type.id)
                      setAppointmentTypeDropdownOpen(false)
                    }}
                    className={`w-full text-left p-2 hover:bg-gray-50 rounded text-sm ${
                      selectedAppointmentType === type.id ? "bg-blue-50 text-blue-600" : ""
                    }`}
                  >
                    {type.name}
                  </button>
                ))}
              </div>
            </div>
          )}
        </div>

        {/* Outcome Filter */}
        <div className="relative outcome-dropdown">
          <button
            onClick={() => setOutcomeDropdownOpen(!outcomeDropdownOpen)}
            className="flex items-center gap-2 border border-gray-300 rounded px-3 py-2 text-sm hover:bg-gray-50"
          >
            {getSelectedOutcomeText()}
            <ChevronDown className="w-4 h-4 text-gray-500" />
          </button>
          {outcomeDropdownOpen && (
            <div className="absolute top-full left-0 mt-1 w-48 bg-white border border-gray-300 rounded-md shadow-lg z-10 max-h-60 overflow-y-auto">
              <div className="p-2">
                <button
                  onClick={() => {
                    onOutcomeChange(undefined)
                    setOutcomeDropdownOpen(false)
                  }}
                  className={`w-full text-left p-2 hover:bg-gray-50 rounded text-sm ${
                    !selectedOutcome ? "bg-blue-50 text-blue-600" : ""
                  }`}
                >
                  All Outcomes
                </button>
                {options?.outcomes.map((outcome) => (
                  <button
                    key={outcome.id}
                    onClick={() => {
                      onOutcomeChange(outcome.id)
                      setOutcomeDropdownOpen(false)
                    }}
                    className={`w-full text-left p-2 hover:bg-gray-50 rounded text-sm ${
                      selectedOutcome === outcome.id ? "bg-blue-50 text-blue-600" : ""
                    }`}
                  >
                    {outcome.name}
                  </button>
                ))}
              </div>
            </div>
          )}
        </div>

        {/* Status Filter */}
        <div className="relative status-dropdown">
          <button
            onClick={() => setStatusDropdownOpen(!statusDropdownOpen)}
            className="flex items-center gap-2 border border-gray-300 rounded px-3 py-2 text-sm hover:bg-gray-50"
          >
            {getSelectedStatusText()}
            <ChevronDown className="w-4 h-4 text-gray-500" />
          </button>
          {statusDropdownOpen && (
            <div className="absolute top-full left-0 mt-1 w-48 bg-white border border-gray-300 rounded-md shadow-lg z-10 max-h-60 overflow-y-auto">
              <div className="p-2">
                <button
                  onClick={() => {
                    onStatusChange(undefined)
                    setStatusDropdownOpen(false)
                  }}
                  className={`w-full text-left p-2 hover:bg-gray-50 rounded text-sm ${
                    !selectedStatus ? "bg-blue-50 text-blue-600" : ""
                  }`}
                >
                  All Status
                </button>
                {options?.status_options.map((status) => (
                  <button
                    key={status.value}
                    onClick={() => {
                      onStatusChange(status.value)
                      setStatusDropdownOpen(false)
                    }}
                    className={`w-full text-left p-2 hover:bg-gray-50 rounded text-sm ${
                      selectedStatus === status.value ? "bg-blue-50 text-blue-600" : ""
                    }`}
                  >
                    {status.label}
                  </button>
                ))}
              </div>
            </div>
          )}
        </div>

        {/* Team Filter */}
        <div className="relative team-dropdown">
          <button
            onClick={() => setTeamDropdownOpen(!teamDropdownOpen)}
            className="flex items-center gap-2 border border-gray-300 rounded px-3 py-2 text-sm hover:bg-gray-50"
          >
            {getSelectedTeamText()}
            <ChevronDown className="w-4 h-4 text-gray-500" />
          </button>
          {teamDropdownOpen && (
            <div className="absolute top-full left-0 mt-1 w-48 bg-white border border-gray-300 rounded-md shadow-lg z-10 max-h-60 overflow-y-auto">
              <div className="p-2">
                <button
                  onClick={() => {
                    onTeamChange(undefined)
                    setTeamDropdownOpen(false)
                  }}
                  className={`w-full text-left p-2 hover:bg-gray-50 rounded text-sm ${
                    !selectedTeam ? "bg-blue-50 text-blue-600" : ""
                  }`}
                >
                  Everyone
                </button>
                {options?.teams.map((team) => (
                  <button
                    key={team.id}
                    onClick={() => {
                      onTeamChange(team.id)
                      setTeamDropdownOpen(false)
                    }}
                    className={`w-full text-left p-2 hover:bg-gray-50 rounded text-sm ${
                      selectedTeam === team.id ? "bg-blue-50 text-blue-600" : ""
                    }`}
                  >
                    {team.name}
                  </button>
                ))}
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  )
}
