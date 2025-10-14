"use client"

import { useState } from "react"
import { Download } from "lucide-react"
import AppointmentsSummaryComponent from "./appointments-summary"
import AppointmentsTable from "./appointments-table"
import AppointmentsFilters from "./appointments-filters"
import AppointmentsPredefinedReportsSelector, {
  PREDEFINED_REPORTS,
  type PredefinedReport,
} from "./appointments-predefined-reports"
import {
  useGetAppointmentsQuery,
  useGetAppointmentsOptionsQuery,
  useExportAppointmentsMutation,
} from "./appointments-api"

export default function AppointmentsReportsPage() {
  const [selectedReport, setSelectedReport] = useState<PredefinedReport>(PREDEFINED_REPORTS[0])
  const [timeframe, setTimeframe] = useState("this_month")
  const [limit, setLimit] = useState(25)
  const [selectedAppointmentType, setSelectedAppointmentType] = useState<number | undefined>()
  const [selectedOutcome, setSelectedOutcome] = useState<number | undefined>()
  const [selectedTeam, setSelectedTeam] = useState<number | undefined>()
  const [selectedStatus, setSelectedStatus] = useState<string | undefined>()

  const { data: appointmentsData, isLoading } = useGetAppointmentsQuery({
    timeframe,
    limit,
    appointment_type_id: selectedAppointmentType,
    outcome_id: selectedOutcome,
    team_id: selectedTeam,
    status: selectedStatus,
  })

  const { data: optionsData } = useGetAppointmentsOptionsQuery()
  const [exportAppointments, { isLoading: isExporting }] = useExportAppointmentsMutation()

  const handleExport = async () => {
    try {
      await exportAppointments({
        timeframe,
        limit,
        appointment_type_id: selectedAppointmentType,
        outcome_id: selectedOutcome,
        team_id: selectedTeam,
        status: selectedStatus,
      }).unwrap()
    } catch (error) {
      console.error("Export failed:", error)
    }
  }

  return (
    <div className="min-h-screen bg-gray-50 p-6">
      <div className="max-w-7xl mx-auto space-y-6">
        {/* Header */}
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-3xl font-bold text-gray-900">Appointments Reports</h1>
            <p className="text-gray-600 mt-1">Track appointment performance and agent metrics</p>
          </div>
          <button
            onClick={handleExport}
            disabled={isExporting}
            className="flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50"
          >
            <Download className="w-4 h-4" />
            {isExporting ? "Exporting..." : "Export"}
          </button>
        </div>

        {/* Predefined Reports Selector */}
        <AppointmentsPredefinedReportsSelector selectedReport={selectedReport} onReportChange={setSelectedReport} />

        {/* Filters */}
        <AppointmentsFilters
          timeframe={timeframe}
          limit={limit}
          selectedAppointmentType={selectedAppointmentType}
          selectedOutcome={selectedOutcome}
          selectedTeam={selectedTeam}
          selectedStatus={selectedStatus}
          onTimeframeChange={setTimeframe}
          onLimitChange={setLimit}
          onAppointmentTypeChange={setSelectedAppointmentType}
          onOutcomeChange={setSelectedOutcome}
          onTeamChange={setSelectedTeam}
          onStatusChange={setSelectedStatus}
          options={optionsData}
        />

        {/* Summary */}
        {appointmentsData?.summary && <AppointmentsSummaryComponent summary={appointmentsData.summary} />}

        {/* Table */}
        <AppointmentsTable agents={appointmentsData?.appointments || []} isLoading={isLoading} />
      </div>
    </div>
  )
}
