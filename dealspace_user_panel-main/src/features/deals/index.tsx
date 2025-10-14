"use client"

import type React from "react"
import { useState, useCallback, useEffect } from "react"
import { toast } from "react-toastify"
import {
  DndContext,
  type DragEndEvent,
  type DragOverEvent,
  DragOverlay,
  type DragStartEvent,
  PointerSensor,
  useSensor,
  useSensors,
  closestCenter,
  KeyboardSensor,
} from "@dnd-kit/core"
import {
  SortableContext,
  arrayMove,
  horizontalListSortingStrategy,
  verticalListSortingStrategy,
  sortableKeyboardCoordinates,
} from "@dnd-kit/sortable"
import { useSortable } from "@dnd-kit/sortable"
import { CSS } from "@dnd-kit/utilities"
import { restrictToVerticalAxis, restrictToWindowEdges } from "@dnd-kit/modifiers"
import { Plus, Settings, X, GripVertical, Edit, Trash2, Palette, ChevronDown, Loader2 } from "lucide-react"
import {
  useGetDealTypesQuery,
  useGetDealStagesQuery,
  useGetDealsQuery,
  useDeleteDealMutation,
  useUpdateDealStageOnlyMutation,
  useUpdateStageOrderMutation,
  useCreateDealStageMutation,
  useUpdateDealStageMutation,
  useDeleteDealStageMutation,
} from "./dealsApi"
import CreateDealModal from "./CreateDealModal"
import EditDealModal from "./EditDealModal"
import DeleteModal from "./DeleteModal"
import type { Deal, DealStage } from "../../types/deals"
import { Link } from "react-router"
import { ASSETS_URL } from "../../utils/helpers"

// Predefined colors for stage selection
const STAGE_COLORS = [
  { name: "Blue", value: "#3B82F6" },
  { name: "Green", value: "#10B981" },
  { name: "Purple", value: "#8B5CF6" },
  { name: "Red", value: "#EF4444" },
  { name: "Orange", value: "#F59E0B" },
  { name: "Pink", value: "#EC4899" },
  { name: "Indigo", value: "#6366F1" },
  { name: "Teal", value: "#14B8A6" },
  { name: "Cyan", value: "#06B6D4" },
  { name: "Emerald", value: "#059669" },
  { name: "Lime", value: "#65A30D" },
  { name: "Amber", value: "#D97706" },
  { name: "Rose", value: "#F43F5E" },
  { name: "Violet", value: "#7C3AED" },
  { name: "Sky", value: "#0EA5E9" },
  { name: "Gray", value: "#6B7280" },
]

// Color Picker Component
function ColorPicker({
  selectedColor,
  onColorSelect,
}: {
  selectedColor: string
  onColorSelect: (color: string) => void
}) {
  return (
    <div className="space-y-3">
      <label className="block text-sm font-medium text-gray-700">Stage Color</label>
      <div className="grid grid-cols-8 gap-2">
        {STAGE_COLORS.map((color) => (
          <button
            key={color.value}
            type="button"
            onClick={() => onColorSelect(color.value)}
            className={`w-8 h-8 rounded-full border-2 transition-all duration-200 hover:scale-110 ${
              selectedColor === color.value
                ? "border-gray-800 ring-2 ring-gray-300"
                : "border-gray-300 hover:border-gray-400"
            }`}
            style={{ backgroundColor: color.value }}
            title={color.name}
          />
        ))}
      </div>
      <div className="flex items-center space-x-2 text-sm text-gray-600">
        <div className="w-4 h-4 rounded border border-gray-300" style={{ backgroundColor: selectedColor }} />
        <span>Selected: {STAGE_COLORS.find((c) => c.value === selectedColor)?.name || "Custom"}</span>
      </div>
    </div>
  )
}

