"use client"

import { useState, useCallback, useEffect } from "react"
import { toast } from "react-toastify"
import { useGetTeamsQuery, useDeleteTeamMutation, useBulkDeleteTeamsMutation } from "./teamsApi"
import { TableLoader } from "../../components/ui/loader/TableLoader"
import { TableErrorComponent } from "../../components/ui/error/TableErrorComponent" 
import { TablePagination } from "../../components/ui/pagination/TablePagination" 
import DeleteModal from "../../components/modal/DeleteModal"
import BulkDeleteModal from "../../components/modal/BulkDeleteModal"
import CreateTeamModal from "./CreateTeamModal"
import EditTeamModal from "./EditTeamModal"
import ManageTeamMembersModal from "./ManageTeamMembersModal"
import AdminLayout from "../../layout/AdminLayout"
import type { Team } from "../../types/teams" 
import DynamicTable, { type Column } from "../../components/tables/BasicTableOne"
import { Crown, Users } from "lucide-react"

export default function Teams() {
  // State Variables
  const [page, setPage] = useState(1)
  const [pageSize, setPageSize] = useState(10)
  const [totalPages, setTotalPages] = useState(1)
  const [totalCount, setTotalCount] = useState(0)

  // Modal states
  const [isShowDeleteModal, setShowDeleteModal] = useState(false)
  const [isShowCreateModal, setShowCreateModal] = useState(false)
  const [isShowEditModal, setShowEditModal] = useState(false)
  const [isShowManageMembersModal, setShowManageMembersModal] = useState(false)
  const [selectedTeamId, setSelectedTeamId] = useState<number | null>(null)
  const [selectedTeam, setSelectedTeam] = useState<Team | null>(null)

  // Selection state
  const [selectedIds, setSelectedIds] = useState<number[]>([])
  const [allSelected, setAllSelected] = useState(false)
  const [deselectedIds, setDeselectedIds] = useState<number[]>([])

  // Bulk delete modal
  const [isShowBulkDeleteModal, setShowBulkDeleteModal] = useState(false)

  // API Calls
  const { data, isLoading, error, refetch } = useGetTeamsQuery({ page, per_page: pageSize })
  const [deleteTeam, { isLoading: isLoadingDelete }] = useDeleteTeamMutation()
  const [bulkDeleteTeams, { isLoading: isLoadingBulkDelete }] = useBulkDeleteTeamsMutation()

  // Table columns
  const columns: Column[] = [
    {
      key: "name",
      label: "Team Name",
      render: (row: Team) => <div className="font-medium">{row.name}</div>,
      isMain: true,
    },
    {
      key: "members",
      label: "Members",
      render: (row: Team) => (
        <div className="flex items-center space-x-4">
          <div className="flex items-center text-sm text-gray-600">
            <Users className="h-4 w-4 mr-1" />
            {row.users?.length || 0}
          </div>
          <div className="flex items-center text-sm text-yellow-600">
            <Crown className="h-4 w-4 mr-1" />
            {row.leaders?.length || 0}
          </div>
        </div>
      ),
      isMain: true,
    },
    {
      key: "created_at",
      label: "Created At",
      render: (row: Team) => <div>{row.created_at ? new Date(row.created_at).toLocaleDateString() : "-"}</div>,
      isMain: false,
    },
    {
      key: "actions",
      label: "Actions",
      render: (row: Team) => (
        <div className="flex items-center gap-2">
          <button
            className="px-3 py-1 bg-gray-500 text-white rounded hover:bg-gray-600 transition-colors"
            onClick={() => handleManageMembers(row)}
          >
            Members
          </button>
          <button
            className="px-3 py-1 bg-green-500 text-white rounded hover:bg-green-600 transition-colors"
            onClick={() => handleEdit(row)}
          >
            Edit
          </button>
          <button
            className="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 transition-colors"
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
  const handleEdit = useCallback((team: Team) => {
    setSelectedTeam(team)
    setShowEditModal(true)
  }, [])

  // Handle Delete
  const handleDelete = useCallback((id: number) => {
    setSelectedTeamId(id)
    setShowDeleteModal(true)
  }, [])

  // Handle Manage Members
  const handleManageMembers = useCallback((team: Team) => {
    setSelectedTeam(team)
    setShowManageMembersModal(true)
  }, [])

  // Confirm Delete
  const handleConfirmDelete = async () => {
    if (selectedTeamId) {
      try {
        const response = await deleteTeam(selectedTeamId).unwrap()
        if (response && response.status) {
          toast.success(response.message || "Team deleted successfully")
          setShowDeleteModal(false)
          setSelectedTeamId(null)
          refetch()
        } else {
          toast.error(response.message || "Error deleting team")
        }
      } catch (error: any) {
        toast.error(error.data?.message || "An error occurred while deleting the team")
      }
    }
  }

  // Cancel Delete
  const handleCancelDelete = useCallback(() => {
    setShowDeleteModal(false)
    setSelectedTeamId(null)
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
      const response = await bulkDeleteTeams({
        isAllSelected: allSelected,
        ids: selectedIds,
        exceptionIds: deselectedIds,
      }).unwrap()

      if (response && response.status) {
        toast.success(response.message || `Teams deleted successfully`)
        setShowBulkDeleteModal(false)
        setSelectedIds([])
        setAllSelected(false)
        setDeselectedIds([])
        refetch()
      } else {
        toast.error(response.message || "Error deleting teams")
      }
    } catch (error: any) {
      toast.error(error.data?.message || "An error occurred while deleting teams")
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

  const teams = data?.data?.items || []

  return (
    <AdminLayout>
      <div className="p-6">
        {/* Teams Table */}
        <DynamicTable
          columns={columns}
          data={teams}
          pageTitle="Teams"
          totalCount={totalCount}
          currentPage={page}
          idField="id"
          noDataTilte="No Teams Found"
          noDataDescription="Create your first team to get started"
          error={error}
          isLoading={isLoading}
          onSelectionChange={handleSelectionChange}
          bulkActions={bulkActions}
          actionButton={
            <div className="flex items-center gap-3">
              <button
                className="relative group bg-gray-800 text-white p-2 rounded hover:bg-gray-900 transition-colors"
                onClick={handleCreate}
              >
                Create Team
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
        <CreateTeamModal
          isOpen={isShowCreateModal}
          onClose={() => setShowCreateModal(false)}
          onSuccess={handleSuccess}
        />

        <EditTeamModal
          isOpen={isShowEditModal}
          onClose={() => setShowEditModal(false)}
          onTeamUpdated={handleSuccess}
          team={selectedTeam}
        />

        <ManageTeamMembersModal
          isOpen={isShowManageMembersModal}
          onClose={() => setShowManageMembersModal(false)}
          onSuccess={handleSuccess}
          team={selectedTeam}
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
