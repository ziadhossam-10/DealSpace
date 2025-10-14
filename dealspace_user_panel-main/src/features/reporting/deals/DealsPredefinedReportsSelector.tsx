"use client"

export interface PredefinedReport {
  key: string
  name: string
  description: string
}

export const PREDEFINED_REPORTS: PredefinedReport[] = [
  {
    key: "overview",
    name: "Deals Overview",
    description: "Overall performance across all deals and agents",
  },
  {
    key: "agent_performance",
    name: "Agent Performance",
    description: "Individual agent performance and metrics",
  },
  {
    key: "pipeline_analysis",
    name: "Pipeline Analysis",
    description: "Deal distribution across pipeline stages",
  },
  {
    key: "deal_value_trends",
    name: "Deal Value Trends",
    description: "Revenue trends and deal value analysis",
  },
  {
    key: "conversion_rates",
    name: "Conversion Rates",
    description: "Win rates and conversion metrics by stage",
  },
]

interface DealsPredefinedReportsSelectorProps {
  selectedReport: PredefinedReport
  onReportChange: (report: PredefinedReport) => void
}

export default function DealsPredefinedReportsSelector({
  selectedReport,
  onReportChange,
}: DealsPredefinedReportsSelectorProps) {
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