// Create Stage Modal Component
function CreateStageModal({
  isOpen,
  onClose,
  typeId,
}: {
  isOpen: boolean
  onClose: () => void
  typeId: number
}) {
  const [name, setName] = useState("")
  const [selectedColor, setSelectedColor] = useState(STAGE_COLORS[0].value)
  const [isSubmitting, setIsSubmitting] = useState(false)
  const [createStage] = useCreateDealStageMutation()

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!name.trim()) return

    setIsSubmitting(true)
    try {
      const response = await createStage({
        name: name.trim(),
        type_id: typeId,
        color: selectedColor,
      }).unwrap()

      if (response.status) {
        toast.success("Stage created successfully!")
        setName("")
        setSelectedColor(STAGE_COLORS[0].value)
        onClose()
      } else {
        toast.error(response.message || "Failed to create stage")
      }
    } catch (error: any) {
      toast.error(error.data?.message || "An error occurred while creating the stage")
    } finally {
      setIsSubmitting(false)
    }
  }

  const handleClose = () => {
    setName("")
    setSelectedColor(STAGE_COLORS[0].value)
    onClose()
  }

  if (!isOpen) return null

  return (
    <>
      <div className="fixed inset-0 bg-black bg-opacity-50 z-50" onClick={handleClose} />
      <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div className="bg-white rounded-lg shadow-xl max-w-md w-full">
          <div className="flex items-center justify-between p-6 border-b border-gray-200">
            <h2 className="text-lg font-semibold text-gray-900">Create New Stage</h2>
            <button
              onClick={handleClose}
              className="p-2 text-gray-400 hover:text-gray-600 transition-colors duration-200"
            >
              <X className="w-5 h-5" />
            </button>
          </div>
          <form onSubmit={handleSubmit} className="p-6 space-y-4">
            <div>
              <label htmlFor="stageName" className="block text-sm font-medium text-gray-700 mb-2">
                Stage Name
              </label>
              <input
                type="text"
                id="stageName"
                value={name}
                onChange={(e) => setName(e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Enter stage name"
                required
              />
            </div>
            <ColorPicker selectedColor={selectedColor} onColorSelect={setSelectedColor} />
            <div className="flex justify-end space-x-3 pt-4">
              <button
                type="button"
                onClick={handleClose}
                className="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors duration-200"
              >
                Cancel
              </button>
              <button
                type="submit"
                disabled={isSubmitting || !name.trim()}
                className="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200"
              >
                {isSubmitting ? "Creating..." : "Create Stage"}
              </button>
            </div>
          </form>
        </div>
      </div>
    </>
  )
}

// Edit Stage Modal Component
function EditStageModal({
  isOpen,
  onClose,
  stage,
}: {
  isOpen: boolean
  onClose: () => void
  stage: DealStage | null
}) {
  const [name, setName] = useState("")
  const [selectedColor, setSelectedColor] = useState(STAGE_COLORS[0].value)
  const [isSubmitting, setIsSubmitting] = useState(false)
  const [updateStage] = useUpdateDealStageMutation()

  useEffect(() => {
    if (stage) {
      setName(stage.name)
      setSelectedColor(stage.color)
    }
  }, [stage])

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!name.trim() || !stage) return

    setIsSubmitting(true)
    try {
      const response = await updateStage({
        id: stage.id,
        name: name.trim(),
        color: selectedColor,
      }).unwrap()

      if (response.status) {
        toast.success("Stage updated successfully!")
        onClose()
      } else {
        toast.error(response.message || "Failed to update stage")
      }
    } catch (error: any) {
      toast.error(error.data?.message || "An error occurred while updating the stage")
    } finally {
      setIsSubmitting(false)
    }
  }

  const handleClose = () => {
    setName("")
    setSelectedColor(STAGE_COLORS[0].value)
    onClose()
  }

  if (!isOpen || !stage) return null

  return (
    <>
      <div className="fixed inset-0 bg-black bg-opacity-50 z-50" onClick={handleClose} />
      <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div className="bg-white rounded-lg shadow-xl max-w-md w-full">
          <div className="flex items-center justify-between p-6 border-b border-gray-200">
            <h2 className="text-lg font-semibold text-gray-900">Edit Stage</h2>
            <button
              onClick={handleClose}
              className="p-2 text-gray-400 hover:text-gray-600 transition-colors duration-200"
            >
              <X className="w-5 h-5" />
            </button>
          </div>
          <form onSubmit={handleSubmit} className="p-6 space-y-4">
            <div>
              <label htmlFor="stageName" className="block text-sm font-medium text-gray-700 mb-2">
                Stage Name
              </label>
              <input
                type="text"
                id="stageName"
                value={name}
                onChange={(e) => setName(e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Enter stage name"
                required
              />
            </div>
            <ColorPicker selectedColor={selectedColor} onColorSelect={setSelectedColor} />
            <div className="flex justify-end space-x-3 pt-4">
              <button
                type="button"
                onClick={handleClose}
                className="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors duration-200"
              >
                Cancel
              </button>
              <button
                type="submit"
                disabled={isSubmitting || !name.trim()}
                className="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200"
              >
                {isSubmitting ? "Updating..." : "Update Stage"}
              </button>
            </div>
          </form>
        </div>
      </div>
    </>
  )
}

// Draggable Stage Row Component for Settings Modal
function DraggableStageRow({
  stage,
  onEdit,
  onDelete,
}: {
  stage: DealStage
  onEdit: (stage: DealStage) => void
  onDelete: (stage: DealStage) => void
}) {
  const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({
    id: stage.id,
    data: {
      type: "stage",
      stage,
    },
  })

  const style = {
    transform: CSS.Transform.toString(transform),
    transition,
  }

  return (
    <div
      ref={setNodeRef}
      style={style}
      className={`bg-white border border-gray-200 rounded-lg p-4 ${
        isDragging ? "opacity-50 shadow-2xl scale-105" : "hover:shadow-md"
      } transition-all duration-200`}
    >
      <div className="flex items-center justify-between">
        <div className="flex items-center space-x-3">
          <div
            {...attributes}
            {...listeners}
            className="cursor-grab active:cursor-grabbing p-1 text-gray-400 hover:text-gray-600"
          >
            <GripVertical className="w-4 h-4" />
          </div>
          <div className="w-4 h-4 rounded-full border border-gray-300" style={{ backgroundColor: stage.color }} />
          <div className="flex items-center space-x-3">
            <span className="text-sm text-gray-500 font-mono">#{stage.sort}</span>
            <span className="font-medium text-gray-900">{stage.name}</span>
          </div>
        </div>
        <div className="flex items-center space-x-2">
          <button
            onClick={() => onEdit(stage)}
            className="p-2 text-gray-400 hover:text-blue-600 transition-colors duration-200"
          >
            <Edit className="w-4 h-4" />
          </button>
          <button
            onClick={() => onDelete(stage)}
            className="p-2 text-gray-400 hover:text-red-600 transition-colors duration-200"
          >
            <Trash2 className="w-4 h-4" />
          </button>
        </div>
      </div>
    </div>
  )
}

// Stages Settings Modal Component
function StagesSettingsModal({
  isOpen,
  onClose,
  activeTypeId,
}: {
  isOpen: boolean
  onClose: () => void
  activeTypeId: number
}) {
  // State Variables
  const [isShowDeleteModal, setShowDeleteModal] = useState(false)
  const [selectedStage, setSelectedStage] = useState<DealStage | null>(null)
  const [isCreateModalOpen, setIsCreateModalOpen] = useState(false)
  const [isEditModalOpen, setIsEditModalOpen] = useState(false)
  const [editStage, setEditStage] = useState<DealStage | null>(null)
  const [localStages, setLocalStages] = useState<DealStage[]>([])

  // API Mutations
  const { data: stagesData, isLoading, error, refetch: fetchStages } = useGetDealStagesQuery(activeTypeId)
  const [deleteStage, { isLoading: isLoadingDelete }] = useDeleteDealStageMutation()
  const [updateStageOrder] = useUpdateStageOrderMutation()

  const stages = stagesData?.data || []

  // Update local state when API data changes
  useEffect(() => {
    if (stages.length > 0) {
      setLocalStages([...stages].sort((a, b) => a.sort - b.sort))
    }
  }, [stages])

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

    const oldIndex = localStages.findIndex((item) => item.id === active.id)
    const newIndex = localStages.findIndex((item) => item.id === over.id)

    if (oldIndex === -1 || newIndex === -1) {
      return
    }

    // Optimistic update
    const newStages = arrayMove(localStages, oldIndex, newIndex)
    setLocalStages(newStages)

    try {
      // Update sort order on server
      const stageToUpdate = newStages[newIndex]
      await updateStageOrder({
        stage_id: stageToUpdate.id,
        sort_order: newIndex + 1, // 1-based indexing
      }).unwrap()

      // Refetch to get the latest data
      fetchStages()
      toast.success("Stage order updated successfully!")
    } catch (error: any) {
      console.error("Failed to update stage order:", error)
      toast.error("Failed to update order. Please try again.")
      // Revert optimistic update on error
      setLocalStages(stages)
    }
  }

  // Handle functions
  const handleAdd = useCallback(() => {
    setIsCreateModalOpen(true)
  }, [])

  const handleEdit = useCallback((stage: DealStage) => {
    setEditStage(stage)
    setIsEditModalOpen(true)
  }, [])

  const handleDelete = useCallback((stage: DealStage) => {
    setSelectedStage(stage)
    setShowDeleteModal(true)
  }, [])

  const handleConfirmDelete = async () => {
    if (selectedStage) {
      try {
        const response = await deleteStage(selectedStage.id).unwrap()
        if (response && response.status) {
          toast.success(response.message || "Stage deleted successfully")
          handleCancelDelete()
          fetchStages()
        } else {
          toast.error(response.message || "Error deleting stage")
        }
      } catch (error: any) {
        toast.error(error.data?.message || "An error occurred while deleting the stage")
      }
    }
  }

  const handleCancelDelete = useCallback(() => {
    setShowDeleteModal(false)
    setSelectedStage(null)
  }, [])

  const handleCreateClose = () => {
    setIsCreateModalOpen(false)
    fetchStages()
  }

  const handleEditClose = () => {
    setIsEditModalOpen(false)
    setEditStage(null)
    fetchStages()
  }

  if (!isOpen) return null

  return (
    <>
      {/* Backdrop */}
      <div className="fixed inset-0 bg-black bg-opacity-50 z-50" onClick={onClose} />

      {/* Modal */}
      <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div className="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[80vh] overflow-hidden">
          {/* Header */}
          <div className="flex items-center justify-between p-6 border-b border-gray-200">
            <div>
              <h2 className="text-xl font-semibold text-gray-900">Deal Stages Settings</h2>
              <p className="mt-1 text-sm text-gray-600">Manage your deal stages and their order</p>
            </div>
            <div className="flex items-center space-x-2">
              <button
                onClick={handleAdd}
                className="flex items-center space-x-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200"
              >
                <Plus className="w-4 h-4" />
                <span>Add Stage</span>
              </button>
              <button
                onClick={onClose}
                className="p-2 text-gray-400 hover:text-gray-600 transition-colors duration-200"
              >
                <X className="w-5 h-5" />
              </button>
            </div>
          </div>

          {/* Content */}
          <div className="p-6 overflow-y-auto max-h-[60vh]">
            {isLoading ? (
              <div className="text-center py-12">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                <p className="mt-4 text-gray-600">Loading stages...</p>
              </div>
            ) : error ? (
              <div className="text-center py-12">
                <p className="text-red-600">Error loading stages. Please try again.</p>
                <button
                  onClick={() => fetchStages()}
                  className="mt-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                >
                  Retry
                </button>
              </div>
            ) : localStages.length === 0 ? (
              <div className="text-center py-12">
                <div className="mx-auto h-12 w-12 text-gray-400">
                  <Palette className="w-12 h-12" />
                </div>
                <h3 className="mt-2 text-sm font-medium text-gray-900">No Stages Found</h3>
                <p className="mt-1 text-sm text-gray-500">No stages found. Create a new stage to get started.</p>
                <div className="mt-6">
                  <button
                    onClick={handleAdd}
                    className="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
                  >
                    <Plus className="mr-2 h-4 w-4" />
                    Add Stage
                  </button>
                </div>
              </div>
            ) : (
              <div className="space-y-3">
                <p className="text-sm text-gray-600 mb-4">💡 Drag and drop to reorder stages</p>
                <DndContext
                  sensors={sensors}
                  collisionDetection={closestCenter}
                  onDragEnd={handleDragEnd}
                  modifiers={[restrictToVerticalAxis, restrictToWindowEdges]}
                >
                  <SortableContext items={localStages.map((item) => item.id)} strategy={verticalListSortingStrategy}>
                    {localStages.map((stage) => (
                      <DraggableStageRow key={stage.id} stage={stage} onEdit={handleEdit} onDelete={handleDelete} />
                    ))}
                  </SortableContext>
                </DndContext>
              </div>
            )}
          </div>
        </div>
      </div>

      {/* Modals */}
      <CreateStageModal isOpen={isCreateModalOpen} onClose={handleCreateClose} typeId={activeTypeId} />
      <EditStageModal isOpen={isEditModalOpen} onClose={handleEditClose} stage={editStage} />
      <DeleteModal
        isOpen={isShowDeleteModal}
        onClose={() => setShowDeleteModal(false)}
        onDelete={handleConfirmDelete}
        isLoadingDelete={isLoadingDelete}
      />
    </>
  )
}

