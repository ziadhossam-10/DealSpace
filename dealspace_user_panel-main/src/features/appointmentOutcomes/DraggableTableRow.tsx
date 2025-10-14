"use client"
import { useSortable } from "@dnd-kit/sortable"
import { CSS } from "@dnd-kit/utilities"
import { GripVertical } from "lucide-react"
import type { AppointmentOutcome } from "./appointmentOutcomesApi"

interface DraggableTableRowProps {
  appointmentOutcome: AppointmentOutcome
  onEdit: (id: number) => void
  onDelete: (id: number) => void
}

export default function DraggableTableRow({ appointmentOutcome, onEdit, onDelete }: DraggableTableRowProps) {
  const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({
    id: appointmentOutcome.id,
  })

  const style = {
    transform: CSS.Transform.toString(transform),
    transition,
  }

  return (
    <div
      ref={setNodeRef}
      style={style}
      className={`px-6 py-4 transition-all duration-200 ${
        isDragging ? "bg-blue-50 shadow-lg z-50 opacity-90 scale-105" : "bg-white hover:bg-gray-50"
      }`}
    >
      <div className="flex items-center gap-4">
        {/* Drag Handle */}
        <div className="w-8">
          <button
            className={`p-1 rounded hover:bg-gray-200 transition-colors ${
              isDragging ? "cursor-grabbing" : "cursor-grab"
            }`}
            {...attributes}
            {...listeners}
          >
            <GripVertical className="h-4 w-4 text-gray-400" />
          </button>
        </div>

        {/* Sort */}
        <div className="w-12">
          <span className="text-sm font-medium text-gray-900">{appointmentOutcome.sort}</span>
        </div>

        {/* Name */}
        <div className="flex-1 min-w-0">
          <div className="text-sm font-medium text-gray-900 truncate">{appointmentOutcome.name}</div>
        </div>

        {/* Description */}
        <div className="flex-1 min-w-0">
          <div className="text-sm text-gray-600 truncate" title={appointmentOutcome.description}>
            {appointmentOutcome.description || "No description"}
          </div>
        </div>

        {/* Actions */}
        <div className="w-32">
          <div className="flex items-center gap-2">
            <button
              className="px-3 py-1 bg-green-500 text-white rounded text-sm hover:bg-green-600 transition-colors"
              onClick={() => onEdit(appointmentOutcome.id)}
            >
              Edit
            </button>
            <button
              className="px-3 py-1 bg-red-500 text-white rounded text-sm hover:bg-red-600 transition-colors"
              onClick={() => onDelete(appointmentOutcome.id)}
            >
              Delete
            </button>
          </div>
        </div>
      </div>
    </div>
  )
}
