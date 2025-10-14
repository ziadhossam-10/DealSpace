"use client"

import { useCallback, useState, useEffect } from "react"
import { toast } from "react-toastify"
import {
  DndContext,
  closestCenter,
  KeyboardSensor,
  PointerSensor,
  useSensor,
  useSensors,
  type DragEndEvent,
} from "@dnd-kit/core"
import { arrayMove, SortableContext, sortableKeyboardCoordinates, verticalListSortingStrategy } from "@dnd-kit/sortable"
import { restrictToVerticalAxis, restrictToWindowEdges } from "@dnd-kit/modifiers"

// CRUDS Components
import DeleteModal from "../../components/modal/DeleteModal"
import CreateDealTypeModal from "./CreateDealTypeModal"
import EditDealTypeModal from "./EditDealTypeModal"

// API Calls
import {
  useGetDealTypesQuery,
  useDeleteDealTypeMutation,
  useUpdateDealTypeSortMutation,
  type DealType,
} from "./dealTypesApi"

import DraggableTableRow from "./DraggableTableRow"
import { Plus } from "lucide-react"

export default function DealTypes() {
  // State Variables
  const [isShowDeleteModal, setShowDeleteModal] = useState(false)
  const [selectedDealTypeId, setSelectedDealTypeId] = useState<number | null>(null)

  // Modal states
  const [isCreateModalOpen, setIsCreateModalOpen] = useState(false)
  const [isEditModalOpen, setIsEditModalOpen] = useState(false)
  const [editDealTypeId, setEditDealTypeId] = useState<number | null>(null)

  // Local state for optimistic updates during drag
  const [localDealTypes, setLocalDealTypes] = useState<DealType[]>([])

  // API Mutations
  const { data, isLoading, error, refetch: fetchDealTypes } = useGetDealTypesQuery()
  const [deleteDealType, { isLoading: isLoadingDelete }] = useDeleteDealTypeMutation()
  const [updateDealTypeSort] = useUpdateDealTypeSortMutation()

  const dealTypes = data?.data || []

  // Update local state when API data changes
  useEffect(() => {
    if (dealTypes.length > 0) {
      setLocalDealTypes([...dealTypes].sort((a, b) => a.sort - b.sort))
    }
  }, [dealTypes])

  // Drag and drop sensors
  const sensors = useSensors(
    useSensor(PointerSensor),
    useSensor(KeyboardSensor, {
      coordinateGetter: sortableKeyboardCoordinates,
    }),
  )

  // Handle drag end
  const handleDragEnd = async (event: DragEndEvent) => {
    const { active, over } = event

    if (!over || active.id === over.id) {
      return
    }

    const oldIndex = localDealTypes.findIndex((item) => item.id === active.id)
    const newIndex = localDealTypes.findIndex((item) => item.id === over.id)

    if (oldIndex === -1 || newIndex === -1) {
      return
    }

    // Optimistic update
    const newDealTypes = arrayMove(localDealTypes, oldIndex, newIndex)
    setLocalDealTypes(newDealTypes)

    try {
      // Update sort order on server
      const dealTypeToUpdate = newDealTypes[newIndex]
      await updateDealTypeSort({
        id: dealTypeToUpdate.id,
        sort: newIndex + 1, // 1-based indexing
      }).unwrap()

      // Refetch to get the latest data
      fetchDealTypes()
      toast.success("Deal type order updated successfully!")
    } catch (error: any) {
      console.error("Failed to update deal type order:", error)
      toast.error("Failed to update order. Please try again.")
      // Revert optimistic update on error
      setLocalDealTypes(dealTypes)
    }
  }

  // Open Create Deal Type Modal
  const handleAdd = useCallback(() => {
    setIsCreateModalOpen(true)
  }, [])

  // Open Edit Deal Type Modal
  const handleEdit = useCallback((id: number) => {
    setEditDealTypeId(id)
    setIsEditModalOpen(true)
  }, [])

  // Ask Delete
  const handleDelete = useCallback((id: number) => {
    setSelectedDealTypeId(id)
    setShowDeleteModal(true)
  }, [])

  // Delete
  const handleConfirmDelete = async () => {
    if (selectedDealTypeId) {
      try {
        const response = await deleteDealType(selectedDealTypeId).unwrap()
        if (response && response.status) {
          toast.success(response.message || "Deal type deleted successfully")
          handleCancelDelete()
          fetchDealTypes()
        } else {
          toast.error(response.message || "Error deleting deal type")
        }
      } catch (error: any) {
        toast.error(error.data?.message || "An error occurred while deleting the deal type")
      }
    }
  }

  // Cancel Delete
  const handleCancelDelete = useCallback(() => {
    setShowDeleteModal(false)
    setSelectedDealTypeId(null)
  }, [])

  if (error) {
    return (
        <div className="container mx-auto">
          <div className="text-center py-12">
            <p className="text-red-600">Error loading deal types. Please try again.</p>
            <button
              onClick={() => fetchDealTypes()}
              className="mt-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
            >
              Retry
            </button>
          </div>
        </div>
    )
  }

  return (
    <div className="container mx-auto pt-4">
    <div>
        {/* Header */}
        <div className="py-4 border-b border-gray-200">
        <div className="flex items-center justify-between">
            <div>
            <h1 className="text-xl font-semibold text-gray-900">Deal Types</h1>
            <p className="mt-1 text-sm text-gray-600">Manage your deal types and their order</p>
            </div>
            <button className="relative group bg-gray-800 text-white p-2 rounded" onClick={handleAdd}>
            <Plus size={18} />
            <div className="absolute top-full right-1/2 translate-x-1/2 mt-2 w-max text-xs text-white bg-black rounded px-2 py-1 opacity-0 group-hover:opacity-100 transition">
                Add Deal Type
            </div>
            </button>
        </div>
        </div>

        {/* Table */}
        <div className="overflow-hidden rounded-lg shadow">
        {isLoading ? (
            <div className="text-center py-12">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
            <p className="mt-4 text-gray-600">Loading deal types...</p>
            </div>
        ) : localDealTypes.length === 0 ? (
            <div className="text-center py-12">
            <div className="mx-auto h-12 w-12 text-gray-400">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                />
                </svg>
            </div>
            <h3 className="mt-2 text-sm font-medium text-gray-900">No Deal Types Found</h3>
            <p className="mt-1 text-sm text-gray-500">
                No deal types found. Create a new deal type to get started.
            </p>
            <div className="mt-6">
                <button
                onClick={handleAdd}
                className="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
                >
                <Plus className="mr-2 h-4 w-4" />
                Add Deal Type
                </button>
            </div>
            </div>
        ) : (
            <div className="min-w-full">
            {/* Table Header */}
            <div className="bg-gray-50 px-6 py-3 border-b border-gray-200">
                <div className="grid grid-cols-12 gap-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                <div className="col-span-1"></div> {/* Drag handle */}
                <div className="col-span-1">Sort</div>
                <div className="col-span-6">Name</div>
                <div className="col-span-4">Actions</div>
                </div>
            </div>

            {/* Draggable Table Body */}
            <DndContext
                sensors={sensors}
                collisionDetection={closestCenter}
                onDragEnd={handleDragEnd}
                modifiers={[restrictToVerticalAxis, restrictToWindowEdges]}
            >
                <SortableContext items={localDealTypes.map((item) => item.id)} strategy={verticalListSortingStrategy}>
                <div className="bg-white divide-y divide-gray-200">
                    {localDealTypes.map((dealType) => (
                    <DraggableTableRow
                        key={dealType.id}
                        dealType={dealType}
                        onEdit={handleEdit}
                        onDelete={handleDelete}
                    />
                    ))}
                </div>
                </SortableContext>
            </DndContext>
            </div>
        )}
        </div>
    </div>

    {/* Create Deal Type Modal */}
    <CreateDealTypeModal
        isOpen={isCreateModalOpen}
        onClose={() => {
        setIsCreateModalOpen(false)
        fetchDealTypes()
        }}
    />

    {/* Edit Deal Type Modal */}
    <EditDealTypeModal
        isOpen={isEditModalOpen}
        onClose={() => {
        setIsEditModalOpen(false)
        setEditDealTypeId(null)
        fetchDealTypes()
        }}
        dealTypeId={editDealTypeId}
    />

    {/* Delete Modal */}
    <DeleteModal
        isOpen={isShowDeleteModal}
        onClose={() => setShowDeleteModal(false)}
        onDelete={handleConfirmDelete}
        isLoadingDelete={isLoadingDelete}
    />
    </div>
  )
}
