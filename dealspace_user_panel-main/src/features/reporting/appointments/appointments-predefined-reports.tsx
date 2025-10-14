"use client"

export interface PredefinedReport {
  key: string
  name: string
  description: string
}

export const PREDEFINED_REPORTS: PredefinedReport[] = [
  {
    key: "overview",
    name: "Appointments Overview",
    description: "Overall performance across all appointments and agents",
  },
  {
    key: "agent_performance",
    name: "Agent Performance",
    description: "Individual agent appointment metrics and attendance rates",
  },
  {
    key: "attendance_analysis",
    name: "Attendance Analysis",
    description: "Detailed breakdown of attendance patterns and no-shows",
  },
  {
    key: "appointment_types",
    name: "Appointment Types",
    description: "Performance analysis by appointment type",
  },
  {
    key: "conversion_tracking",
    name: "Conversion Tracking",
    description: "Track appointment outcomes and conversion rates",
  },
]

interface AppointmentsPredefinedReportsSelectorProps {
  selectedReport: PredefinedReport
  onReportChange: (report: PredefinedReport) => void
}

export default function AppointmentsPredefinedReportsSelector({
  selectedReport,
  onReportChange,
}: AppointmentsPredefinedReportsSelectorProps) {
  return (
    <div className="bg-white rounded-lg shadow p-6">
      <h3 className="text-lg font-semibold text-gray-900 mb-4">Report Views</h3>
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        {PREDEFINED_REPORTS.map((report) => (
          <button
            key={report.key}
            onClick={() => onReportChange(report)}
            className={`p-4 rounded-lg border-2 text-left transition-all ${
              selectedReport.key === report.key
                ? "border-blue-500 bg-blue-50 text-blue-900"
                : "border-gray-200 hover:border-gray-300 hover:bg-gray-50"
            }`}
          >
            <h4 className="font-medium text-sm mb-1">{report.name}</h4>
            <p className="text-xs text-gray-600">{report.description}</p>
          </button>
        ))}
      </div>
    </div>
  )
}
