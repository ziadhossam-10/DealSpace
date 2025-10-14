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
    id: "total-activity",
    question: "Total lead count and total agent activity",
    description: "Compare total leads received vs agent activity metrics",
    chartType: "area",
    metrics: ["new_leads", "calls", "emails", "texts"],
  },
  {
    id: "not-acted-on",
    question: "How many leads have we not acted on",
    description: "Show leads that haven't received any follow-up",
    chartType: "bar",
    metrics: ["leads_not_acted_on", "leads_not_called", "leads_not_emailed"],
  },
  {
    id: "follow-up-speed",
    question: "How quickly we follow up on leads",
    description: "Average time to first contact by agent",
    chartType: "bar",
    metrics: ["avg_speed_to_action", "avg_speed_to_first_call", "avg_speed_to_first_email"],
  },
  {
    id: "contact-attempts",
    question: "How many times we try to contact each lead",
    description: "Average contact attempts per lead by agent",
    chartType: "line",
    metrics: ["avg_contact_attempts", "avg_call_attempts", "avg_email_attempts"],
  },
  {
    id: "response-rates",
    question: "What team member is getting the most leads to respond",
    description: "Response rates by communication method",
    chartType: "bar",
    metrics: ["response_rate", "phone_response_rate", "email_response_rate"],
  },
  {
    id: "appointments-set",
    question: "Which team member has the most appointments set",
    description: "Appointments scheduled by each agent",
    chartType: "bar",
    metrics: ["appointments", "appointments_set"],
  },
]

export default function PredefinedReportsSelector({ selectedReport, onReportChange }: PredefinedReportsSelectorProps) {
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
