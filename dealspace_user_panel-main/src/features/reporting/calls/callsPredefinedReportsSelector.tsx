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
    id: "call-volume",
    question: "Who is making the most calls",
    description: "Compare call volume and activity across agents",
    chartType: "bar",
    metrics: ["calls_made", "calls_connected", "conversations"],
  },
  {
    id: "connection-rates",
    question: "Who has the best connection and conversation rates",
    description: "Compare success rates for connecting and converting calls",
    chartType: "bar",
    metrics: ["connection_rate", "conversation_rate", "answer_rate"],
  },
  {
    id: "talk-time",
    question: "Who spends the most time on calls",
    description: "Compare total talk time and average call duration",
    chartType: "bar",
    metrics: ["total_talk_time", "avg_call_duration", "avg_conversation_duration"],
  },
  {
    id: "contact-reach",
    question: "Who is reaching the most unique contacts",
    description: "Compare unique contacts called and reached",
    chartType: "bar",
    metrics: ["unique_contacts_called", "contacts_reached"],
  },
  {
    id: "daily-activity",
    question: "What is the daily call activity pattern",
    description: "Average calls per day and talk time per day",
    chartType: "line",
    metrics: ["avg_calls_per_day", "avg_talk_time_per_day"],
  },
  {
    id: "call-efficiency",
    question: "Who is most efficient with their calling time",
    description: "Efficiency metrics: calls per day vs talk time",
    chartType: "area",
    metrics: ["avg_calls_per_day", "avg_call_duration", "connection_rate"],
  },
  {
    id: "inbound-vs-outbound",
    question: "Inbound vs outbound call comparison",
    description: "Compare incoming and outgoing call activity",
    chartType: "bar",
    metrics: ["calls_made", "calls_received", "calls_missed"],
  },
]

export default function CallsPredefinedReportsSelector({
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