// Show More Button Component
function ShowMoreButton({
  onLoadMore,
  isLoading,
  hasMore,
  currentCount,
  totalCount,
}: {
  onLoadMore: () => void
  isLoading: boolean
  hasMore: boolean
  currentCount: number
  totalCount: number
}) {
  if (!hasMore) {
    return (
      <div className="text-center py-4">
        <p className="text-sm text-gray-500">
          Showing all {totalCount} deal{totalCount !== 1 ? "s" : ""}
        </p>
      </div>
    )
  }

  return (
    <div className="text-center py-6">
      <button
        onClick={onLoadMore}
        disabled={isLoading}
        className="inline-flex items-center px-6 py-3 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200"
      >
        {isLoading ? (
          <>
            <Loader2 className="w-4 h-4 mr-2 animate-spin" />
            Loading more...
          </>
        ) : (
          <>
            <ChevronDown className="w-4 h-4 mr-2" />
            Show More ({currentCount} of {totalCount})
          </>
        )}
      </button>
    </div>
  )
}

// User/Person Avatar Component
function UserAvatar({
  user,
  type = "user",
}: {
  user: any
  type?: "user" | "person"
}) {
  const [imageError, setImageError] = useState(false)

  const getImageSrc = () => {
    if (type === "user") {
      return user?.avatar ? `${ASSETS_URL}/storage/${user?.avatar}` : null
    } else {
      return user?.picture ? `${ASSETS_URL}${user?.picture}` : null
    }
  }

  const getInitials = () => {
    if (type === "user") {
      return user?.name
        .split(" ")
        .map((n: string) => n[0])
        .join("")
        .toUpperCase()
    } else {
      return `${user?.first_name?.[0]||''} ${user?.last_name?.[0] || (user?.first_name?.[1]||'')}`
    }
  }

  const getName = () => {
    if (type === "user") {
      return user?.name
    } else {
      return `${user?.first_name} ${user?.last_name}`
    }
  }

  const imageSrc = getImageSrc()
  const shouldShowImage = imageSrc && !imageError

  return (
    <div
      className={`w-8 h-8 rounded-full flex items-center justify-center text-xs font-medium overflow-hidden ${
        type === "user" ? "bg-gray-300 text-gray-700" : "bg-blue-300 text-blue-700"
      }`}
      title={getName()}
    >
      {shouldShowImage ? (
        <img
          src={imageSrc || "/placeholder.svg"}
          alt={getName()}
          className="h-full w-full object-cover rounded-full"
          onError={() => setImageError(true)}
        />
      ) : (
        <span>{getInitials()}</span>
      )}
    </div>
  )
}

