"use client"

import { useState } from "react"
import { X, Settings } from "lucide-react"

interface CallsCustomizeColumnsModalProps {
  isOpen: boolean
  onClose: () => void
  visibleColumns: string[]
  onUpdateColumns: (columns: string[]) => void
}

const COLUMN_GROUPS = {
  "CALL VOLUME": [
    { key: "calls_made", label: "Calls Made" },
    { key: "calls_connected", label: "Calls Connected" },
    { key: "conversations", label: "Conversations" },
    { key: "calls_received", label: "Calls Received" },
    { key: "calls_missed", label: "Calls Missed" },
  ],
  "PERFORMANCE RATES": [
    { key: "connection_rate", label: "Connection Rate" },
    { key: "conversation_rate", label: "Conversation Rate" },
    { key: "answer_rate", label: "Answer Rate" },
  ],
  "TIME METRICS": [
    { key: "total_talk_time", label: "Total Talk Time" },
    { key: "avg_call_duration", label: "Avg Call Duration" },
    { key: "avg_conversation_duration", label: "Avg Conversation Duration" },
    { key: "avg_answer_time", label: "Avg Answer Time" },
    { key: "avg_talk_time_per_day", label: "Avg Talk Time Per Day" },
  ],
  "CONTACT METRICS": [
    { key: "unique_contacts_called", label: "Unique Contacts Called" },
    { key: "contacts_reached", label: "Contacts Reached" },
    { key: "avg_calls_per_day", label: "Avg Calls Per Day" },
  ],
  OUTCOMES: [{ key: "outcomes", label: "Total Outcomes" }],
}

export default function CallsCustomizeColumnsModal({
  isOpen,
  onClose,
  visibleColumns,
  onUpdateColumns,
}: CallsCustomizeColumnsModalProps) {
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
