"use client"

import { useState } from "react"
import { X, Settings } from "lucide-react"

interface LeadSourceCustomizeColumnsModalProps {
  isOpen: boolean
  onClose: () => void
  visibleColumns: string[]
  onUpdateColumns: (columns: string[]) => void
}

const COLUMN_GROUPS = {
  "LEAD ACTIVITY": [
    { key: "new_leads", label: "New Leads" },
    { key: "calls", label: "Calls" },
    { key: "emails", label: "Emails" },
    { key: "texts", label: "Texts" },
    { key: "notes", label: "Notes" },
    { key: "tasks_completed", label: "Tasks Completed" },
    { key: "appointments", label: "Appointments" },
  ],
  "FOLLOW-UP METRICS": [
    { key: "leads_not_acted_on", label: "Not Acted On" },
    { key: "leads_not_called", label: "Not Called" },
    { key: "leads_not_emailed", label: "Not Emailed" },
    { key: "leads_not_texted", label: "Not Texted" },
  ],
  "SPEED TO ACTION": [
    { key: "avg_speed_to_action", label: "Avg. Speed to Action" },
    { key: "avg_speed_to_first_call", label: "Avg. Speed to First Call" },
    { key: "avg_speed_to_first_email", label: "Avg. Speed to First Email" },
    { key: "avg_speed_to_first_text", label: "Avg. Speed to First Text" },
  ],
  "CONTACT ATTEMPTS": [
    { key: "avg_contact_attempts", label: "Avg. Contact Attempts" },
    { key: "avg_call_attempts", label: "Avg. Call Attempts" },
    { key: "avg_email_attempts", label: "Avg. Email Attempts" },
    { key: "avg_text_attempts", label: "Avg. Text Attempts" },
  ],
  "RESPONSE RATES": [
    { key: "response_rate", label: "Overall Response Rate" },
    { key: "phone_response_rate", label: "Phone Response Rate" },
    { key: "email_response_rate", label: "Email Response Rate" },
    { key: "text_response_rate", label: "Text Response Rate" },
  ],
  "SALES METRICS": [
    { key: "deals_closed", label: "Deals Closed" },
    { key: "deal_value", label: "Deal Value" },
    { key: "deal_commission", label: "Deal Commission" },
    { key: "conversion_rate", label: "Conversion Rate" },
  ],
  "WEBSITE METRICS": [
    { key: "website_registrations", label: "Website Registrations" },
    { key: "inquiries", label: "Inquiries" },
    { key: "properties_viewed", label: "Properties Viewed" },
    { key: "properties_saved", label: "Properties Saved" },
    { key: "page_views", label: "Page Views" },
  ],
}

export default function LeadSourceCustomizeColumnsModal({
  isOpen,
  onClose,
  visibleColumns,
  onUpdateColumns,
}: LeadSourceCustomizeColumnsModalProps) {
  const [selectedColumns, setSelectedColumns] = useState<string[]>(visibleColumns)

  if (!isOpen) return null

  const handleToggleColumn = (columnKey: string) => {
    setSelectedColumns((prev) =>
      prev.includes(columnKey) ? prev.filter((col) => col !== columnKey) : [...prev, columnKey],
    )
  }

  const handleUpdateColumns = () => {
    onUpdateColumns(selectedColumns)
    onClose()
  }

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div className="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4">
        <div className="flex items-center justify-between p-6 border-b">
          <div className="flex items-center">
            <Settings className="w-5 h-5 mr-2 text-gray-600" />
            <h2 className="text-lg font-semibold">Customize Columns</h2>
          </div>
          <button onClick={onClose} className="text-gray-400 hover:text-gray-600">
            <X className="w-5 h-5" />
          </button>
        </div>
        <div className="p-6 max-h-96 overflow-y-auto">
          <div className="grid grid-cols-2 gap-6">
            {Object.entries(COLUMN_GROUPS).map(([groupName, columns]) => (
              <div key={groupName} className="mb-6">
                <h3 className="text-sm font-semibold text-gray-900 mb-3">{groupName}</h3>
                <div className="space-y-2">
                  {columns.map((column) => (
                    <label key={column.key} className="flex items-center">
                      <input
                        type="checkbox"
                        checked={selectedColumns.includes(column.key)}
                        onChange={() => handleToggleColumn(column.key)}
                        className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                      />
                      <span className="ml-2 text-sm text-gray-700">{column.label}</span>
                    </label>
                  ))}
                </div>
              </div>
            ))}
          </div>
        </div>
        <div className="flex justify-end gap-3 p-6 border-t">
          <button
            onClick={onClose}
            className="px-4 py-2 text-gray-700 border border-gray-300 rounded-md hover:bg-gray-50"
          >
            Cancel
          </button>
          <button
            onClick={handleUpdateColumns}
            className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
          >
            Update columns
          </button>
        </div>
      </div>
    </div>
  )
}