// Draggable Stage Column Component
function DraggableStageColumn({
  stage,
  deals,
  stageTotals,
  onCreateDeal,
  onEditDeal,
  onDeleteDeal,
  onEditStage,
  formatCurrency,
  formatLargeCurrency,
  isLoadingDeals,
  isDragOverStage,
  isDraggingDeal,
  isDraggingStage, // NEW: Added this prop
  onLoadMore,
  isLoadingMore,
  hasMore,
  currentCount,
  totalCount,
}: {
  stage: DealStage
  deals: Deal[]
  stageTotals: { count: number; total: number }
  onCreateDeal: (stageId: number) => void
  onEditDeal: (deal: Deal) => void
  onDeleteDeal: (deal: Deal) => void
  onEditStage: (stage: DealStage) => void
  formatCurrency: (amount: number) => string
  formatLargeCurrency: (amount: number) => string
  isLoadingDeals: boolean
  isDragOverStage: boolean
  isDraggingDeal: boolean
  isDraggingStage: boolean // NEW: Added this prop
  onLoadMore: () => void
  isLoadingMore: boolean
  hasMore: boolean
  currentCount: number
  totalCount: number
}) {
  const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({
    id: `stage-${stage.id}`,
    data: {
      type: "stage",
      stage,
    },
  })

  const style = {
    transform: CSS.Transform.toString(transform),
    transition,
  }

  // Determine the visual state of the stage
  const getStageClasses = () => {
    let baseClasses = "bg-white rounded-lg shadow-sm min-w-80 transition-all duration-300 ease-in-out"

    if (isDragging) {
      baseClasses += " opacity-50"
    } else if (isDragOverStage && isDraggingDeal) {
      // Stage is being hovered over while dragging a deal
      baseClasses += " ring-4 ring-blue-400 ring-opacity-50 shadow-xl scale-105 bg-blue-50"
    } else if (isDraggingDeal) {
      // Deal is being dragged but not over this stage
      baseClasses += " opacity-75 scale-95"
    }

    return baseClasses
  }

  return (
    <div ref={setNodeRef} style={style} className={getStageClasses()}>
      {/* Draggable Stage Header with enhanced visual feedback */}
      <div
        {...attributes}
        {...listeners}
        className={`h-2 rounded-t-lg cursor-grab active:cursor-grabbing transition-all duration-300 ${
          isDragOverStage && isDraggingDeal ? "h-3 shadow-lg" : ""
        }`}
        style={{ backgroundColor: stage.color }}
      >
        {/* Animated pulse effect when deal is being dragged over */}
        {isDragOverStage && isDraggingDeal && (
          <div
            className="absolute inset-0 rounded-t-lg animate-pulse opacity-60"
            style={{ backgroundColor: stage.color }}
          />
        )}
      </div>

      <div className="p-4 border-b border-gray-200">
        <div className="flex items-center justify-between">
          <div>
            <div className="flex items-center space-x-2">
              <h3
                className={`text-lg font-semibold cursor-grab transition-all duration-300 ${
                  isDragOverStage && isDraggingDeal ? "text-blue-700 scale-105" : "text-gray-900"
                }`}
                {...attributes}
                {...listeners}
              >
                {stage.name}
                {isDragOverStage && isDraggingDeal && <span className="ml-2 text-sm animate-bounce">📥</span>}
              </h3>
              <button
                onClick={() => onEditStage(stage)}
                className="p-1 text-gray-400 hover:text-blue-600 transition-colors duration-200"
                title="Edit stage"
              >
                <Edit className="w-3 h-3" />
              </button>
            </div>
            <div className="flex items-center space-x-2 text-sm text-gray-500">
              <span>{stageTotals.count} deals</span>
              <span className="text-green-600 font-medium">{formatLargeCurrency(stageTotals.total)}</span>
            </div>
          </div>
          <button
            onClick={() => onCreateDeal(stage.id)}
            className={`w-8 h-8 text-white rounded-full flex items-center justify-center transition-all duration-300 ${
              isDragOverStage && isDraggingDeal
                ? "bg-blue-600 hover:bg-blue-700 scale-110 shadow-lg"
                : "bg-blue-500 hover:bg-blue-600"
            }`}
          >
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
            </svg>
          </button>
        </div>
      </div>

      {/* Droppable Deals Area with enhanced drop zone */}
      <SortableContext items={deals.map((deal) => `deal-${deal.id}`)} strategy={verticalListSortingStrategy}>
        <div
          className={`p-4 space-y-4 max-h-96 overflow-y-auto min-h-32 transition-all duration-300 ${
            isDragOverStage && isDraggingDeal
              ? "bg-gradient-to-b from-blue-50 to-blue-100 border-2 border-dashed border-blue-300"
              : ""
          }`}
          data-stage-id={stage.id}
          style={{
            // Ensure the drop zone covers the entire area
            position: "relative",
            minHeight: deals.length === 0 ? "200px" : "32px",
            // UPDATED: Hide all deals when any stage is being dragged
            display: isDraggingStage ? "none" : "block",
          }}
        >
          {/* Drop zone indicator when dragging over empty stage */}
          {isDragOverStage && isDraggingDeal && deals.length === 0 && (
            <div className="flex flex-col items-center justify-center py-12 text-blue-600 animate-pulse">
              <div className="w-16 h-16 border-4 border-dashed border-blue-400 rounded-full flex items-center justify-center mb-4">
                <svg className="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                </svg>
              </div>
              <p className="text-lg font-semibold">Drop deal here</p>
              <p className="text-sm opacity-75">Release to move to {stage.name}</p>
            </div>
          )}

          {/* Loading state */}
          {isLoadingDeals ? (
            <div className="text-center py-8">
              <div className="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600 mx-auto"></div>
              <p className="mt-2 text-sm text-gray-500">Loading deals...</p>
            </div>
          ) : deals.length === 0 && !(isDragOverStage && isDraggingDeal) ? (
            <div className="text-center py-8 text-gray-500">
              <p className="text-sm">No deals in this stage</p>
              <p className="text-xs mt-1">Drag deals here or create new ones</p>
            </div>
          ) : (
            <>
              {/* Drop indicator at the top when dragging over */}
              {isDragOverStage && isDraggingDeal && deals.length > 0 && (
                <div className="h-2 bg-blue-400 rounded-full animate-pulse shadow-lg mb-2" />
              )}

              {deals.map((deal) => (
                <DraggableDealCard
                  key={deal.id}
                  deal={deal}
                  onEdit={onEditDeal}
                  onDelete={onDeleteDeal}
                  formatCurrency={formatCurrency}
                  isDraggingDeal={isDraggingDeal}
                />
              ))}

              {/* Drop indicator at the bottom when dragging over */}
              {isDragOverStage && isDraggingDeal && deals.length > 0 && (
                <div className="h-2 bg-blue-400 rounded-full animate-pulse shadow-lg mt-2" />
              )}
            </>
          )}
        </div>
      </SortableContext>

      {/* Show More Button for this stage - UPDATED: Hide when dragging stages */}
      {!isLoadingDeals && !isDraggingStage && (
        <div className="px-2 pb-2">
          {hasMore ? (
            <button
              onClick={onLoadMore}
              disabled={isLoadingMore}
              className="w-full py-2 px-3 text-xs font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center space-x-1"
            >
              {isLoadingMore ? (
                <>
                  <Loader2 className="w-3 h-3 animate-spin" />
                  <span>Loading...</span>
                </>
              ) : (
                <>
                  <ChevronDown className="w-3 h-3" />
                  <span>
                    Show More ({currentCount} of {totalCount})
                  </span>
                </>
              )}
            </button>
          ) : totalCount > 0 ? (
            <div className="text-center py-2">
              <p className="text-xs text-gray-500">
                All {totalCount} deal{totalCount !== 1 ? "s" : ""} shown
              </p>
            </div>
          ) : null}
        </div>
      )}
    </div>
  )
}

