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
import CreateAppointmentOutcomeModal from "./CreateAppointmentOutcomeModal"
import EditAppointmentOutcomeModal from "./EditAppointmentOutcomeModal"

// API Calls
import {
  useGetAppointmentOutcomesQuery,
  useDeleteAppointmentOutcomeMutation,
  useUpdateAppointmentOutcomeSortMutation,
  type AppointmentOutcome,
} from "./appointmentOutcomesApi"
import DraggableTableRow from "./DraggableTableRow"
import { Plus } from "lucide-react"

export default function AppointmentOutcomes() {
  // State Variables
  const [isShowDeleteModal, setShowDeleteModal] = useState(false)
  const [selectedAppointmentOutcomeId, setSelectedAppointmentOutcomeId] = useState<number | null>(null)

  // Modal states
  const [isCreateModalOpen, setIsCreateModalOpen] = useState(false)
  const [isEditModalOpen, setIsEditModalOpen] = useState(false)
  const [editAppointmentOutcomeId, setEditAppointmentOutcomeId] = useState<number | null>(null)

  // Local state for optimistic updates during drag
  const [localAppointmentOutcomes, setLocalAppointmentOutcomes] = useState<AppointmentOutcome[]>([])

  // API Mutations
  const { data, isLoading, error, refetch: fetchAppointmentOutcomes } = useGetAppointmentOutcomesQuery()
  const [deleteAppointmentOutcome, { isLoading: isLoadingDelete }] = useDeleteAppointmentOutcomeMutation()
  const [updateAppointmentOutcomeSort] = useUpdateAppointmentOutcomeSortMutation()

  const appointmentOutcomes = data?.data || []

  // Update local state when API data changes
  useEffect(() => {
    if (appointmentOutcomes.length > 0) {
      setLocalAppointmentOutcomes([...appointmentOutcomes].sort((a, b) => a.sort - b.sort))
    }
  }, [appointmentOutcomes])

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

    const oldIndex = localAppointmentOutcomes.findIndex((item) => item.id === active.id)
    const newIndex = localAppointmentOutcomes.findIndex((item) => item.id === over.id)

    if (oldIndex === -1 || newIndex === -1) {
      return
    }

    // Optimistic update
    const newAppointmentOutcomes = arrayMove(localAppointmentOutcomes, oldIndex, newIndex)
    setLocalAppointmentOutcomes(newAppointmentOutcomes)

    try {
      // Update sort order on server
      const appointmentOutcomeToUpdate = newAppointmentOutcomes[newIndex]
      await updateAppointmentOutcomeSort({
        id: appointmentOutcomeToUpdate.id,
        sort: newIndex + 1, // 1-based indexing
      }).unwrap()

      // Refetch to get the latest data
      fetchAppointmentOutcomes()
      toast.success("Appointment outcome order updated successfully!")
    } catch (error: any) {
      console.error("Failed to update appointment outcome order:", error)
      toast.error("Failed to update order. Please try again.")
      // Revert optimistic update on error
      setLocalAppointmentOutcomes(appointmentOutcomes)
    }
  }

  // Open Create Appointment Outcome Modal
  const handleAdd = useCallback(() => {
    setIsCreateModalOpen(true)
  }, [])

  // Open Edit Appointment Outcome Modal
  const handleEdit = useCallback((id: number) => {
    setEditAppointmentOutcomeId(id)
    setIsEditModalOpen(true)
  }, [])

  // Ask Delete
  const handleDelete = useCallback((id: number) => {
    setSelectedAppointmentOutcomeId(id)
    setShowDeleteModal(true)
  }, [])

  // Delete
  const handleConfirmDelete = async () => {
    if (selectedAppointmentOutcomeId) {
      try {
        const response = await deleteAppointmentOutcome(selectedAppointmentOutcomeId).unwrap()
        if (response && response.status) {
          toast.success(response.message || "Appointment outcome deleted successfully")
          handleCancelDelete()
          fetchAppointmentOutcomes()
        } else {
          toast.error(response.message || "Error deleting appointment outcome")
        }
      } catch (error: any) {
        toast.error(error.data?.message || "An error occurred while deleting the appointment outcome")
      }
    }
  }

  // Cancel Delete
  const handleCancelDelete = useCallback(() => {
    setShowDeleteModal(false)
    setSelectedAppointmentOutcomeId(null)
  }, [])

  if (error) {
    return (
      <div className="container mx-auto">
        <div className="text-center py-12">
          <p className="text-red-600">Error loading appointment outcomes. Please try again.</p>
          <button
            onClick={() => fetchAppointmentOutcomes()}
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
              <h1 className="text-xl font-semibold text-gray-900">Appointment Outcomes</h1>
              <p className="mt-1 text-sm text-gray-600">Manage your appointment outcomes and their order</p>
            </div>
            <button className="relative group bg-gray-800 text-white p-2 rounded" onClick={handleAdd}>
              <Plus size={18} />
              <div className="absolute top-full right-1/2 translate-x-1/2 mt-2 w-max text-xs text-white bg-black rounded px-2 py-1 opacity-0 group-hover:opacity-100 transition">
                Add Appointment Outcome
              </div>
            </button>
          </div>
        </div>

        {/* Table */}
        <div className="overflow-hidden rounded-lg shadow">
          {isLoading ? (
            <div className="text-center py-12">
              <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
              <p className="mt-4 text-gray-600">Loading appointment outcomes...</p>
            </div>
          ) : localAppointmentOutcomes.length === 0 ? (
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
              <h3 className="mt-2 text-sm font-medium text-gray-900">No Appointment Outcomes Found</h3>
              <p className="mt-1 text-sm text-gray-500">
                No appointment outcomes found. Create a new appointment outcome to get started.
              </p>
              <div className="mt-6">
                <button
                  onClick={handleAdd}
                  className="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
                >
                  <Plus className="mr-2 h-4 w-4" />
                  Add Appointment Outcome
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
                  items={localAppointmentOutcomes.map((item) => item.id)}
                  strategy={verticalListSortingStrategy}
                >
                  <div className="bg-white divide-y divide-gray-200">
                    {localAppointmentOutcomes.map((appointmentOutcome) => (
                      <DraggableTableRow
                        key={appointmentOutcome.id}
                        appointmentOutcome={appointmentOutcome}
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

      {/* Create Appointment Outcome Modal */}
      <CreateAppointmentOutcomeModal
        isOpen={isCreateModalOpen}
        onClose={() => {
          setIsCreateModalOpen(false)
          fetchAppointmentOutcomes()
        }}
      />

      {/* Edit Appointment Outcome Modal */}
      <EditAppointmentOutcomeModal
        isOpen={isEditModalOpen}
        onClose={() => {
          setIsEditModalOpen(false)
          setEditAppointmentOutcomeId(null)
          fetchAppointmentOutcomes()
        }}
        appointmentOutcomeId={editAppointmentOutcomeId}
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
