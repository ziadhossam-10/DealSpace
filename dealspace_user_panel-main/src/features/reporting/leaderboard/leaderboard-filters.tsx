"use client"

import { useState, useEffect } from "react"
import { ChevronDown } from "lucide-react"
import type { LeaderboardOptionsResponse } from "./leaderboard-api"

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

interface LeaderboardFiltersProps {
  timeframe: string
  limit: number
  selectedStage?: number
  selectedType?: number
  selectedTeam?: number
  onTimeframeChange: (timeframe: string) => void
  onLimitChange: (limit: number) => void
  onStageChange: (stageId?: number) => void
  onTypeChange: (typeId?: number) => void
  onTeamChange: (teamId?: number) => void
  options?: LeaderboardOptionsResponse
}

export default function LeaderboardFilters({
  timeframe,
  limit,
  selectedStage,
  selectedType,
  selectedTeam,
  onTimeframeChange,
  onLimitChange,
  onStageChange,
  onTypeChange,
  onTeamChange,
  options,
}: LeaderboardFiltersProps) {
  const [stageDropdownOpen, setStageDropdownOpen] = useState(false)
  const [typeDropdownOpen, setTypeDropdownOpen] = useState(false)
  const [teamDropdownOpen, setTeamDropdownOpen] = useState(false)

  // Close dropdowns when clicking outside
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      const target = event.target as Element
      if (!target.closest(".stage-dropdown")) {
        setStageDropdownOpen(false)
      }
      if (!target.closest(".type-dropdown")) {
        setTypeDropdownOpen(false)
      }
      if (!target.closest(".team-dropdown")) {
        setTeamDropdownOpen(false)
      }
    }
    document.addEventListener("mousedown", handleClickOutside)
    return () => document.removeEventListener("mousedown", handleClickOutside)
  }, [])

  const getSelectedStageText = () => {
    if (!selectedStage) return "All Pipelines"
    const stage = options?.stages.find((s) => s.id === selectedStage)
    return stage?.name || "Unknown Stage"
  }

  const getSelectedTypeText = () => {
    if (!selectedType) return "All Types"
    const type = options?.types.find((t) => t.id === selectedType)
    return type?.name || "Unknown Type"
  }

  const getSelectedTeamText = () => {
    if (!selectedTeam) return "Everyone"
    const team = options?.teams.find((t) => t.id === selectedTeam)
    return team?.name || "Unknown Team"
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

        {/* Stage Filter */}
        <div className="relative stage-dropdown">
          <button
            onClick={() => setStageDropdownOpen(!stageDropdownOpen)}
            className="flex items-center gap-2 border border-gray-300 rounded px-3 py-2 text-sm hover:bg-gray-50"
          >
            {getSelectedStageText()}
            <ChevronDown className="w-4 h-4 text-gray-500" />
          </button>
          {stageDropdownOpen && (
            <div className="absolute top-full left-0 mt-1 w-48 bg-white border border-gray-300 rounded-md shadow-lg z-10 max-h-60 overflow-y-auto">
              <div className="p-2">
                <button
                  onClick={() => {
                    onStageChange(undefined)
                    setStageDropdownOpen(false)
                  }}
                  className={`w-full text-left p-2 hover:bg-gray-50 rounded text-sm ${
                    !selectedStage ? "bg-blue-50 text-blue-600" : ""
                  }`}
                >
                  All Pipelines
                </button>
                {options?.stages.map((stage) => (
                  <button
                    key={stage.id}
                    onClick={() => {
                      onStageChange(stage.id)
                      setStageDropdownOpen(false)
                    }}
                    className={`w-full text-left p-2 hover:bg-gray-50 rounded text-sm ${
                      selectedStage === stage.id ? "bg-blue-50 text-blue-600" : ""
                    }`}
                  >
                    {stage.name}
                  </button>
                ))}
              </div>
            </div>
          )}
        </div>

        {/* Type Filter */}
        <div className="relative type-dropdown">
          <button
            onClick={() => setTypeDropdownOpen(!typeDropdownOpen)}
            className="flex items-center gap-2 border border-gray-300 rounded px-3 py-2 text-sm hover:bg-gray-50"
          >
            {getSelectedTypeText()}
            <ChevronDown className="w-4 h-4 text-gray-500" />
          </button>
          {typeDropdownOpen && (
            <div className="absolute top-full left-0 mt-1 w-48 bg-white border border-gray-300 rounded-md shadow-lg z-10 max-h-60 overflow-y-auto">
              <div className="p-2">
                <button
                  onClick={() => {
                    onTypeChange(undefined)
                    setTypeDropdownOpen(false)
                  }}
                  className={`w-full text-left p-2 hover:bg-gray-50 rounded text-sm ${
                    !selectedType ? "bg-blue-50 text-blue-600" : ""
                  }`}
                >
                  All Types
                </button>
                {options?.types.map((type) => (
                  <button
                    key={type.id}
                    onClick={() => {
                      onTypeChange(type.id)
                      setTypeDropdownOpen(false)
                    }}
                    className={`w-full text-left p-2 hover:bg-gray-50 rounded text-sm ${
                      selectedType === type.id ? "bg-blue-50 text-blue-600" : ""
                    }`}
                  >
                    {type.name}
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
