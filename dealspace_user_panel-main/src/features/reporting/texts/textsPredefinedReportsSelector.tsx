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
    id: "text-volume",
    question: "Who is sending the most texts",
    description: "Compare text volume and delivery across agents",
    chartType: "bar",
    metrics: ["texts_sent", "texts_delivered", "texts_received"],
  },
  {
    id: "delivery-performance",
    question: "Who has the best delivery and response rates",
    description: "Compare success rates for text delivery and engagement",
    chartType: "bar",
    metrics: ["delivery_rate", "response_rate", "engagement_rate"],
  },
  {
    id: "conversation-engagement",
    question: "Who is generating the most text conversations",
    description: "Compare conversation initiation and engagement",
    chartType: "bar",
    metrics: ["conversations_initiated", "conversations_active", "contacts_responded"],
  },
  {
    id: "contact-reach",
    question: "Who is reaching the most unique contacts via text",
    description: "Compare unique contacts texted and response rates",
    chartType: "bar",
    metrics: ["unique_contacts_texted", "contacts_responded", "response_rate"],
  },
  {
    id: "error-analysis",
    question: "Which agents have the most text delivery issues",
    description: "Analyze opt-outs, carrier filtering, and other errors",
    chartType: "bar",
    metrics: ["opt_outs", "carrier_filtered", "other_errors", "texts_failed"],
  },
  {
    id: "daily-activity",
    question: "What is the daily text activity pattern",
    description: "Average texts per day and response patterns",
    chartType: "line",
    metrics: ["avg_texts_per_day", "avg_responses_per_day"],
  },
  {
    id: "message-quality",
    question: "How do message characteristics affect engagement",
    description: "Message length and response time analysis",
    chartType: "area",
    metrics: ["avg_message_length", "avg_response_time", "engagement_rate"],
  },
]

export default function TextsPredefinedReportsSelector({
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
