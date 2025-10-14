"use client"

import { useState } from "react"
import { X, Settings } from "lucide-react"

interface TextsCustomizeColumnsModalProps {
  isOpen: boolean
  onClose: () => void
  visibleColumns: string[]
  onUpdateColumns: (columns: string[]) => void
}

const COLUMN_GROUPS = {
  "TEXT VOLUME": [
    { key: "texts_sent", label: "Texts Sent" },
    { key: "texts_received", label: "Texts Received" },
    { key: "texts_delivered", label: "Texts Delivered" },
    { key: "texts_failed", label: "Texts Failed" },
  ],
  "PERFORMANCE RATES": [
    { key: "delivery_rate", label: "Delivery Rate" },
    { key: "response_rate", label: "Response Rate" },
    { key: "engagement_rate", label: "Engagement Rate" },
  ],
  "CONTACT METRICS": [
    { key: "unique_contacts_texted", label: "Unique Contacts Texted" },
    { key: "contacts_responded", label: "Contacts Responded" },
    { key: "conversations_initiated", label: "Conversations Initiated" },
    { key: "conversations_active", label: "Active Conversations" },
  ],
  "ERROR TRACKING": [
    { key: "opt_outs", label: "Opt Outs" },
    { key: "carrier_filtered", label: "Carrier Filtered" },
    { key: "other_errors", label: "Other Errors" },
  ],
  "DAILY AVERAGES": [
    { key: "avg_texts_per_day", label: "Avg Texts Per Day" },
    { key: "avg_responses_per_day", label: "Avg Responses Per Day" },
    { key: "avg_response_time", label: "Avg Response Time" },
  ],
  "MESSAGE ANALYSIS": [
    { key: "texts_by_hour", label: "Peak Hour Activity" },
    { key: "avg_message_length", label: "Avg Message Length" },
    { key: "message_length_distribution", label: "Message Length Distribution" },
  ],
}

export default function TextsCustomizeColumnsModal({
  isOpen,
  onClose,
  visibleColumns,
  onUpdateColumns,
}: TextsCustomizeColumnsModalProps) {
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
