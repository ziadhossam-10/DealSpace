"use client"

import { useState, useCallback, useEffect } from "react"
import { toast } from "react-toastify"
import { Eye, Share2, Lock, MessageSquare } from "lucide-react"
import {
  useGetTextMessageTemplatesQuery,
  useDeleteTextMessageTemplateMutation,
  useBulkDeleteTextMessageTemplatesMutation,
} from "./textMessageTemplatesApi"
import { TableLoader } from "../../components/ui/loader/TableLoader"
import { TableErrorComponent } from "../../components/ui/error/TableErrorComponent"
import { TablePagination } from "../../components/ui/pagination/TablePagination"
import DeleteModal from "../../components/modal/DeleteModal"
import BulkDeleteModal from "../../components/modal/BulkDeleteModal"
import CreateTextMessageTemplateModal from "./CreateTextMessageTemplateModal"
import EditTextMessageTemplateModal from "./EditTextMessageTemplateModal"
import AdminLayout from "../../layout/AdminLayout"
import type { TextMessageTemplate } from "../../types/textMessageTemplates"
import DynamicTable, { type Column } from "../../components/tables/BasicTableOne"

export default function TextMessageTemplates() {
  // State Variables
  const [page, setPage] = useState(1)
  const [pageSize, setPageSize] = useState(10)
  const [totalPages, setTotalPages] = useState(1)
  const [totalCount, setTotalCount] = useState(0)

  // Modal states
  const [isShowDeleteModal, setShowDeleteModal] = useState(false)
  const [isShowCreateModal, setShowCreateModal] = useState(false)
  const [isShowEditModal, setShowEditModal] = useState(false)
  const [isShowPreviewModal, setShowPreviewModal] = useState(false)
  const [selectedTemplateId, setSelectedTemplateId] = useState<number | null>(null)
  const [selectedTemplate, setSelectedTemplate] = useState<TextMessageTemplate | null>(null)

  // Selection state
  const [selectedIds, setSelectedIds] = useState<number[]>([])
  const [allSelected, setAllSelected] = useState(false)
  const [deselectedIds, setDeselectedIds] = useState<number[]>([])

  // Bulk delete modal
  const [isShowBulkDeleteModal, setShowBulkDeleteModal] = useState(false)

  // API Calls
  const { data, isLoading, error, refetch } = useGetTextMessageTemplatesQuery({ page, per_page: pageSize })
  const [deleteTextMessageTemplate, { isLoading: isLoadingDelete }] = useDeleteTextMessageTemplateMutation()
  const [bulkDeleteTextMessageTemplates, { isLoading: isLoadingBulkDelete }] =
    useBulkDeleteTextMessageTemplatesMutation()

  // Table columns
  const columns: Column[] = [
    {
      key: "name",
      label: "Message",
      render: (row: TextMessageTemplate) => (
        <div className="flex items-center space-x-2">
          <div>
            <div className="font-medium text-gray-900">{row.name}</div>
            <div className="text-sm text-gray-500 truncate max-w-xs">
              {row.message.length > 50 ? `${row.message.substring(0, 50)}...` : row.message}
            </div>
          </div>
        </div>
      ),
      isMain: true,
    },
    {
      key: "shared",
      label: "Sharing",
      render: (row: TextMessageTemplate) => (
        <div className="flex items-center space-x-1">
          {row.is_shared ? (
            <>
              <Share2 className="h-4 w-4 text-green-500" />
              <span className="text-sm text-green-600">Shared</span>
            </>
          ) : (
            <>
              <Lock className="h-4 w-4 text-gray-500" />
              <span className="text-sm text-gray-600">Private</span>
            </>
          )}
        </div>
      ),
      isMain: false,
    },
    {
      key: "length",
      label: "Length",
      render: (row: TextMessageTemplate) => <div className="text-sm text-gray-600">{row.message.length} chars</div>,
      isMain: false,
    },
    {
      key: "owner",
      label: "Owner",
      render: (row: TextMessageTemplate) => <div>{row.user?.name || "-"}</div>,
      isMain: true,
    },
    {
      key: "created_at",
      label: "Created At",
      render: (row: TextMessageTemplate) => (
        <div>{row.created_at ? new Date(row.created_at).toLocaleDateString() : "-"}</div>
      ),
      isMain: false,
    },
    {
      key: "actions",
      label: "Actions",
      render: (row: TextMessageTemplate) => (
        <div className="flex items-center gap-2">
          <button
            className="px-3 py-1 bg-green-500 text-white rounded text-sm hover:bg-green-600"
            onClick={() => handleEdit(row)}
          >
            Edit
          </button>
          <button
            className="px-3 py-1 bg-red-500 text-white rounded text-sm hover:bg-red-600"
            onClick={() => handleDelete(row.id)}
          >
            Delete
          </button>
        </div>
      ),
      isMain: true,
    },
  ]

  // Bulk actions
  const bulkActions = [
    {
      label: "Delete Selected",
      action: (ids: number[]) => {
        if (ids.length > 0) {
          setShowBulkDeleteModal(true)
        }
      },
    },
  ]

  // Update pagination data from the query response
  useEffect(() => {
    if (data?.data) {
      if (data.data.meta.last_page) {
        setTotalPages(data.data.meta.last_page)
      }
      if (data.data.meta.total) {
        setTotalCount(data.data.meta.total)
      }
    }
  }, [data])

  // Handle Create
  const handleCreate = useCallback(() => {
    setShowCreateModal(true)
  }, [])

  // Handle Edit
  const handleEdit = useCallback((template: TextMessageTemplate) => {
    setSelectedTemplate(template)
    setShowEditModal(true)
  }, [])

  // Handle Delete
  const handleDelete = useCallback((id: number) => {
    setSelectedTemplateId(id)
    setShowDeleteModal(true)
  }, [])

  // Handle Preview
  const handlePreview = useCallback((template: TextMessageTemplate) => {
    setSelectedTemplate(template)
    setShowPreviewModal(true)
  }, [])

  // Confirm Delete
  const handleConfirmDelete = async () => {
    if (selectedTemplateId) {
      try {
        const response = await deleteTextMessageTemplate(selectedTemplateId).unwrap()
        if (response && response.status) {
          toast.success(response.message || "Text message template deleted successfully")
          setShowDeleteModal(false)
          setSelectedTemplateId(null)
          refetch()
        } else {
          toast.error(response.message || "Error deleting text message template")
        }
      } catch (error: any) {
        toast.error(error.data?.message || "An error occurred while deleting the text message template")
      }
    }
  }

  // Cancel Delete
  const handleCancelDelete = useCallback(() => {
    setShowDeleteModal(false)
    setSelectedTemplateId(null)
  }, [])

  // Handle selection change
  const handleSelectionChange = useCallback((selected: number[], isAllSelected: boolean, deselected: number[]) => {
    setSelectedIds(selected)
    setAllSelected(isAllSelected)
    setDeselectedIds(deselected)
  }, [])

  // Bulk Delete
  const handleConfirmBulkDelete = async () => {
    try {
      const response = await bulkDeleteTextMessageTemplates({
        isAllSelected: allSelected,
        ids: selectedIds,
        exceptionIds: deselectedIds,
      }).unwrap()

      if (response && response.status) {
        toast.success(response.message || `Text message templates deleted successfully`)
        setShowBulkDeleteModal(false)
        setSelectedIds([])
        setAllSelected(false)
        setDeselectedIds([])
        refetch()
      } else {
        toast.error(response.message || "Error deleting text message templates")
      }
    } catch (error: any) {
      toast.error(error.data?.message || "An error occurred while deleting text message templates")
    }
  }

  // Cancel Bulk Delete
  const handleCancelBulkDelete = useCallback(() => {
    setShowBulkDeleteModal(false)
  }, [])

  // Handle Success callbacks
  const handleSuccess = useCallback(() => {
    refetch()
  }, [refetch])

  if (isLoading) return <TableLoader />
  if (error) return <TableErrorComponent />

  const templates = data?.data?.items || []

  return (
    <AdminLayout>
      <div className="p-6">
        {/* Text Message Templates Table */}
        <DynamicTable
          columns={columns}
          data={templates}
          pageTitle="Text Message Templates"
          totalCount={totalCount}
          currentPage={page}
          idField="id"
          noDataTilte="No Text Message Templates Found"
          noDataDescription="Create your first text message template to get started"
          error={error}
          isLoading={isLoading}
          onSelectionChange={handleSelectionChange}
          bulkActions={bulkActions}
          actionButton={
            <div className="flex items-center gap-3">
              <button
                className="relative group bg-gray-800 text-white p-2 rounded hover:bg-gray-700"
                onClick={handleCreate}
              >
                Create Template
              </button>
            </div>
          }
        />

        {/* Pagination */}
        {totalPages > 1 && (
          <div className="mt-6">
            <TablePagination
              page={page}
              totalPages={totalPages}
              totalCount={totalCount}
              setPage={setPage}
              pageSize={pageSize}
              setPageSize={setPageSize}
            />
          </div>
        )}

        {/* Modals */}
        <CreateTextMessageTemplateModal
          isOpen={isShowCreateModal}
          onClose={() => setShowCreateModal(false)}
          onSuccess={handleSuccess}
        />

        <EditTextMessageTemplateModal
          isOpen={isShowEditModal}
          onClose={() => setShowEditModal(false)}
          onTemplateUpdated={handleSuccess}
          template={selectedTemplate}
        />

        {/* Preview Modal */}
        {isShowPreviewModal && selectedTemplate && (
          <div className="fixed inset-0 bg-black bg-opacity-50 z-50 flex justify-center items-center p-4">
            <div className="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-hidden">
              <div className="flex justify-between items-center p-6 border-b border-gray-200">
                <h2 className="text-xl font-semibold text-gray-900">Preview: {selectedTemplate.name}</h2>
                <button
                  onClick={() => setShowPreviewModal(false)}
                  className="p-1 rounded-md hover:bg-gray-100 transition-colors"
                >
                  <Eye className="h-4 w-4 text-gray-500" />
                </button>
              </div>
              <div className="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
                <div className="space-y-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Template Name:</label>
                    <div className="p-3 bg-gray-50 rounded-md">{selectedTemplate.name}</div>
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Message:</label>
                    <div className="p-4 bg-gray-50 rounded-md">
                      <div className="whitespace-pre-wrap text-gray-900">{selectedTemplate.message}</div>
                      <div className="mt-2 text-xs text-gray-500">
                        Character count: {selectedTemplate.message.length}
                      </div>
                    </div>
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Sharing:</label>
                    <div className="p-3 bg-gray-50 rounded-md flex items-center space-x-2">
                      {selectedTemplate.is_shared ? (
                        <>
                          <Share2 className="h-4 w-4 text-green-500" />
                          <span className="text-green-600">Shared with all users</span>
                        </>
                      ) : (
                        <>
                          <Lock className="h-4 w-4 text-gray-500" />
                          <span className="text-gray-600">Private template</span>
                        </>
                      )}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        )}

        <DeleteModal
          isOpen={isShowDeleteModal}
          onClose={handleCancelDelete}
          onDelete={handleConfirmDelete}
          isLoadingDelete={isLoadingDelete}
        />

        {/* Bulk Delete Modal */}
        <BulkDeleteModal
          isOpen={isShowBulkDeleteModal}
          onClose={handleCancelBulkDelete}
          onDelete={handleConfirmBulkDelete}
          isLoadingDelete={isLoadingBulkDelete}
          count={allSelected ? totalCount - deselectedIds.length : selectedIds.length}
        />
      </div>
    </AdminLayout>
  )
}
