"use client"

import { useState, useCallback, useEffect } from "react"
import { toast } from "react-toastify"
import {
  useGetTrackingScriptsQuery,
  useDeleteTrackingScriptMutation,
  useToggleTrackingScriptStatusMutation,
  useRegenerateScriptKeyMutation,
} from "./trackingScriptsApi"
import { TableLoader } from "../../components/ui/loader/TableLoader"
import { TableErrorComponent } from "../../components/ui/error/TableErrorComponent"
import { TablePagination } from "../../components/ui/pagination/TablePagination"
import DeleteModal from "../../components/modal/DeleteModal"
import CreateTrackingScriptModal from "./CreateTrackingScriptModal"
import EditTrackingScriptModal from "./EditTrackingScriptModal"
import TrackingCodeModal from "./TrackingCodeModal"
import type { TrackingScript } from "../../types/trackingScripts"
import DynamicTable, { type Column } from "../../components/tables/BasicTableOne"
import IntegrationsLayout from "../../layout/IntegrationLayout"

export default function TrackingScripts() {
  // State Variables
  const [page, setPage] = useState(1)
  const [pageSize, setPageSize] = useState(10)
  const [totalPages, setTotalPages] = useState(1)
  const [totalCount, setTotalCount] = useState(0)

  // Modal states
  const [isShowDeleteModal, setShowDeleteModal] = useState(false)
  const [isShowCreateModal, setShowCreateModal] = useState(false)
  const [isShowEditModal, setShowEditModal] = useState(false)
  const [isShowTrackingCodeModal, setShowTrackingCodeModal] = useState(false)
  const [selectedScriptId, setSelectedScriptId] = useState<number | null>(null)
  const [selectedScriptIdForEdit, setSelectedScriptIdForEdit] = useState<number | null>(null)
  const [selectedScriptIdForCode, setSelectedScriptIdForCode] = useState<number | null>(null)

  // API Calls
  const { data, isLoading, error, refetch } = useGetTrackingScriptsQuery({
    page,
    per_page: pageSize,
  })
  const [deleteScript, { isLoading: isLoadingDelete }] = useDeleteTrackingScriptMutation()
  const [toggleStatus, { isLoading: isLoadingToggle }] = useToggleTrackingScriptStatusMutation()
  const [regenerateKey, { isLoading: isLoadingRegenerate }] = useRegenerateScriptKeyMutation()

  // Table columns
  const columns: Column[] = [
    {
      key: "name",
      label: "Name",
      render: (row: TrackingScript) => (
        <div>
          <div className="font-medium">{row.name}</div>
          <div className="text-sm text-gray-500 truncate max-w-xs">{row.description}</div>
        </div>
      ),
      isMain: true,
    },
    {
      key: "domain",
      label: "Domains",
      render: (row: TrackingScript) => (
        <div className="space-y-1">
          {row.domain && row.domain.length > 0 ? (
            <>
              {row.domain.slice(0, 2).map((domain, index) => (
                <span key={index} className="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded mr-1">
                  {domain}
                </span>
              ))}
              {row.domain.length > 2 && <span className="text-xs text-gray-500">+{row.domain.length - 2} more</span>}
            </>
          ) : (
            <span className="inline-block bg-green-100 text-green-800 text-xs px-2 py-1 rounded">No Restrictions</span>
          )}
        </div>
      ),
      isMain: true,
    },
    {
      key: "status",
      label: "Status",
      render: (row: TrackingScript) => (
        <span
          className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
            row.is_active ? "bg-green-100 text-green-800" : "bg-red-100 text-red-800"
          }`}
        >
          {row.is_active ? "Active" : "Inactive"}
        </span>
      ),
      isMain: true,
    },
    {
      key: "features",
      label: "Features",
      render: (row: TrackingScript) => (
        <div className="space-y-1">
          <div className="flex flex-wrap gap-1">
            {row.track_page_views && (
              <span className="bg-gray-100 text-gray-700 text-xs px-2 py-1 rounded">Page Views</span>
            )}
            {row.auto_lead_capture && (
              <span className="bg-gray-100 text-gray-700 text-xs px-2 py-1 rounded">Lead Capture</span>
            )}
            {row.track_utm_parameters && (
              <span className="bg-gray-100 text-gray-700 text-xs px-2 py-1 rounded">UTM Tracking</span>
            )}
          </div>
          <div className="text-xs text-gray-500">
            {row.custom_events_count || 0} events • {row.form_selectors_count || 0} forms
          </div>
        </div>
      ),
      isMain: false,
    },
    {
      key: "script_key",
      label: "Script Key",
      render: (row: TrackingScript) => (
        <div className="font-mono text-xs bg-gray-50 px-2 py-1 rounded truncate max-w-32">{row.script_key}</div>
      ),
      isMain: false,
    },
    {
      key: "created_at",
      label: "Created At",
      render: (row: TrackingScript) => (
        <div className="text-sm text-gray-500">{new Date(row.created_at).toLocaleDateString()}</div>
      ),
      isMain: false,
    },
    {
      key: "actions",
      label: "Actions",
      render: (row: TrackingScript) => (
        <div className="flex items-center gap-2">
          <button
            className="px-3 py-1 bg-purple-500 text-white rounded text-sm hover:bg-purple-600"
            onClick={() => handleViewCode(row)}
          >
            View Code
          </button>
          <button
            className="px-3 py-1 bg-blue-500 text-white rounded text-sm hover:bg-blue-600"
            onClick={() => handleRegenerate(row.id)}
            disabled={isLoadingRegenerate}
          >
            Regenerate
          </button>
          <button
            className={`px-3 py-1 text-white rounded text-sm ${
              row.is_active ? "bg-orange-500 hover:bg-orange-600" : "bg-green-500 hover:bg-green-600"
            }`}
            onClick={() => handleToggleStatus(row.id)}
            disabled={isLoadingToggle}
          >
            {row.is_active ? "Deactivate" : "Activate"}
          </button>
          <button
            className="px-3 py-1 bg-gray-500 text-white rounded text-sm hover:bg-gray-600"
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
  const handleEdit = useCallback((script: TrackingScript) => {
    setSelectedScriptIdForEdit(script.id)
    setShowEditModal(true)
  }, [])

  // Handle Delete
  const handleDelete = useCallback((id: number) => {
    setSelectedScriptId(id)
    setShowDeleteModal(true)
  }, [])

  // Handle View Code
  const handleViewCode = useCallback((script: TrackingScript) => {
    setSelectedScriptIdForCode(script.id)
    setShowTrackingCodeModal(true)
  }, [])

  // Handle Toggle Status
  const handleToggleStatus = async (id: number) => {
    try {
      const response = await toggleStatus(id).unwrap()
      if (response && response.status) {
        toast.success(response.message || "Script status updated successfully")
        refetch()
      } else {
        toast.error(response.message || "Error updating script status")
      }
    } catch (error: any) {
      toast.error(error.data?.message || "An error occurred while updating the script status")
    }
  }

  // Handle Regenerate
  const handleRegenerate = async (id: number) => {
    try {
      const response = await regenerateKey(id).unwrap()
      if (response && response.status) {
        toast.success(response.message || "Script key regenerated successfully")
        refetch()
      } else {
        toast.error(response.message || "Error regenerating script key")
      }
    } catch (error: any) {
      toast.error(error.data?.message || "An error occurred while regenerating the script key")
    }
  }

  // Confirm Delete
  const handleConfirmDelete = async () => {
    if (selectedScriptId) {
      try {
        const response = await deleteScript(selectedScriptId).unwrap()
        if (response && response.status) {
          toast.success(response.message || "Tracking script deleted successfully")
          setShowDeleteModal(false)
          setSelectedScriptId(null)
          refetch()
        } else {
          toast.error(response.message || "Error deleting tracking script")
        }
      } catch (error: any) {
        toast.error(error.data?.message || "An error occurred while deleting the tracking script")
      }
    }
  }

  // Cancel Delete
  const handleCancelDelete = useCallback(() => {
    setShowDeleteModal(false)
    setSelectedScriptId(null)
  }, [])

  // Handle Success callbacks
  const handleSuccess = useCallback(() => {
    refetch()
  }, [refetch])

  if (isLoading) return <TableLoader />
  if (error) return <TableErrorComponent />

  const trackingScripts = data?.data?.items || []

  return (
    <IntegrationsLayout>
      <div className="p-6">
        {/* Tracking Scripts Table */}
        <DynamicTable
          columns={columns}
          data={trackingScripts}
          pageTitle="Tracking Scripts"
          totalCount={totalCount}
          currentPage={page}
          idField="id"
          noDataTilte="No Tracking Scripts Found"
          noDataDescription="Create your first tracking script to start monitoring your website"
          error={error}
          isLoading={isLoading}
          actionButton={
            <div className="flex items-center gap-3">
              <button className="relative group bg-gray-800 text-white p-2 rounded" onClick={handleCreate}>
                Create Tracking Script
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
        <CreateTrackingScriptModal
          isOpen={isShowCreateModal}
          onClose={() => setShowCreateModal(false)}
          onSuccess={handleSuccess}
        />

        <EditTrackingScriptModal
          isOpen={isShowEditModal}
          onClose={() => {
            setShowEditModal(false)
            setSelectedScriptIdForEdit(null)
          }}
          onScriptUpdated={handleSuccess}
          scriptId={selectedScriptIdForEdit}
        />

        <TrackingCodeModal
          isOpen={isShowTrackingCodeModal}
          onClose={() => {
            setShowTrackingCodeModal(false)
            setSelectedScriptIdForCode(null)
          }}
          scriptId={selectedScriptIdForCode}
        />

        <DeleteModal
          isOpen={isShowDeleteModal}
          onClose={handleCancelDelete}
          onDelete={handleConfirmDelete}
          isLoadingDelete={isLoadingDelete}
        />
      </div>
    </IntegrationsLayout>
  )
}
