"use client"

import { useCallback, useState } from "react"
import { toast } from "react-toastify"

// CRUDS Components
import DeleteModal from "../../components/modal/DeleteModal"
import CreateCustomFieldModal from "./CreateCustomFieldModal"
import EditCustomFieldModal from "./EditCustomFieldModal"

// API Calls
import { useGetCustomFieldsQuery, useDeleteCustomFieldMutation, type CustomField } from "./customFieldsApi"
import DynamicTable, { type Column } from "../../components/tables/BasicTableOne"

import { Plus } from "lucide-react"
import AdminLayout from "../../layout/AdminLayout"

const FIELD_TYPES = {
  0: "Text",
  1: "Date",
  2: "Number",
  3: "Dropdown",
}

export default function CustomFields() {
  // State Variables for client-side pagination
  const [isShowDeleteModal, setShowDeleteModal] = useState(false)
  const [selectedCustomFieldId, setSelectedCustomFieldId] = useState<number | null>(null)

  // Modal states
  const [isCreateModalOpen, setIsCreateModalOpen] = useState(false)
  const [isEditModalOpen, setIsEditModalOpen] = useState(false)
  const [editCustomFieldId, setEditCustomFieldId] = useState<number | null>(null)

  // API Mutations - fetch all custom fields without pagination
  const { data, isLoading, error, refetch: fetchCustomFields } = useGetCustomFieldsQuery()
  const [deleteCustomField, { isLoading: isLoadingDelete }] = useDeleteCustomFieldMutation()

  // Table columns
  const columns: Column[] = [
    {
      key: "label",
      label: "Label",
      render: (row: CustomField) => <div>{row.label}</div>,
      isMain: true,
    },
    {
      key: "type",
      label: "Type",
      render: (row: CustomField) => (
        <div>
          <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
            {FIELD_TYPES[row.type as keyof typeof FIELD_TYPES] || "Unknown"}
          </span>
        </div>
      ),
      isMain: true,
    },
    {
      key: "options",
      label: "Options",
      render: (row: CustomField) => (
        <div>
          {row.type === 3 && row.options ? (
            <div className="flex flex-wrap gap-1">
              {row.options.slice(0, 3).map((option, index) => (
                <span
                  key={index}
                  className="inline-flex items-center px-2 py-1 rounded text-xs bg-gray-100 text-gray-700"
                >
                  {option}
                </span>
              ))}
              {row.options.length > 3 && (
                <span className="inline-flex items-center px-2 py-1 rounded text-xs bg-gray-100 text-gray-700">
                  +{row.options.length - 3} more
                </span>
              )}
            </div>
          ) : (
            <span className="text-gray-400">-</span>
          )}
        </div>
      ),
      isMain: true,
    },
    {
      key: "created_at",
      label: "Created At",
      render: (row: CustomField) => <div>{row.created_at ? new Date(row.created_at).toLocaleDateString() : "-"}</div>,
      isMain: false,
    },
    {
      key: "actions",
      label: "Actions",
      render: (row: CustomField) => (
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

  // Open Create Custom Field Modal
  const handleAdd = useCallback(() => {
    setIsCreateModalOpen(true)
  }, [])

  // Open Edit Custom Field Modal
  const handleEdit = useCallback((id: number) => {
    setEditCustomFieldId(id)
    setIsEditModalOpen(true)
  }, [])

  // Ask Delete
  const handleDelete = useCallback((id: number) => {
    setSelectedCustomFieldId(id)
    setShowDeleteModal(true)
  }, [])

  // Delete
  const handleConfirmDelete = async () => {
    if (selectedCustomFieldId) {
      try {
        const response = await deleteCustomField(selectedCustomFieldId).unwrap()

        if (response && response.status) {
          toast.success(response.message || "Custom field deleted successfully")
          handleCancelDelete()
          fetchCustomFields()
        } else {
          toast.error(response.message || "Error deleting custom field")
        }
      } catch (error: any) {
        toast.error(error.data?.message || "An error occurred while deleting the custom field")
      }
    }
  }

  // Cancel Delete
  const handleCancelDelete = useCallback(() => {
    setShowDeleteModal(false)
    setSelectedCustomFieldId(null)
  }, [])

  return (
    <AdminLayout>
      <div className="p-4">
        <DynamicTable
          pageTitle="Custom Fields"
          actionButton={
            <div className="flex items-center gap-3">
              <button className="relative group bg-gray-800 text-white p-2 rounded" onClick={handleAdd}>
                <Plus size={18} />
                <div className="absolute top-full right-1/2 translate-x-1/2 mt-2 w-max text-xs text-white bg-black rounded px-2 py-1 opacity-0 group-hover:opacity-100 transition">
                  Add Custom Field
                </div>
              </button>
            </div>
          }
          columns={columns}
          data={data?.data || []}
          totalCount={data?.data.length || 0}
          idField="id"
          noDataTilte="No Custom Fields Found"
          noDataDescription="No custom fields found. Create a new custom field to get started."
          error={error}
          isLoading={isLoading}
        />

        {/* Create Custom Field Modal */}
        <CreateCustomFieldModal
          isOpen={isCreateModalOpen}
          onClose={() => {
            setIsCreateModalOpen(false)
            fetchCustomFields()
          }}
        />

        {/* Edit Custom Field Modal */}
        <EditCustomFieldModal
          isOpen={isEditModalOpen}
          onClose={() => {
            setIsEditModalOpen(false)
            setEditCustomFieldId(null)
            fetchCustomFields()
          }}
          customFieldId={editCustomFieldId}
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
