"use client"

import { useState, useCallback, useEffect } from "react"
import { toast } from "react-toastify"
import { Plus } from "lucide-react"
import { useNavigate } from "react-router"
import { useGetGroupsQuery, useDeleteGroupMutation } from "./groupsApi"
import { TableLoader } from "../../components/ui/loader/TableLoader"
import { TableErrorComponent } from "../../components/ui/error/TableErrorComponent"
import { TablePagination } from "../../components/ui/pagination/TablePagination"
import DeleteModal from "../../components/modal/DeleteModal"
import GroupCard from "./GroupCard"
import ManageUsersModal from "./ManageUsersModal"
import AdminLayout from "../../layout/AdminLayout"
import type { Group } from "../../types/groups"

export default function Groups() {
  const navigate = useNavigate()

  // State Variables
  const [page, setPage] = useState(1)
  const [pageSize, setPageSize] = useState(12) // More items per page for cards
  const [totalPages, setTotalPages] = useState(1)
  const [totalCount, setTotalCount] = useState(0)

  // Modal states (only for delete and manage users)
  const [isShowDeleteModal, setShowDeleteModal] = useState(false)
  const [isShowManageUsersModal, setShowManageUsersModal] = useState(false)

  const [selectedGroupId, setSelectedGroupId] = useState<number | null>(null)
  const [selectedGroup, setSelectedGroup] = useState<Group | null>(null)

  // API Calls
  const { data, isLoading, error, refetch } = useGetGroupsQuery({ page, per_page: pageSize })
  const [deleteGroup, { isLoading: isLoadingDelete }] = useDeleteGroupMutation()

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

  // Handle Create - Navigate to create page
  const handleCreate = useCallback(() => {
    navigate("/admin/groups/create")
  }, [navigate])

  // Handle Edit - Navigate to edit page
  const handleEdit = useCallback(
    (group: Group) => {
      navigate(`/admin/groups/edit/${group.id}`)
    },
    [navigate],
  )

  // Handle Delete
  const handleDelete = useCallback((id: number) => {
    setSelectedGroupId(id)
    setShowDeleteModal(true)
  }, [])

  // Handle Manage Users
  const handleManageUsers = useCallback((group: Group) => {
    setSelectedGroup(group)
    setShowManageUsersModal(true)
  }, [])

  // Confirm Delete
  const handleConfirmDelete = async () => {
    if (selectedGroupId) {
      try {
        const response = await deleteGroup(selectedGroupId).unwrap()
        if (response && response.status) {
          toast.success(response.message || "Group deleted successfully")
          setShowDeleteModal(false)
          setSelectedGroupId(null)
          refetch()
        } else {
          toast.error(response.message || "Error deleting group")
        }
      } catch (error: any) {
        toast.error(error.data?.message || "An error occurred while deleting the group")
      }
    }
  }

  // Cancel Delete
  const handleCancelDelete = useCallback(() => {
    setShowDeleteModal(false)
    setSelectedGroupId(null)
  }, [])

  // Handle Success callbacks
  const handleSuccess = useCallback(() => {
    refetch()
  }, [refetch])

  if (isLoading) return <TableLoader />
  if (error) return <TableErrorComponent />

  const groups = data?.data?.items || []

  return (
    <AdminLayout>
      <div className="p-6">
        {/* Header */}
        <div className="flex justify-between items-center mb-6">
          <div>
            <h1 className="text-2xl font-bold text-gray-900">Groups</h1>
            <p className="text-gray-600">Manage your groups and their members</p>
          </div>
          <button
            onClick={handleCreate}
            className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 flex items-center"
          >
            <Plus className="mr-2 h-4 w-4" />
            Create Group
          </button>
        </div>

        {/* Groups Grid */}
        {groups.length === 0 ? (
          <div className="text-center py-12">
            <div className="text-gray-500 text-lg mb-4">No groups found</div>
            <p className="text-gray-400 mb-6">Create your first group to get started</p>
          </div>
        ) : (
          <>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
              {groups.map((group: Group) => (
                <GroupCard
                  key={group.id}
                  group={group}
                  onEdit={handleEdit}
                  onDelete={handleDelete}
                  onManageUsers={handleManageUsers}
                />
              ))}
            </div>

            {/* Pagination */}
            {totalPages > 1 && (
              <div className="mt-8">
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
          </>
        )}

        {/* Remaining Modals (only delete and manage users) */}
        <ManageUsersModal
          isOpen={isShowManageUsersModal}
          onClose={() => setShowManageUsersModal(false)}
          onSuccess={handleSuccess}
          group={selectedGroup}
        />

        <DeleteModal
          isOpen={isShowDeleteModal}
          onClose={handleCancelDelete}
          onDelete={handleConfirmDelete}
          isLoadingDelete={isLoadingDelete}
        />
      </div>
    </AdminLayout>
  )
}
