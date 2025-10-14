"use client"

import { useState, useCallback, useEffect } from "react"
import { toast } from "react-toastify"
import {
  useGetApiKeysQuery,
  useDeleteApiKeyMutation,
  useRevokeApiKeyMutation,
  useActivateApiKeyMutation,
  useRegenerateApiKeyMutation,
} from "./apiKeysApi"
import { TableLoader } from "../../components/ui/loader/TableLoader"
import { TableErrorComponent } from "../../components/ui/error/TableErrorComponent"
import { TablePagination } from "../../components/ui/pagination/TablePagination"
import DeleteModal from "../../components/modal/DeleteModal"
import CreateApiKeyModal from "./CreateApiKeyModal"
import EditApiKeyModal from "./EditApiKeyModal"
import ApiKeyModal from "./ApiKeyModal"
import type { ApiKey } from "../../types/apiKeys"
import DynamicTable, { type Column } from "../../components/tables/BasicTableOne"
import IntegrationsLayout from "../../layout/IntegrationLayout"

export default function ApiKeys() {
  // State Variables
  const [page, setPage] = useState(1)
  const [pageSize, setPageSize] = useState(10)
  const [totalPages, setTotalPages] = useState(1)
  const [totalCount, setTotalCount] = useState(0)

  // Modal states
  const [isShowDeleteModal, setShowDeleteModal] = useState(false)
  const [isShowCreateModal, setShowCreateModal] = useState(false)
  const [isShowEditModal, setShowEditModal] = useState(false)
  const [isShowApiKeyModal, setShowApiKeyModal] = useState(false)
  const [selectedApiKeyId, setSelectedApiKeyId] = useState<number | null>(null)
  const [selectedApiKey, setSelectedApiKey] = useState<ApiKey | null>(null)
  const [newApiKey, setNewApiKey] = useState("")
  const [apiKeyModalTitle, setApiKeyModalTitle] = useState("")
  const [apiKeyModalMessage, setApiKeyModalMessage] = useState("")

  // API Calls
  const { data, isLoading, error, refetch } = useGetApiKeysQuery({ page, per_page: pageSize })
  const [deleteApiKey, { isLoading: isLoadingDelete }] = useDeleteApiKeyMutation()
  const [revokeApiKey, { isLoading: isLoadingRevoke }] = useRevokeApiKeyMutation()
  const [activateApiKey, { isLoading: isLoadingActivate }] = useActivateApiKeyMutation()
  const [regenerateApiKey, { isLoading: isLoadingRegenerate }] = useRegenerateApiKeyMutation()

  // Table columns
  const columns: Column[] = [
    {
      key: "name",
      label: "Name",
      render: (row: ApiKey) => <div className="font-medium">{row.name}</div>,
      isMain: true,
    },
    {
      key: "allowed_domains",
      label: "Allowed Domains",
      render: (row: ApiKey) => (
        <div className="space-y-1">
          {row.allowed_domains.map((domain, index) => (
            <span key={index} className="inline-block bg-gray-100 text-gray-800 text-xs px-2 py-1 rounded mr-1">
              {domain}
            </span>
          ))}
        </div>
      ),
      isMain: true,
    },
    {
      key: "status",
      label: "Status",
      render: (row: ApiKey) => (
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
      key: "last_used_at",
      label: "Last Used",
      render: (row: ApiKey) => (
        <div className="text-sm text-gray-500">
          {row.last_used_at ? new Date(row.last_used_at).toLocaleDateString() : "Never"}
        </div>
      ),
      isMain: false,
    },
    {
      key: "created_at",
      label: "Created At",
      render: (row: ApiKey) => (
        <div className="text-sm text-gray-500">{new Date(row.created_at).toLocaleDateString()}</div>
      ),
      isMain: false,
    },
    {
      key: "actions",
      label: "Actions",
      render: (row: ApiKey) => (
        <div className="flex items-center gap-2">
          <button
            className="px-3 py-1 bg-blue-500 text-white rounded text-sm hover:bg-blue-600"
            onClick={() => handleRegenerate(row.id)}
            disabled={isLoadingRegenerate}
          >
            Regenerate
          </button>
          {row.is_active ? (
            <button
              className="px-3 py-1 bg-orange-500 text-white rounded text-sm hover:bg-orange-600"
              onClick={() => handleRevoke(row.id)}
              disabled={isLoadingRevoke}
            >
              Revoke
            </button>
          ) : (
            <button
              className="px-3 py-1 bg-green-500 text-white rounded text-sm hover:bg-green-600"
              onClick={() => handleActivate(row.id)}
              disabled={isLoadingActivate}
            >
              Activate
            </button>
          )}
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
  const handleEdit = useCallback((apiKey: ApiKey) => {
    setSelectedApiKey(apiKey)
    setShowEditModal(true)
  }, [])

  // Handle Delete
  const handleDelete = useCallback((id: number) => {
    setSelectedApiKeyId(id)
    setShowDeleteModal(true)
  }, [])

  // Handle Revoke
  const handleRevoke = async (id: number) => {
    try {
      const response = await revokeApiKey(id).unwrap()
      if (response && response.status) {
        toast.success(response.message || "API key revoked successfully")
        refetch()
      } else {
        toast.error(response.message || "Error revoking API key")
      }
    } catch (error: any) {
      toast.error(error.data?.message || "An error occurred while revoking the API key")
    }
  }

  // Handle Activate
  const handleActivate = async (id: number) => {
    try {
      const response = await activateApiKey(id).unwrap()
      if (response && response.status) {
        toast.success(response.message || "API key activated successfully")
        refetch()
      } else {
        toast.error(response.message || "Error activating API key")
      }
    } catch (error: any) {
      toast.error(error.data?.message || "An error occurred while activating the API key")
    }
  }

  // Handle Regenerate
  const handleRegenerate = async (id: number) => {
    try {
      const response = await regenerateApiKey(id).unwrap()
      if (response && response.status && response.data.key) {
        toast.success(response.message || "API key regenerated successfully")
        setNewApiKey(response.data.key)
        setApiKeyModalTitle("API Key Regenerated")
        setApiKeyModalMessage(
          "Your API key has been regenerated. Please copy and store it securely as it won't be shown again.",
        )
        setShowApiKeyModal(true)
        refetch()
      } else {
        toast.error(response.message || "Error regenerating API key")
      }
    } catch (error: any) {
      toast.error(error.data?.message || "An error occurred while regenerating the API key")
    }
  }

  // Confirm Delete
  const handleConfirmDelete = async () => {
    if (selectedApiKeyId) {
      try {
        const response = await deleteApiKey(selectedApiKeyId).unwrap()
        if (response && response.status) {
          toast.success(response.message || "API key deleted successfully")
          setShowDeleteModal(false)
          setSelectedApiKeyId(null)
          refetch()
        } else {
          toast.error(response.message || "Error deleting API key")
        }
      } catch (error: any) {
        toast.error(error.data?.message || "An error occurred while deleting the API key")
      }
    }
  }

  // Cancel Delete
  const handleCancelDelete = useCallback(() => {
    setShowDeleteModal(false)
    setSelectedApiKeyId(null)
  }, [])

  // Handle Success callbacks
  const handleSuccess = useCallback(() => {
    refetch()
  }, [refetch])

  // Handle API Key Created
  const handleApiKeyCreated = useCallback((apiKey: string) => {
    setNewApiKey(apiKey)
    setApiKeyModalTitle("API Key Created")
    setApiKeyModalMessage(
      "Your new API key has been created. Please copy and store it securely as it won't be shown again.",
    )
    setShowApiKeyModal(true)
  }, [])

  if (isLoading) return <TableLoader />
  if (error) return <TableErrorComponent />

  const apiKeys = data?.data?.items || []

  return (
    <IntegrationsLayout>
      <div className="p-6">
        {/* API Keys Table */}
        <DynamicTable
          columns={columns}
          data={apiKeys}
          pageTitle="API Keys"
          totalCount={totalCount}
          currentPage={page}
          idField="id"
          noDataTilte="No API Keys Found"
          noDataDescription="Create your first API key to get started"
          error={error}
          isLoading={isLoading}
          actionButton={
            <div className="flex items-center gap-3">
              <button className="relative group bg-gray-800 text-white p-2 rounded" onClick={handleCreate}>
                Create API Key
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
        <CreateApiKeyModal
          isOpen={isShowCreateModal}
          onClose={() => setShowCreateModal(false)}
          onSuccess={handleSuccess}
          onApiKeyCreated={handleApiKeyCreated}
        />

        <EditApiKeyModal
          isOpen={isShowEditModal}
          onClose={() => setShowEditModal(false)}
          onApiKeyUpdated={handleSuccess}
          apiKey={selectedApiKey}
        />

        <ApiKeyModal
          isOpen={isShowApiKeyModal}
          onClose={() => setShowApiKeyModal(false)}
          apiKey={newApiKey}
          title={apiKeyModalTitle}
          message={apiKeyModalMessage}
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
