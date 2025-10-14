"use client"

import { useCallback, useEffect, useState } from "react"
import { toast } from "react-toastify"
import { useNavigate } from "react-router"

// Internal Components
import { TablePagination } from "../../components/ui/pagination/TablePagination"

// CRUDS Components
import DeleteModal from "../../components/modal/DeleteModal"
import BulkDeleteModal from "../../components/modal/BulkDeleteModal"

// API Calls
import {
  useGetUsersQuery,
  useDeleteUserMutation,
  useBulkDeleteUsersMutation,
  useLazyDownloadTemplateQuery,
  useImportUsersMutation,
  useBulkExportUsersMutation,
  useGetRolesQuery,
} from "./usersApi"
import type { User } from "../../types/users"
import DynamicTable, { type Column } from "../../components/tables/BasicTableOne"

import { FileDown, FileUp, UserPlus } from "lucide-react"
import AdminLayout from "../../layout/AdminLayout"

export default function Users() {
  const router = useNavigate()

  // State Variables
  const [page, setPage] = useState(1)
  const [pageSize, setPageSize] = useState(10)
  const [totalPages, setTotalPages] = useState(1)
  const [totalCount, setTotalCount] = useState(0)
  const [isShowDeleteModal, setShowDeleteModal] = useState(false)
  const [selectedUserId, setSelectedUserId] = useState<number | null>(null)
  const [isShowImportModal, setShowImportModal] = useState(false)

  // Selection state
  const [selectedIds, setSelectedIds] = useState<number[]>([])
  const [allSelected, setAllSelected] = useState(false)
  const [deselectedIds, setDeselectedIds] = useState<number[]>([])

  // Bulk delete modal
  const [isShowBulkDeleteModal, setShowBulkDeleteModal] = useState(false)

  // API Mutations
  const { data, isLoading, error, refetch: fetchUsers } = useGetUsersQuery({ page, per_page: pageSize })
  const { data: rolesData } = useGetRolesQuery()
  const [deleteUser, { isLoading: isLoadingDelete }] = useDeleteUserMutation()
  const [bulkDeleteUsers, { isLoading: isLoadingBulkDelete }] = useBulkDeleteUsersMutation()
  const [bulkExportUsers] = useBulkExportUsersMutation()

  const [triggerDownloadTemplate] = useLazyDownloadTemplateQuery()
  const [importUsers] = useImportUsersMutation()

  // Get role name by role id
  const getRoleName = (roleId: number): string => {
    if (!rolesData?.data || roleId < 1 || roleId > rolesData.data.length) {
      return "Unknown"
    }
    return rolesData.data[roleId - 1]
  }

  // Table columns
  const columns: Column[] = [
    {
      key: "name",
      label: "Name",
      render: (row: User) => <div>{row.name}</div>,
      isMain: true,
    },
    {
      key: "email",
      label: "Email",
      render: (row: User) => <div>{row.email}</div>,
      isMain: true,
    },
    {
      key: "role",
      label: "Role",
      render: (row: User) => <div>{row.role_name}</div>,
      isMain: true,
    },
    {
      key: "created_at",
      label: "Created At",
      render: (row: User) => <div>{row.created_at ? new Date(row.created_at).toLocaleDateString() : "-"}</div>,
      isMain: false,
    },
    {
      key: "actions",
      label: "Actions",
      render: (row: User) => (
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

  useEffect(() => {
    fetchUsers()
  }, [page, pageSize])

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
    {
      label: "Export Selected",
      action: async (ids: number[]) => {
        try {
          toast.info(`Exporting ${allSelected ? totalCount - deselectedIds.length : ids.length} users...`)

          const blob = await bulkExportUsers({
            isAllSelected: allSelected,
            ids: selectedIds,
            exceptionIds: deselectedIds,
          }).unwrap()

          // Create download link
          const url = window.URL.createObjectURL(blob)
          const link = document.createElement("a")
          link.href = url
          link.setAttribute("download", `users-export-${new Date().toISOString().split("T")[0]}.xlsx`)
          document.body.appendChild(link)
          link.click()
          link.parentNode?.removeChild(link)
          window.URL.revokeObjectURL(url)

          setAllSelected(false)
          setSelectedIds([])
          setDeselectedIds([])

          toast.success("Export completed successfully")
        } catch (error: any) {
          toast.error("Failed to export data")
          console.error("Export error:", error)
        }
      },
    },
  ]

  // useEffect Hooks
  useEffect(() => {
    fetchUsers()
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [page, pageSize])

  // Update useEffect to set pagination data from the query response
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

  const handleDownloadTemplate = async () => {
    try {
      const blob = await triggerDownloadTemplate().unwrap()

      const url = window.URL.createObjectURL(blob)
      const link = document.createElement("a")
      link.href = url
      link.setAttribute("download", "users-template.xlsx")
      document.body.appendChild(link)
      link.click()
      link.parentNode?.removeChild(link)
      window.URL.revokeObjectURL(url)
    } catch (error: any) {
      toast.error("Failed to download template")
      console.error("Download error:", error)
    }
  }

  // Handle selection change
  const handleSelectionChange = (selected: number[], isAllSelected: boolean, deselected: number[]) => {
    setSelectedIds(selected)
    setAllSelected(isAllSelected)
    setDeselectedIds(deselected)
  }

  // Navigate to Add User page
  const handleAdd = useCallback(() => {
    router("/admin/users/add")
  }, [router])

  // Navigate to Edit User page
  const handleEdit = useCallback(
    (id: number) => {
      router(`/admin/users/${id}/edit`)
    },
    [router],
  )

  // Ask Delete
  const handleDelete = useCallback((id: number) => {
    setSelectedUserId(id)
    setShowDeleteModal(true)
  }, [])

  // Delete
  const handleConfirmDelete = async () => {
    if (selectedUserId) {
      try {
        const response = await deleteUser(selectedUserId).unwrap()

        if (response && response.status) {
          toast.success(response.message || "User deleted successfully")
          handleCancelDelete()
          fetchUsers()
        } else {
          toast.error(response.message || "Error deleting user")
        }
      } catch (error: any) {
        toast.error(error.data?.message || "An error occurred while deleting the user")
      }
    }
  }

  // Cancel Delete
  const handleCancelDelete = useCallback(() => {
    setShowDeleteModal(false)
    setSelectedUserId(null)
  }, [])

  // Bulk Delete
  const handleConfirmBulkDelete = async () => {
    try {
      const response = await bulkDeleteUsers({
        isAllSelected: allSelected,
        ids: selectedIds,
        exceptionIds: deselectedIds,
      }).unwrap()

      if (response && response.status) {
        toast.success(response.message || `Users deleted successfully`)
        setShowBulkDeleteModal(false)
        setSelectedIds([])
        setAllSelected(false)
        setDeselectedIds([])
        fetchUsers()
      } else {
        toast.error(response.message || "Error deleting users")
      }
    } catch (error: any) {
      toast.error(error.data?.message || "An error occurred while deleting users")
    }
  }

  // Cancel Bulk Delete
  const handleCancelBulkDelete = useCallback(() => {
    setShowBulkDeleteModal(false)
  }, [])

  return (
    <AdminLayout>
        <div className="p-4">

        <DynamicTable
          pageTitle="Users"
          actionButton={
            <div className="flex items-center gap-3">
                <button className="relative group bg-gray-800 text-white p-2 rounded" onClick={handleAdd}>
                <UserPlus size={18} />
                <div className="absolute top-full right-1/2 translate-x-1/2 mt-2 w-max text-xs text-white bg-black rounded px-2 py-1 opacity-0 group-hover:opacity-100 transition">
                    Add User
                </div>
                </button>

                <button className="relative group bg-blue-600 text-white p-2 rounded" onClick={handleDownloadTemplate}>
                <FileDown size={18} />
                <div className="absolute top-full right-1/2 translate-x-1/2 mt-2 w-max text-xs text-white bg-black rounded px-2 py-1 opacity-0 group-hover:opacity-100 transition">
                    Download Template
                </div>
                </button>

                <button
                className="relative group bg-green-600 text-white p-2 rounded"
                onClick={() => setShowImportModal(true)}
                >
                <FileUp size={18} />
                <div className="absolute top-full right-0 mt-2 w-max text-xs text-white bg-black rounded px-2 py-1 opacity-0 group-hover:opacity-100 transition">
                    Import Excel
                </div>
                </button>
            </div>
            }
            columns={columns}
            data={data?.data?.items || []}
            totalCount={totalCount}
            currentPage={page}
            onSelectionChange={handleSelectionChange}
            bulkActions={bulkActions}
            idField="id"
            noDataTilte="No Users Found"
            noDataDescription="No users found upload or insert new users"
            error={error}
            isLoading={isLoading}  
        />

        {(data?.data?.items?.length || 0) > 0 && (
            <TablePagination
            page={page}
            totalPages={totalPages}
            totalCount={totalCount}
            setPage={setPage}
            pageSize={pageSize}
            setPageSize={setPageSize}
            />
        )}

        {/* Delete Modal */}
        <DeleteModal
            isOpen={isShowDeleteModal}
            onClose={() => setShowDeleteModal(false)}
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

        {isShowImportModal && (
          <div className="fixed inset-0 bg-black bg-opacity-50 z-50 flex justify-center items-center">
            <div className="bg-white p-6 rounded-2xl shadow-2xl w-full max-w-md">
              <h2 className="text-xl font-bold mb-6 text-center">Import Users</h2>
              <form
                onSubmit={async (e) => {
                  e.preventDefault()
                  const form = e.target as HTMLFormElement
                  const fileInput = form.elements.namedItem("file") as HTMLInputElement

                  if (!fileInput?.files?.[0]) {
                    toast.error("Please select a file to upload")
                    return
                  }

                  const formData = new FormData()
                  formData.append("file", fileInput.files[0])

                  try {
                    const response = await importUsers(formData).unwrap()
                    toast.success(response.message || "Import successful")
                    setShowImportModal(false)
                    fetchUsers()
                  } catch (error: any) {
                    toast.error(error?.data?.message || "Import failed")
                  }
                }}
              >
                <input
                  type="file"
                  name="file"
                  accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel"
                  className="hidden"
                  id="file-upload"
                />

                <label
                  htmlFor="file-upload"
                  className="flex flex-col items-center justify-center w-full border-2 border-dashed border-gray-300 rounded-lg p-6 cursor-pointer hover:border-blue-400 transition-colors duration-200 mb-4"
                >
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    className="h-10 w-10 text-blue-500 mb-2"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    strokeWidth={2}
                  >
                    <path strokeLinecap="round" strokeLinejoin="round" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1M12 12V4m0 0L8 8m4-4l4 4" />
                  </svg>
                  <span className="text-gray-600">Click to upload CSV or Excel file</span>
                </label>

                <div className="flex justify-end gap-3">
                  <button
                    type="submit"
                    className="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-md transition-all"
                  >
                    Upload
                  </button>
                  <button
                    type="button"
                    className="bg-gray-200 hover:bg-gray-300 text-gray-800 px-5 py-2 rounded-md transition-all"
                    onClick={() => setShowImportModal(false)}
                  >
                    Cancel
                  </button>
                </div>
              </form>
            </div>
          </div>
        )}
        </div>
    </AdminLayout>
  )
}
