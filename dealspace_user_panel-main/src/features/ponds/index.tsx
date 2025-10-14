"use client"

import { useState, useCallback, useEffect } from "react"
import { toast } from "react-toastify"
import { useGetPondsQuery, useDeletePondMutation, useBulkDeletePondsMutation } from "./pondsApi"
import { TableLoader } from "../../components/ui/loader/TableLoader"
import { TableErrorComponent } from "../../components/ui/error/TableErrorComponent"
import { TablePagination } from "../../components/ui/pagination/TablePagination"
import DeleteModal from "../../components/modal/DeleteModal"
import BulkDeleteModal from "../../components/modal/BulkDeleteModal"
import CreatePondModal from "./CreatePondModal"
import EditPondModal from "./EditPondModal"
import ManageUsersModal from "./ManageUsersModal"
import AdminLayout from "../../layout/AdminLayout"
import type { Pond } from "../../types/ponds"
import DynamicTable, { type Column } from "../../components/tables/BasicTableOne"

export default function Ponds() {
  // State Variables
  const [page, setPage] = useState(1)
  const [pageSize, setPageSize] = useState(10)
  const [totalPages, setTotalPages] = useState(1)
  const [totalCount, setTotalCount] = useState(0)

  // Modal states
  const [isShowDeleteModal, setShowDeleteModal] = useState(false)
  const [isShowCreateModal, setShowCreateModal] = useState(false)
  const [isShowEditModal, setShowEditModal] = useState(false)
  const [isShowManageUsersModal, setShowManageUsersModal] = useState(false)

  const [selectedPondId, setSelectedPondId] = useState<number | null>(null)
  const [selectedPond, setSelectedPond] = useState<Pond | null>(null)

  // Selection state
  const [selectedIds, setSelectedIds] = useState<number[]>([])
  const [allSelected, setAllSelected] = useState(false)
  const [deselectedIds, setDeselectedIds] = useState<number[]>([])

  // Bulk delete modal
  const [isShowBulkDeleteModal, setShowBulkDeleteModal] = useState(false)

  // API Calls
  const { data, isLoading, error, refetch } = useGetPondsQuery({ page, per_page: pageSize })
  const [deletePond, { isLoading: isLoadingDelete }] = useDeletePondMutation()
  const [bulkDeletePonds, { isLoading: isLoadingBulkDelete }] = useBulkDeletePondsMutation()

  // Table columns
  const columns: Column[] = [
    {
      key: "name",
      label: "Name",
      render: (row: Pond) => <div>{row.name}</div>,
      isMain: true,
    },
    {
      key: "owner",
      label: "Owner",
      render: (row: Pond) => <div>{row.user?.name || "-"}</div>,
      isMain: true,
    },
    {
      key: "created_at",
      label: "Created At",
      render: (row: Pond) => <div>{row.created_at ? new Date(row.created_at).toLocaleDateString() : "-"}</div>,
      isMain: false,
    },
    {
      key: "actions",
      label: "Actions",
      render: (row: Pond) => (
        <div className="flex items-center gap-2">
          <button className="px-3 py-1 bg-gray-500 text-white rounded" onClick={() => handleManageUsers(row)}>
            Users
          </button>
          <button className="px-3 py-1 bg-green-500 text-white rounded" onClick={() => handleEdit(row)}>
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
  const handleEdit = useCallback((pond: Pond) => {
    setSelectedPond(pond)
    setShowEditModal(true)
  }, [])

  // Handle Delete
  const handleDelete = useCallback((id: number) => {
    setSelectedPondId(id)
    setShowDeleteModal(true)
  }, [])

  // Handle Manage Users
  const handleManageUsers = useCallback((pond: Pond) => {
    setSelectedPond(pond)
    setShowManageUsersModal(true)
  }, [])

  // Confirm Delete
  const handleConfirmDelete = async () => {
    if (selectedPondId) {
      try {
        const response = await deletePond(selectedPondId).unwrap()
        if (response && response.status) {
          toast.success(response.message || "Pond deleted successfully")
          setShowDeleteModal(false)
          setSelectedPondId(null)
          refetch()
        } else {
          toast.error(response.message || "Error deleting pond")
        }
      } catch (error: any) {
        toast.error(error.data?.message || "An error occurred while deleting the pond")
      }
    }
  }

  // Cancel Delete
  const handleCancelDelete = useCallback(() => {
    setShowDeleteModal(false)
    setSelectedPondId(null)
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
      const response = await bulkDeletePonds({
        isAllSelected: allSelected,
        ids: selectedIds,
        exceptionIds: deselectedIds,
      }).unwrap()

      if (response && response.status) {
        toast.success(response.message || `Ponds deleted successfully`)
        setShowBulkDeleteModal(false)
        setSelectedIds([])
        setAllSelected(false)
        setDeselectedIds([])
        refetch()
      } else {
        toast.error(response.message || "Error deleting ponds")
      }
    } catch (error: any) {
      toast.error(error.data?.message || "An error occurred while deleting ponds")
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

  const ponds = data?.data?.items || []

  return (
    <AdminLayout>
      <div className="p-6">
        {/* Ponds Table */}
        <DynamicTable
          columns={columns}
          data={ponds}
          pageTitle="Ponds"
          totalCount={totalCount}
          currentPage={page}
          idField="id"
          noDataTilte="No Ponds Found"
          noDataDescription="Create your first pond to get started"
          error={error}
          isLoading={isLoading}
          onSelectionChange={handleSelectionChange}
          bulkActions={bulkActions}
          actionButton={
            <div className="flex items-center gap-3">
              <button className="relative group bg-gray-800 text-white p-2 rounded" onClick={handleCreate}>
                Create
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
        <CreatePondModal
          isOpen={isShowCreateModal}
          onClose={() => setShowCreateModal(false)}
          onSuccess={handleSuccess}
        />

        <EditPondModal
          isOpen={isShowEditModal}
          onClose={() => setShowEditModal(false)}
          onPondUpdated={handleSuccess}
          pond={selectedPond}
        />

        <ManageUsersModal
          isOpen={isShowManageUsersModal}
          onClose={() => setShowManageUsersModal(false)}
          onSuccess={handleSuccess}
          pond={selectedPond}
        />

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
