"use client"

import { useState } from "react"
import { ChevronDown } from "lucide-react"

export interface PredefinedReport {
  id: string
  question: string
  description: string
  chartType: "line" | "bar" | "area"
  metrics: string[]
}

interface PredefinedReportsSelectorProps {
  selectedReport: PredefinedReport
  onReportChange: (report: PredefinedReport) => void
}

const PREDEFINED_REPORTS: PredefinedReport[] = [
  {
    id: "lead-volume-by-source",
    question: "Which lead sources generate the most leads",
    description: "Compare lead volume and activity across different sources",
    chartType: "bar",
    metrics: ["new_leads", "calls", "emails", "texts"],
  },
  {
    id: "conversion-performance",
    question: "Which lead sources convert the best",
    description: "Compare conversion rates and deal values by source",
    chartType: "bar",
    metrics: ["conversion_rate", "deals_closed", "deal_value"],
  },
  {
    id: "response-rates-by-source",
    question: "Which lead sources have the highest response rates",
    description: "Response rates across different communication channels by source",
    chartType: "bar",
    metrics: ["response_rate", "phone_response_rate", "email_response_rate"],
  },
  {
    id: "follow-up-speed",
    question: "How quickly we follow up on leads by source",
    description: "Average time to first contact by lead source",
    chartType: "bar",
    metrics: ["avg_speed_to_action", "avg_speed_to_first_call", "avg_speed_to_first_email"],
  },
  {
    id: "website-engagement",
    question: "Which sources drive the most website engagement",
    description: "Website activity metrics by lead source",
    chartType: "area",
    metrics: ["website_registrations", "inquiries", "properties_viewed", "page_views"],
  },
  {
    id: "lead-quality",
    question: "Which sources provide the highest quality leads",
    description: "Lead quality indicators by source",
    chartType: "line",
    metrics: ["avg_contact_attempts", "response_rate", "appointments", "deals_closed"],
  },
  {
    id: "missed-opportunities",
    question: "Which sources have leads we're not following up on",
    description: "Identify missed follow-up opportunities by source",
    chartType: "bar",
    metrics: ["leads_not_acted_on", "leads_not_called", "leads_not_emailed"],
  },
]

export default function LeadSourcePredefinedReportsSelector({
  selectedReport,
  onReportChange,
}: PredefinedReportsSelectorProps) {
  const [isOpen, setIsOpen] = useState(false)

  return (
    <div className="mb-6">
      <div className="flex items-center gap-2 mb-4">
        <span className="text-gray-700">Show me</span>
        <div className="relative">
          <button
            onClick={() => setIsOpen(!isOpen)}
            className="flex items-center gap-2 text-blue-600 hover:text-blue-800 font-medium"
          >
            {selectedReport.question}
            <ChevronDown className="w-4 h-4" />
          </button>
          {isOpen && (
            <div className="absolute top-full left-0 mt-1 w-96 bg-white border border-gray-300 rounded-md shadow-lg z-20">
              <div className="p-2 max-h-80 overflow-y-auto">
                {PREDEFINED_REPORTS.map((report) => (
                  <button
                    key={report.id}
                    onClick={() => {
                      onReportChange(report)
                      setIsOpen(false)
                    }}
                    className={`w-full text-left p-3 rounded hover:bg-gray-50 ${
                      selectedReport.id === report.id ? "bg-blue-50 text-blue-700" : "text-gray-700"
                    }`}
                  >
                    <div className="font-medium">{report.question}</div>
                    <div className="text-sm text-gray-500 mt-1">{report.description}</div>
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

export { PREDEFINED_REPORTS }