// Draggable Deal Card Component with updated avatar display
function DraggableDealCard({
  deal,
  onEdit,
  onDelete,
  formatCurrency,
  isDraggingDeal,
}: {
  deal: Deal
  onEdit: (deal: Deal) => void
  onDelete: (deal: Deal) => void
  formatCurrency: (amount: number) => string
  isDraggingDeal: boolean
}) {
  const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({
    id: `deal-${deal.id}`,
    data: {
      type: "deal",
      deal,
    },
  })

  const style = {
    transform: CSS.Transform.toString(transform),
    transition,
    zIndex: isDragging ? 1000 : 1,
  }

  const getDealClasses = () => {
    let baseClasses =
      "bg-gray-50 rounded-lg p-4 hover:bg-gray-100 transition-all duration-300 cursor-grab active:cursor-grabbing"

    if (isDragging) {
      baseClasses += " opacity-50 shadow-2xl scale-105 rotate-2"
    } else if (isDraggingDeal && !isDragging) {
      // Other deals when one is being dragged
      baseClasses += " opacity-60 scale-95"
    }

    return baseClasses
  }

  return (
    <div ref={setNodeRef} style={style} className={getDealClasses()} {...attributes} {...listeners}>
      <div className="flex justify-between items-start mb-2">
        <h4 className="font-medium text-gray-900 pointer-events-none">{deal.name}</h4>
        <div className="flex space-x-1 pointer-events-auto" onClick={(e) => e.stopPropagation()}>
          <button
            onClick={(e) => {
              e.stopPropagation()
              e.preventDefault()
              onEdit(deal)
            }}
            className="p-1 text-gray-400 hover:text-blue-600 transition-colors duration-200"
            style={{ pointerEvents: "auto" }}
          >
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
              />
            </svg>
          </button>
          <button
            onClick={(e) => {
              e.stopPropagation()
              e.preventDefault()
              onDelete(deal)
            }}
            className="p-1 text-gray-400 hover:text-red-600 transition-colors duration-200"
            style={{ pointerEvents: "auto" }}
          >
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
              />
            </svg>
          </button>
        </div>
      </div>

      {deal.description && <p className="text-sm text-gray-600 mb-3 pointer-events-none">{deal.description}</p>}

      <div className="flex items-center space-x-2 mb-3 pointer-events-none">
        <span className="text-lg font-semibold text-green-600">{formatCurrency(deal.price)}</span>
      </div>

      {deal.projected_close_date && (
        <p className="text-xs text-gray-500 mb-3 pointer-events-none">
          Projected Close: {new Date(deal.projected_close_date).toLocaleDateString()}
        </p>
      )}

      <div className="flex items-center space-x-2 pointer-events-none">
        {deal.users.map((user) => (
          <UserAvatar key={user?.id} user={user} type="user" />
        ))}
        {
          (deal.users.length > 0 && deal.people.length > 0) && (
            <span className="w-[1px] h-[25px] bg-gray-200"></span>
          )
        }
        {deal.people.map((person) => (
          <UserAvatar key={person.id} user={person} type="person" />
        ))}
      </div>
    </div>
  )
}

// Enhanced Drag Overlay Components
function DragOverlayContent({ activeId, stages, deals }: { activeId: string; stages: DealStage[]; deals: Deal[] }) {
  if (activeId.startsWith("stage-")) {
    const stageId = Number.parseInt(activeId.replace("stage-", ""))
    const stage = stages.find((s) => s.id === stageId)

    if (!stage) return null

    return (
      <div className="bg-white rounded-lg shadow-2xl border-4 border-blue-500 min-w-80 transform rotate-3 animate-pulse">
        <div className="h-3 rounded-t-lg shadow-lg" style={{ backgroundColor: stage.color }}></div>
        <div className="p-4">
          <h3 className="text-lg font-semibold text-gray-900">{stage.name}</h3>
          <p className="text-sm text-blue-600 font-medium">Moving stage...</p>
        </div>
      </div>
    )
  }

  if (activeId.startsWith("deal-")) {
    const dealId = Number.parseInt(activeId.replace("deal-", ""))
    const deal = deals.find((d) => d.id === dealId)

    if (!deal) {
      console.log(
        "Deal not found for ID:",
        dealId,
        "Available deals:",
        deals.map((d) => d.id),
      )
      return null
    }

    return (
      <div className="bg-gray-50 rounded-lg p-4 shadow-2xl border-4 border-blue-500 min-w-64 transform rotate-6 animate-bounce">
        <div className="flex items-center space-x-2 mb-2">
          <div className="w-3 h-3 bg-blue-500 rounded-full animate-ping"></div>
          <h4 className="font-medium text-gray-900">{deal.name}</h4>
        </div>
        <span className="text-lg font-semibold text-green-600">
          {new Intl.NumberFormat("en-US", {
            style: "currency",
            currency: "USD",
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
          }).format(deal.price)}
        </span>
        <p className="text-xs text-blue-600 font-medium mt-1">Drop to move deal</p>
      </div>
    )
  }

  return null
}

