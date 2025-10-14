"use client"

import { useCallback, useState } from "react"
import { toast } from "react-toastify"

// CRUDS Components
import DeleteModal from "../../components/modal/DeleteModal"
import CreateStageModal from "./CreateStageModal"
import EditStageModal from "./EditStageModal"

// API Calls
import { useGetStagesQuery, useDeleteStageMutation } from "./stagesApi"
import DynamicTable, { type Column } from "../../components/tables/BasicTableOne"

import { Plus } from "lucide-react"
import AdminLayout from "../../layout/AdminLayout"
import type { Stage } from "../../types/stages"

export default function Stages() {
  // State Variables for client-side pagination
  const [isShowDeleteModal, setShowDeleteModal] = useState(false)
  const [selectedStageId, setSelectedStageId] = useState<number | null>(null)

  // Modal states
  const [isCreateModalOpen, setIsCreateModalOpen] = useState(false)
  const [isEditModalOpen, setIsEditModalOpen] = useState(false)
  const [editStageId, setEditStageId] = useState<number | null>(null)

  // API Mutations - fetch all stages without pagination
  const { data, isLoading, error, refetch: fetchStages } = useGetStagesQuery()
  const [deleteStage, { isLoading: isLoadingDelete }] = useDeleteStageMutation()


  // Table columns
  const columns: Column[] = [
    {
      key: "name",
      label: "Name",
      render: (row: Stage) => <div>{row.name}</div>,
      isMain: true,
    },
    {
      key: "description",
      label: "Description",
      render: (row: Stage) => <div>{row.description || "-"}</div>,
      isMain: true,
    },
    {
      key: "created_at",
      label: "Created At",
      render: (row: Stage) => <div>{row.created_at ? new Date(row.created_at).toLocaleDateString() : "-"}</div>,
      isMain: false,
    },
    {
      key: "actions",
      label: "Actions",
      render: (row: Stage) => (
        <div className="flex items-center gap-2">
          <button className="px-3 py-1 bg-green-500 text-white rounded" onClick={() => handleEdit(row.id)}>
            Edit
          </button>
          <button className="px-3 py-1 bg-red-500 text-white rounded" onClick={() => handleDelete(row.id)}>
            Delete
          </button>
        </div>
      ),
      isMain: true,
    },
  ]

  // Open Create Stage Modal
  const handleAdd = useCallback(() => {
    setIsCreateModalOpen(true)
  }, [])

  // Open Edit Stage Modal
  const handleEdit = useCallback((id: number) => {
    setEditStageId(id)
    setIsEditModalOpen(true)
  }, [])

  // Ask Delete
  const handleDelete = useCallback((id: number) => {
    setSelectedStageId(id)
    setShowDeleteModal(true)
  }, [])

  // Delete
  const handleConfirmDelete = async () => {
    if (selectedStageId) {
      try {
        const response = await deleteStage(selectedStageId).unwrap()

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

  // Cancel Delete
  const handleCancelDelete = useCallback(() => {
    setShowDeleteModal(false)
    setSelectedStageId(null)
  }, [])


  return (
    <AdminLayout>
      <div className="p-4">
        <DynamicTable
          pageTitle="Stages"
          actionButton={
            <div className="flex items-center gap-3">
              <button className="relative group bg-gray-800 text-white p-2 rounded" onClick={handleAdd}>
                <Plus size={18} />
                <div className="absolute top-full right-1/2 translate-x-1/2 mt-2 w-max text-xs text-white bg-black rounded px-2 py-1 opacity-0 group-hover:opacity-100 transition">
                  Add Stage
                </div>
              </button>
            </div>
          }
          columns={columns}
          data={data?.data || []}
          totalCount={data?.data.length || 0}
          idField="id"
          noDataTilte="No Stages Found"
          noDataDescription="No stages found. Create a new stage to get started."
          error={error}
          isLoading={isLoading}
        />

        {/* Create Stage Modal */}
        <CreateStageModal
          isOpen={isCreateModalOpen}
          onClose={() => {
            setIsCreateModalOpen(false)
            fetchStages()
          }}
        />

        {/* Edit Stage Modal */}
        <EditStageModal
          isOpen={isEditModalOpen}
          onClose={() => {
            setIsEditModalOpen(false)
            setEditStageId(null)
            fetchStages()
          }}
          stageId={editStageId}
        />

        {/* Delete Modal */}
        <DeleteModal
          isOpen={isShowDeleteModal}
          onClose={() => setShowDeleteModal(false)}
          onDelete={handleConfirmDelete}
          isLoadingDelete={isLoadingDelete}
        />
      </div>
    </AdminLayout>
  )
}
