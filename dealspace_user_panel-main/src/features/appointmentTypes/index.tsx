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
import CreateAppointmentTypeModal from "./CreateAppointmentTypeModal"
import EditAppointmentTypeModal from "./EditAppointmentTypeModal"

// API Calls
import {
  useGetAppointmentTypesQuery,
  useDeleteAppointmentTypeMutation,
  useUpdateAppointmentTypeSortMutation,
  type AppointmentType,
} from "./appointmentTypesApi"
import DraggableTableRow from "./DraggableTableRow"
import { Plus } from "lucide-react"

export default function AppointmentTypes() {
  // State Variables
  const [isShowDeleteModal, setShowDeleteModal] = useState(false)
  const [selectedAppointmentTypeId, setSelectedAppointmentTypeId] = useState<number | null>(null)

  // Modal states
  const [isCreateModalOpen, setIsCreateModalOpen] = useState(false)
  const [isEditModalOpen, setIsEditModalOpen] = useState(false)
  const [editAppointmentTypeId, setEditAppointmentTypeId] = useState<number | null>(null)

  // Local state for optimistic updates during drag
  const [localAppointmentTypes, setLocalAppointmentTypes] = useState<AppointmentType[]>([])

  // API Mutations
  const { data, isLoading, error, refetch: fetchAppointmentTypes } = useGetAppointmentTypesQuery()
  const [deleteAppointmentType, { isLoading: isLoadingDelete }] = useDeleteAppointmentTypeMutation()
  const [updateAppointmentTypeSort] = useUpdateAppointmentTypeSortMutation()

  const appointmentTypes = data?.data || []

  // Update local state when API data changes
  useEffect(() => {
    if (appointmentTypes.length > 0) {
      setLocalAppointmentTypes([...appointmentTypes].sort((a, b) => a.sort - b.sort))
    }
  }, [appointmentTypes])

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

    const oldIndex = localAppointmentTypes.findIndex((item) => item.id === active.id)
    const newIndex = localAppointmentTypes.findIndex((item) => item.id === over.id)

    if (oldIndex === -1 || newIndex === -1) {
      return
    }

    // Optimistic update
    const newAppointmentTypes = arrayMove(localAppointmentTypes, oldIndex, newIndex)
    setLocalAppointmentTypes(newAppointmentTypes)

    try {
      // Update sort order on server
      const appointmentTypeToUpdate = newAppointmentTypes[newIndex]
      await updateAppointmentTypeSort({
        id: appointmentTypeToUpdate.id,
        sort: newIndex + 1, // 1-based indexing
      }).unwrap()

      // Refetch to get the latest data
      fetchAppointmentTypes()
      toast.success("Appointment type order updated successfully!")
    } catch (error: any) {
      console.error("Failed to update appointment type order:", error)
      toast.error("Failed to update order. Please try again.")
      // Revert optimistic update on error
      setLocalAppointmentTypes(appointmentTypes)
    }
  }

  // Open Create Appointment Type Modal
  const handleAdd = useCallback(() => {
    setIsCreateModalOpen(true)
  }, [])

  // Open Edit Appointment Type Modal
  const handleEdit = useCallback((id: number) => {
    setEditAppointmentTypeId(id)
    setIsEditModalOpen(true)
  }, [])

  // Ask Delete
  const handleDelete = useCallback((id: number) => {
    setSelectedAppointmentTypeId(id)
    setShowDeleteModal(true)
  }, [])

  // Delete
  const handleConfirmDelete = async () => {
    if (selectedAppointmentTypeId) {
      try {
        const response = await deleteAppointmentType(selectedAppointmentTypeId).unwrap()
        if (response && response.status) {
          toast.success(response.message || "Appointment type deleted successfully")
          handleCancelDelete()
          fetchAppointmentTypes()
        } else {
          toast.error(response.message || "Error deleting appointment type")
        }
      } catch (error: any) {
        toast.error(error.data?.message || "An error occurred while deleting the appointment type")
      }
    }
  }

  // Cancel Delete
  const handleCancelDelete = useCallback(() => {
    setShowDeleteModal(false)
    setSelectedAppointmentTypeId(null)
  }, [])

  if (error) {
    return (
      <div className="container mx-auto">
        <div className="text-center py-12">
          <p className="text-red-600">Error loading appointment types. Please try again.</p>
          <button
            onClick={() => fetchAppointmentTypes()}
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
              <h1 className="text-xl font-semibold text-gray-900">Appointment Types</h1>
              <p className="mt-1 text-sm text-gray-600">Manage your appointment types and their order</p>
            </div>
            <button className="relative group bg-gray-800 text-white p-2 rounded" onClick={handleAdd}>
              <Plus size={18} />
              <div className="absolute top-full right-1/2 translate-x-1/2 mt-2 w-max text-xs text-white bg-black rounded px-2 py-1 opacity-0 group-hover:opacity-100 transition">
                Add Appointment Type
              </div>
            </button>
          </div>
        </div>

        {/* Table */}
        <div className="overflow-hidden rounded-lg shadow">
          {isLoading ? (
            <div className="text-center py-12">
              <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
              <p className="mt-4 text-gray-600">Loading appointment types...</p>
            </div>
          ) : localAppointmentTypes.length === 0 ? (
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
              <h3 className="mt-2 text-sm font-medium text-gray-900">No Appointment Types Found</h3>
              <p className="mt-1 text-sm text-gray-500">
                No appointment types found. Create a new appointment type to get started.
              </p>
              <div className="mt-6">
                <button
                  onClick={handleAdd}
                  className="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
                >
                  <Plus className="mr-2 h-4 w-4" />
                  Add Appointment Type
                </button>
              </div>
            </div>
          ) : (
            <div className="min-w-full">
              {/* Table Header */}
              <div className="bg-gray-50 px-6 py-3 border-b border-gray-200">
                <div className="flex items-center gap-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  <div className="w-8"></div> {/* Drag handle */}
                  <div className="w-12">Sort</div>
                  <div className="flex-1 min-w-0">Name</div>
                  <div className="flex-1 min-w-0">Description</div>
                  <div className="w-32">Actions</div>
                </div>
              </div>

              {/* Draggable Table Body */}
              <DndContext
                sensors={sensors}
                collisionDetection={closestCenter}
                onDragEnd={handleDragEnd}
                modifiers={[restrictToVerticalAxis, restrictToWindowEdges]}
              >
                <SortableContext
                  items={localAppointmentTypes.map((item) => item.id)}
                  strategy={verticalListSortingStrategy}
                >
                  <div className="bg-white divide-y divide-gray-200">
                    {localAppointmentTypes.map((appointmentType) => (
                      <DraggableTableRow
                        key={appointmentType.id}
                        appointmentType={appointmentType}
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

      {/* Create Appointment Type Modal */}
      <CreateAppointmentTypeModal
        isOpen={isCreateModalOpen}
        onClose={() => {
          setIsCreateModalOpen(false)
          fetchAppointmentTypes()
        }}
      />

      {/* Edit Appointment Type Modal */}
      <EditAppointmentTypeModal
        isOpen={isEditModalOpen}
        onClose={() => {
          setIsEditModalOpen(false)
          setEditAppointmentTypeId(null)
          fetchAppointmentTypes()
        }}
        appointmentTypeId={editAppointmentTypeId}
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
