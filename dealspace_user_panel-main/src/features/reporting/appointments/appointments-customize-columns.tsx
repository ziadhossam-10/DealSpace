"use client"

import { useState } from "react"
import { X } from "lucide-react"

const ALL_COLUMNS = [
  { key: "total_appointments", label: "Total Appointments" },
  { key: "attended_appointments", label: "Attended" },
  { key: "no_show_appointments", label: "No Show" },
  { key: "rescheduled_appointments", label: "Rescheduled" },
  { key: "canceled_appointments", label: "Canceled" },
  { key: "attendance_rate", label: "Attendance Rate" },
  { key: "conversion_rate", label: "Conversion Rate" },
  { key: "total_appointment_value", label: "Total Value" },
  { key: "average_appointment_value", label: "Average Value" },
  { key: "upcoming_appointments", label: "Upcoming" },
  { key: "overdue_appointments", label: "Overdue" },
  { key: "appointment_types", label: "Types Count" },
  { key: "outcomes", label: "Outcomes Count" },
]

interface AppointmentsCustomizeColumnsModalProps {
  isOpen: boolean
  onClose: () => void
  visibleColumns: string[]
  onUpdateColumns: (columns: string[]) => void
}

export default function AppointmentsCustomizeColumnsModal({
  isOpen,
  onClose,
  visibleColumns,
  onUpdateColumns,
}: AppointmentsCustomizeColumnsModalProps) {
  const [tempVisibleColumns, setTempVisibleColumns] = useState<string[]>(visibleColumns)

  if (!isOpen) return null

  const handleToggleColumn = (columnKey: string) => {
    setTempVisibleColumns((prev) =>
      prev.includes(columnKey) ? prev.filter((col) => col !== columnKey) : [...prev, columnKey],
    )
  }

  const handleSave = () => {
    onUpdateColumns(tempVisibleColumns)
    onClose()
  }

  const handleCancel = () => {
    setTempVisibleColumns(visibleColumns)
    onClose()
  }

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div className="bg-white rounded-lg p-6 w-full max-w-md">
        <div className="flex items-center justify-between mb-4">
          <h3 className="text-lg font-semibold">Customize Columns</h3>
          <button onClick={handleCancel} className="text-gray-400 hover:text-gray-600">
            <X className="w-5 h-5" />
          </button>
        </div>

        <div className="space-y-2 max-h-96 overflow-y-auto">
          {ALL_COLUMNS.map((column) => (
            <label key={column.key} className="flex items-center space-x-2 cursor-pointer">
              <input
                type="checkbox"
                checked={tempVisibleColumns.includes(column.key)}
                onChange={() => handleToggleColumn(column.key)}
                className="rounded border-gray-300"
              />
              <span className="text-sm">{column.label}</span>
            </label>
          ))}
        </div>

        <div className="flex justify-end space-x-2 mt-6">
          <button onClick={handleCancel} className="px-4 py-2 text-sm border border-gray-300 rounded hover:bg-gray-50">
            Cancel
          </button>
          <button onClick={handleSave} className="px-4 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">
            Save Changes
          </button>
        </div>
      </div>
    </div>
  )
}
