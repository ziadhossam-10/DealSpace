"use client"

import { useSortable } from "@dnd-kit/sortable"
import { CSS } from "@dnd-kit/utilities"
import { GripVertical } from "lucide-react"
import type { DealType } from "./dealTypesApi"

interface DraggableTableRowProps {
  dealType: DealType
  onEdit: (id: number) => void
  onDelete: (id: number) => void
}

export default function DraggableTableRow({ dealType, onEdit, onDelete }: DraggableTableRowProps) {
  const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({ id: dealType.id })

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
      <div className="grid grid-cols-12 gap-4 items-center">
        {/* Drag Handle */}
        <div className="col-span-1">
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
        <div className="col-span-1">
          <span className="text-sm font-medium text-gray-900">{dealType.sort}</span>
        </div>

        {/* Name */}
        <div className="col-span-6">
          <div className="text-sm font-medium text-gray-900">{dealType.name}</div>
        </div>

        {/* Actions */}
        <div className="col-span-4">
          <div className="flex items-center gap-2">
            <button
              className="px-3 py-1 bg-green-500 text-white rounded text-sm hover:bg-green-600 transition-colors"
              onClick={() => onEdit(dealType.id)}
            >
              Edit
            </button>
            <button
              className="px-3 py-1 bg-red-500 text-white rounded text-sm hover:bg-red-600 transition-colors"
              onClick={() => onDelete(dealType.id)}
            >
              Delete
            </button>
          </div>
        </div>
      </div>
    </div>
  )
}