export default function DraggableDealsBoard() {
  // State for active deal type (buyers/sellers) - Initialize as null until we know the first type
  const [activeTypeId, setActiveTypeId] = useState<number | null>(null)
  const [activeId, setActiveId] = useState<string | null>(null)
  const [overId, setOverId] = useState<string | null>(null)

  // Pagination state - per stage
  const [stagePages, setStagePages] = useState<Record<number, number>>({}) // Track current page per stage
  const [stageDeals, setStageDeals] = useState<Record<number, Deal[]>>({}) // Store loaded deals per stage
  const [stageLoadingMore, setStageLoadingMore] = useState<Record<number, boolean>>({}) // Loading state per stage
  const [perPage] = useState(10) // Show 10 items per stage initially

  // Modal states
  const [isShowCreateModal, setShowCreateModal] = useState(false)
  const [isShowEditModal, setShowEditModal] = useState(false)
  const [isShowDeleteModal, setShowDeleteModal] = useState(false)
  const [selectedDeal, setSelectedDeal] = useState<Deal | null>(null)
  const [createModalStageId, setCreateModalStageId] = useState<number>(1)

  // Stages Settings Modal state
  const [isShowStagesSettings, setShowStagesSettings] = useState(false)
  const [editStageModal, setEditStageModal] = useState<DealStage | null>(null)
  const [isShowEditStageModal, setShowEditStageModal] = useState(false)

  // Local state for optimistic updates
  const [localStages, setLocalStages] = useState<DealStage[]>([])

  // API calls
  const { data: dealTypesData, isLoading: isLoadingTypes } = useGetDealTypesQuery()
  const {
    data: stagesData,
    isLoading: isLoadingStages,
    refetch: refetchStages,
  } = useGetDealStagesQuery(activeTypeId!, {
    skip: activeTypeId === null, // Skip the query if we don't have an activeTypeId yet
  })

  const {
    data: dealsData,
    isLoading: isLoadingDeals,
    refetch,
  } = useGetDealsQuery(
    {
      type_id: activeTypeId!,
      // Remove pagination parameters - we'll fetch all and paginate per stage
    },
    {
      skip: activeTypeId === null,// Skip the query if we don't have an activeTypeId yet
    },
  )

  const [deleteDeal, { isLoading: isLoadingDelete }] = useDeleteDealMutation()
  const [updateDealStageOnly] = useUpdateDealStageOnlyMutation()
  const [updateDealStage] = useUpdateDealStageMutation()
  const [updateStageOrder] = useUpdateStageOrderMutation()

  const dealTypes = dealTypesData?.data || []
  const stages = localStages.length > 0 ? localStages : stagesData?.data || []
  const currentPageDeals = dealsData?.data?.items || []
  const pagination = dealsData?.data?.meta || { current_page: 1, last_page: 1, total: 0 }

  // Set the first deal type as active when deal types are loaded
  useEffect(() => {
    if (dealTypes.length > 0 && activeTypeId === null) {
      setActiveTypeId(dealTypes[0].id)
    }
  }, [dealTypes, activeTypeId])

  // Initialize stage pagination when stages change
  useEffect(() => {
    if (stages.length > 0) {
      const initialPages: Record<number, number> = {}
      const initialLoadingStates: Record<number, boolean> = {}
      stages.forEach((stage) => {
        initialPages[stage.id] = 1
        initialLoadingStates[stage.id] = false
      })
      setStagePages(initialPages)
      setStageLoadingMore(initialLoadingStates)
    }
  }, [stages])

  // Update stage deals when API data changes
  useEffect(() => {
    if (dealsData?.data?.items && stages.length > 0) {
      const allDeals = dealsData.data.items
      const newStageDeals: Record<number, Deal[]> = {}

      stages.forEach((stage) => {
        const stageDealsAll = allDeals.filter((deal) => deal.stage_id === stage.id)
        const currentPage = stagePages[stage.id] || 1
        const dealsToShow = stageDealsAll.slice(0, currentPage * perPage)
        newStageDeals[stage.id] = dealsToShow
      })

      setStageDeals(newStageDeals)
    }
  }, [dealsData, stages, stagePages, perPage])

  // Determine drag states
  const isDraggingDeal = activeId?.startsWith("deal-") || false
  const isDraggingStage = activeId?.startsWith("stage-") || false

  // Update local stages when API data changes
  useEffect(() => {
    if (stagesData?.data) {
      setLocalStages([...stagesData.data].sort((a, b) => a.sort - b.sort))
    }
  }, [stagesData])

  // Group deals by stage using stageDeals instead of allDeals
  const dealsByStage = stages.reduce(
    (acc, stage) => {
      acc[stage.id] = stageDeals[stage.id] || []
      return acc
    },
    {} as Record<number, Deal[]>,
  )

  // Get totals from API response
  const apiTotals = dealsData?.data?.totals || { total_deals_count: 0, total_deals_price: 0 }

  // Calculate totals per stage from the allDeals data
  const stageTotals = stages.reduce(
    (acc, stage) => {
      const stageDeals = dealsByStage[stage.id] || []
      acc[stage.id] = {
        count: stageDeals.length,
        total: stageDeals.reduce((sum, deal) => sum + deal.price, 0),
      }
      return acc
    },
    {} as Record<number, { count: number; total: number }>,
  )

  // Add overall totals display
  const overallTotals = {
    count: apiTotals.total_deals_count,
    total: apiTotals.total_deals_price,
  }

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat("en-US", {
      style: "currency",
      currency: "USD",
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }).format(amount)
  }

  const formatLargeCurrency = (amount: number) => {
    if (amount >= 1000000) {
      return `$${(amount / 1000000).toFixed(1)}M`
    } else if (amount >= 1000) {
      return `$${(amount / 1000).toFixed(0)}K`
    }
    return formatCurrency(amount)
  }

  // Drag and Drop Sensors
  const sensors = useSensors(
    useSensor(PointerSensor, {
      activationConstraint: {
        distance: 3, // Reduced from 8 to make dragging more responsive
      },
    }),
    useSensor(KeyboardSensor, {
      coordinateGetter: sortableKeyboardCoordinates,
    }),
  )

  // Handle drag start
  const handleDragStart = (event: DragStartEvent) => {
    setActiveId(event.active.id as string)
  }

  // Handle drag over (for deals moving between stages)
  const handleDragOver = (event: DragOverEvent) => {
    const { active, over } = event

    setOverId((over?.id as string) || null)

    if (!over) return

    const activeId = active.id as string
    const overId = over.id as string

    // Handle deal dragging over stages or other deals
    if (activeId.startsWith("deal-")) {
      if (overId.startsWith("stage-")) {
        // Dragging over a stage directly
        return
      } else if (overId.startsWith("deal-")) {
        // Dragging over another deal - find which stage it belongs to
        const targetDeal = Object.values(dealsByStage)
          .flat()
          .find((deal) => `deal-${deal.id}` === overId)

        if (targetDeal) {
          setOverId(`stage-${targetDeal.stage_id}`)
        }
      }
    }
  }

  // Handle drag end
  const handleDragEnd = async (event: DragEndEvent) => {
    const { active, over } = event

    setActiveId(null)
    setOverId(null)

    if (!over) return

    const activeId = active.id as string
    const overId = over.id as string

    // Handle stage reordering
    if (activeId.startsWith("stage-") && overId.startsWith("stage-")) {
      const activeStageId = Number.parseInt(activeId.replace("stage-", ""))
      const overStageId = Number.parseInt(overId.replace("stage-", ""))

      if (activeStageId === overStageId) return

      const oldIndex = stages.findIndex((stage) => stage.id === activeStageId)
      const newIndex = stages.findIndex((stage) => stage.id === overStageId)

      // Optimistic update
      const newStages = arrayMove(stages, oldIndex, newIndex)
      setLocalStages(newStages)

      // Update sort orders on the server
      try {
        await updateStageOrder({
          stage_id: activeStageId,
          sort_order: newIndex + 1, // 1-based indexing
        }).unwrap()

        // Refetch to get the latest data
        handleRefreshData()
      } catch (error) {
        console.error("Failed to update stage order:", error)
        // Revert optimistic update on error
        setLocalStages(stages)
      }
    }

    // Handle deal moving between stages
    if (activeId.startsWith("deal-") && (overId.startsWith("stage-") || overId.startsWith("deal-"))) {
      const dealId = Number.parseInt(activeId.replace("deal-", ""))
      let newStageId: number

      if (overId.startsWith("stage-")) {
        newStageId = Number.parseInt(overId.replace("stage-", ""))
      } else {
        // If dropped on another deal, find the stage of that deal
        const targetDeal = Object.values(dealsByStage)
          .flat()
          .find((deal) => `deal-${deal.id}` === overId)

        if (!targetDeal) return
        newStageId = targetDeal.stage_id
      }

      const deal = Object.values(dealsByStage)
        .flat()
        .find((d) => d.id === dealId)

      if (!deal || deal.stage_id === newStageId) return

      try {
        await updateDealStageOnly({
          id: dealId,
          stage_id: newStageId,
        }).unwrap()

        // Refetch to get updated data
        handleRefreshData()
      } catch (error) {
        console.error("Failed to update deal stage:", error)
      }
    }
  }

  // Handle create deal
  const handleCreateDeal = useCallback((stageId: number) => {
    setCreateModalStageId(stageId)
    setShowCreateModal(true)
  }, [])

  // Handle edit deal
  const handleEditDeal = useCallback((deal: Deal) => {
    setSelectedDeal(deal)
    setShowEditModal(true)
  }, [])

  // Handle delete deal
  const handleDeleteDeal = useCallback((deal: Deal) => {
    setSelectedDeal(deal)
    setShowDeleteModal(true)
  }, [])

  // Handle edit stage
  const handleEditStage = useCallback((stage: DealStage) => {
    setEditStageModal(stage)
    setShowEditStageModal(true)
  }, [])

  // Confirm delete
  const handleConfirmDelete = async () => {
    if (selectedDeal) {
      try {
        const response = await deleteDeal(selectedDeal.id).unwrap()
        if (response.status) {
          setShowDeleteModal(false)
          setSelectedDeal(null)
          handleRefreshData()
        }
      } catch (error) {
        console.error("Error deleting deal:", error)
      }
    }
  }

  // Cancel delete
  const handleCancelDelete = useCallback(() => {
    setShowDeleteModal(false)
    setSelectedDeal(null)
  }, [])

  // Handle success callbacks
  const handleSuccess = useCallback(() => {
    handleRefreshData()
  }, [])

  const handleStageSuccess = useCallback(() => {
    refetchStages()
    handleRefreshData()
  }, [refetchStages])

  // Handle load more for specific stage
  const handleLoadMoreForStage = useCallback((stageId: number) => {
    setStageLoadingMore((prev) => ({ ...prev, [stageId]: true }))

    setTimeout(() => {
      setStagePages((prev) => ({ ...prev, [stageId]: (prev[stageId] || 1) + 1 }))
      setStageLoadingMore((prev) => ({ ...prev, [stageId]: false }))
    }, 500) // Small delay to show loading state
  }, [])

  // Handle refresh data (reset all stages to first page)
  const handleRefreshData = useCallback(() => {
    const resetPages: Record<number, number> = {}
    stages.forEach((stage) => {
      resetPages[stage.id] = 1
    })
    setStagePages(resetPages)
    setStageDeals({})
    refetch()
  }, [refetch, stages])

  // Check if there are more deals to load
  const hasMoreDeals = false

  if (isLoadingTypes) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
          <p className="mt-4 text-gray-600">Loading deal types...</p>
        </div>
      </div>
    )
  }

  // Show loading if we don't have an active type ID yet
  if (activeTypeId === null) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
          <p className="mt-4 text-gray-600">Initializing...</p>
        </div>
      </div>
    )
  }

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <div className="bg-white border-b border-gray-200">
        <div className="px-6 py-4">
          <div className="flex items-center justify-between">
            <div className="flex items-center space-x-4">
              <div className="flex space-x-1">
                {dealTypes.map((type) => (
                  <button
                    key={type.id}
                    onClick={() => setActiveTypeId(type.id)}
                    className={`px-4 py-2 text-sm font-medium border-b-2 transition-all duration-200 ${
                      activeTypeId === type.id
                        ? "text-blue-600 border-blue-600"
                        : "text-gray-500 border-transparent hover:text-gray-700"
                    }`}
                  >
                    {type.name}
                  </button>
                ))}
              </div>
              <Link
                to={"/admin/deal-types"}
                className="p-2 text-gray-400 hover:text-gray-600 transition-colors duration-200"
                title="Stages Settings"
              >
                <Settings className="w-5 h-5" />
              </Link>
            </div>
            <div className="flex items-center space-x-4">
              <div className="flex items-center space-x-2">
                <svg className="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                  />
                </svg>
                <Link
                  to={"/deal-reporting"}
                  className="text-sm text-gray-600 bg-transparent border-none focus:outline-none"
                >
                  Deal Reporting
                </Link>
              </div>
              <button
                onClick={() => setShowStagesSettings(true)}
                className="text-sm text-green-600 bg-transparent border-none focus:outline-none"
              >
                Edit Stages
              </button>
            </div>
          </div>
        </div>
      </div>

      {/* Enhanced Drag and Drop Instructions */}
      <div
        className={`px-6 py-2 border-b transition-all duration-300 ${
          isDraggingDeal
            ? "bg-blue-100 border-blue-300"
            : isDraggingStage
              ? "bg-purple-100 border-purple-300"
              : "bg-blue-50 border-blue-200"
        }`}
      >
        <div className="flex items-center justify-between">
          <p
            className={`text-sm transition-colors duration-300 ${
              isDraggingDeal ? "text-blue-800" : isDraggingStage ? "text-purple-800" : "text-blue-700"
            }`}
          >
            {isDraggingDeal ? (
              <>
                🎯 <strong>Dragging Deal:</strong> Drop on any stage to move the deal there!
              </>
            ) : isDraggingStage ? (
              <>
                🔄 <strong>Reordering Stage:</strong> Drop on another stage to change the order!
              </>
            ) : (
              <>
                💡 <strong>Tip:</strong> Drag stage headers to reorder stages, or drag deals between stages to move
                them.
              </>
            )}
          </p>
          {/* Update the pagination info in the header */}
          <div className="text-sm text-gray-600">
            Total: {dealsData?.data?.totals?.total_deals_count || 0} deals across all stages
          </div>
        </div>
      </div>

      {/* Main Content */}
      <div className="p-6">
        {isLoadingStages ? (
          <div className="text-center py-12">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
            <p className="mt-4 text-gray-600">Loading stages...</p>
          </div>
        ) : (
          <DndContext
            sensors={sensors}
            collisionDetection={closestCenter} // Changed from closestCorners
            onDragStart={handleDragStart}
            onDragOver={handleDragOver}
            onDragEnd={handleDragEnd}
          >
            <div className="flex gap-6 overflow-x-auto pb-4">
              <SortableContext
                items={stages.map((stage) => `stage-${stage.id}`)}
                strategy={horizontalListSortingStrategy}
              >
                {stages.map((stage) => {
                  const isDragOverThisStage = overId === `stage-${stage.id}` && isDraggingDeal

                  return (
                    <DraggableStageColumn
                      key={stage.id}
                      stage={stage}
                      deals={dealsByStage[stage.id] || []}
                      stageTotals={stageTotals[stage.id] || { count: 0, total: 0 }}
                      onCreateDeal={handleCreateDeal}
                      onEditDeal={handleEditDeal}
                      onDeleteDeal={handleDeleteDeal}
                      onEditStage={handleEditStage}
                      formatCurrency={formatCurrency}
                      formatLargeCurrency={formatLargeCurrency}
                      isLoadingDeals={isLoadingDeals}
                      isDragOverStage={isDragOverThisStage}
                      isDraggingDeal={isDraggingDeal}
                      isDraggingStage={isDraggingStage} // UPDATED: Pass the isDraggingStage prop
                      // Add pagination props
                      onLoadMore={() => handleLoadMoreForStage(stage.id)}
                      isLoadingMore={stageLoadingMore[stage.id] || false}
                      hasMore={(() => {
                        const allStageDeals = dealsData?.data?.items?.filter((deal) => deal.stage_id === stage.id) || []
                        const currentlyShown = dealsByStage[stage.id]?.length || 0
                        return currentlyShown < allStageDeals.length
                      })()}
                      currentCount={dealsByStage[stage.id]?.length || 0}
                      totalCount={(() => {
                        const allStageDeals = dealsData?.data?.items?.filter((deal) => deal.stage_id === stage.id) || []
                        return allStageDeals.length
                      })()}
                    />
                  )
                })}
              </SortableContext>
            </div>

            <DragOverlay>
              {activeId ? (
                <DragOverlayContent
                  activeId={activeId}
                  stages={stages}
                  deals={Object.values(dealsByStage).flat()} // Pass all deals from all stages
                />
              ) : null}
            </DragOverlay>
          </DndContext>
        )}
      </div>

      {/* Stages Settings Modal */}
      <StagesSettingsModal
        isOpen={isShowStagesSettings}
        onClose={() => setShowStagesSettings(false)}
        activeTypeId={activeTypeId}
      />

      {/* Individual Stage Edit Modal */}
      <EditStageModal
        isOpen={isShowEditStageModal}
        onClose={() => {
          setShowEditStageModal(false)
          setEditStageModal(null)
          handleStageSuccess()
        }}
        stage={editStageModal}
      />

      {/* Deal Modals */}
      <CreateDealModal
        isOpen={isShowCreateModal}
        onClose={() => setShowCreateModal(false)}
        onSuccess={handleSuccess}
        stageId={createModalStageId}
        typeId={activeTypeId}
      />

      <EditDealModal
        isOpen={isShowEditModal}
        onClose={() => setShowEditModal(false)}
        onSuccess={handleSuccess}
        deal={selectedDeal}
      />

      <DeleteModal
        isOpen={isShowDeleteModal}
        onClose={handleCancelDelete}
        onDelete={handleConfirmDelete}
        isLoadingDelete={isLoadingDelete}
      />
    </div>
  )
}
