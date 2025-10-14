"use client"

import type React from "react"

import { useState, useEffect } from "react"
import { toast } from "react-toastify"
import { X, Plus } from "lucide-react"
import { useUpdatePondMutation, useGetPondByIdQuery } from "./pondsApi"
import type { Pond } from "../../types/ponds"
import { useGetUsersQuery } from "../users/usersApi"
import type { User } from "../../types/users"

interface ManageUsersModalProps {
  isOpen: boolean
  onClose: () => void
  onSuccess: () => void
  pond: Pond | null
}

interface SelectedUser {
  id: number
  name: string
  email: string
}

export default function ManageUsersModal({ isOpen, onClose, onSuccess, pond }: ManageUsersModalProps) {
  const [updatePond, { isLoading: isUpdating }] = useUpdatePondMutation()

  // Fetch complete pond data with users
  const { data: pondData, isLoading: isLoadingPond } = useGetPondByIdQuery(pond?.id || 0, {
    skip: !isOpen || !pond,
  })

  const [searchTerm, setSearchTerm] = useState("")
  const [showUserSearch, setShowUserSearch] = useState(false)
  const [usersToAdd, setUsersToAdd] = useState<SelectedUser[]>([])
  const [usersToDelete, setUsersToDelete] = useState<number[]>([])
  const [currentUsers, setCurrentUsers] = useState<User[]>([])

  const { data: usersData, isLoading: isLoadingUsers } = useGetUsersQuery(
    { search: searchTerm, page: 1, per_page: 100 },
    { skip: !showUserSearch || !pond },
  )

  useEffect(() => {
    if (isOpen && pondData?.data) {
      setCurrentUsers(pondData.data.users || [])
      setUsersToAdd([])
      setUsersToDelete([])
      setSearchTerm("")
      setShowUserSearch(false)
    }
  }, [isOpen, pondData])

  const handleUserSelect = (user: any, checked: boolean) => {
    if (checked) {
      const newUser = { id: user.id, name: user.name, email: user.email }
      setUsersToAdd((prev) => [...prev, newUser])
    } else {
      setUsersToAdd((prev) => prev.filter((u) => u.id !== user.id))
    }
  }

  const handleRemoveNewUser = (userId: number) => {
    setUsersToAdd((prev) => prev.filter((u) => u.id !== userId))
  }

  const handleDeleteExistingUser = (userId: number) => {
    if (usersToDelete.includes(userId)) {
      setUsersToDelete((prev) => prev.filter((id) => id !== userId))
    } else {
      setUsersToDelete((prev) => [...prev, userId])
    }
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()

    if (!pond) return

    try {
      const updateData: any = {}

      if (usersToAdd.length > 0) {
        updateData.user_ids = usersToAdd.map((u) => u.id)
      }

      if (usersToDelete.length > 0) {
        updateData.user_ids_to_delete = usersToDelete
      }

      if (Object.keys(updateData).length > 0) {
        await updatePond({ id: pond.id, ...updateData }).unwrap()
        toast.success("Users updated successfully!")
      }

      onSuccess()
      onClose()
    } catch (error: any) {
      console.error("Failed to update users:", error)
      toast.error(error.data?.message || "Failed to update users. Please try again.")
    }
  }

  if (!isOpen || !pond) return null

  // Show loading state while fetching pond details
  if (isLoadingPond) {
    return (
      <div className="fixed inset-0 bg-black bg-opacity-50 z-50 flex justify-center items-center p-4">
        <div className="bg-white rounded-lg shadow-xl w-full max-w-3xl p-8">
          <div className="text-center">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-4"></div>
            <p className="text-gray-600">Loading pond details...</p>
          </div>
        </div>
      </div>
    )
  }

  const availableUsers =
    usersData?.data?.items?.filter(
      (user: any) => !currentUsers.some((cu) => cu.id === user.id) && !usersToAdd.some((ua) => ua.id === user.id),
    ) || []

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 z-50 flex justify-center items-center p-4">
      <div className="bg-white rounded-lg shadow-xl w-full max-w-3xl max-h-[90vh] overflow-hidden">
        <div className="flex justify-between items-center p-6 border-b border-gray-200">
          <h2 className="text-xl font-semibold text-gray-900">Manage Users - {pond.name}</h2>
          <button onClick={onClose} className="p-1 rounded-md hover:bg-gray-100 transition-colors">
            <X className="h-4 w-4 text-gray-500" />
          </button>
        </div>

        <form onSubmit={handleSubmit} className="p-6 space-y-6 overflow-y-auto max-h-[calc(90vh-120px)]">
          {/* Current Users */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Current Users ({currentUsers.filter((u) => !usersToDelete.includes(u.id)).length})
            </label>
            <div className="space-y-2 max-h-60 overflow-y-auto border border-gray-200 rounded-md p-2">
              {currentUsers.map((user) => (
                <div
                  key={user.id}
                  className={`flex items-center justify-between p-2 rounded-md ${
                    usersToDelete.includes(user.id) ? "bg-red-50 opacity-50" : "bg-gray-50"
                  }`}
                >
                  <div>
                    <div className="font-medium text-gray-900">{user.name}</div>
                    <div className="text-sm text-gray-500">{user.email}</div>
                  </div>
                  <button
                    type="button"
                    onClick={() => handleDeleteExistingUser(user.id)}
                    className={`p-1 rounded-md transition-colors ${
                      usersToDelete.includes(user.id)
                        ? "text-green-600 hover:bg-green-100"
                        : "text-red-600 hover:bg-red-100"
                    }`}
                  >
                    {usersToDelete.includes(user.id) ? "Undo" : <X className="h-4 w-4" />}
                  </button>
                </div>
              ))}
              {currentUsers.length === 0 && <div className="text-center py-4 text-gray-500">No users in this pond</div>}
            </div>
          </div>

          {/* Add New Users */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">Add New Users</label>
            {!showUserSearch ? (
              <button
                type="button"
                onClick={() => setShowUserSearch(true)}
                className="w-full px-4 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 flex items-center justify-center"
              >
                <Plus className="mr-2 h-4 w-4" />
                Search and Add Users
              </button>
            ) : (
              <div className="space-y-4">
                <input
                  type="text"
                  placeholder="Search users..."
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                />

                {isLoadingUsers ? (
                  <div className="text-center py-4 text-gray-500">Loading users...</div>
                ) : (
                  <div className="max-h-40 overflow-y-auto border border-gray-200 rounded-md p-2">
                    {availableUsers.map((user: any) => (
                      <div key={user.id} className="flex items-center space-x-2 py-2">
                        <input
                          type="checkbox"
                          checked={usersToAdd.some((u) => u.id === user.id)}
                          onChange={(e) => handleUserSelect(user, e.target.checked)}
                          className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        />
                        <div className="flex-1">
                          <div className="font-medium text-gray-900">{user.name}</div>
                          <div className="text-sm text-gray-500">{user.email}</div>
                        </div>
                      </div>
                    ))}
                    {availableUsers.length === 0 && (
                      <div className="text-center py-4 text-gray-500">No available users found</div>
                    )}
                  </div>
                )}
              </div>
            )}
          </div>

          {/* Users to Add */}
          {usersToAdd.length > 0 && (
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">Users to Add ({usersToAdd.length})</label>
              <div className="space-y-2 max-h-40 overflow-y-auto border border-gray-200 rounded-md p-2">
                {usersToAdd.map((user) => (
                  <div key={user.id} className="flex items-center justify-between p-2 bg-green-50 rounded-md">
                    <div>
                      <div className="font-medium text-gray-900">{user.name}</div>
                      <div className="text-sm text-gray-500">{user.email}</div>
                    </div>
                    <button
                      type="button"
                      onClick={() => handleRemoveNewUser(user.id)}
                      className="p-1 rounded-md hover:bg-green-200 transition-colors"
                    >
                      <X className="h-4 w-4 text-gray-500" />
                    </button>
                  </div>
                ))}
              </div>
            </div>
          )}

          {/* Form Actions */}
          <div className="flex justify-end space-x-3 pt-4 border-t border-gray-200">
            <button
              type="button"
              onClick={onClose}
              className="px-4 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={isUpdating || (usersToAdd.length === 0 && usersToDelete.length === 0)}
              className={`px-4 py-2 border border-transparent rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 ${
                isUpdating || (usersToAdd.length === 0 && usersToDelete.length === 0)
                  ? "opacity-75 cursor-not-allowed"
                  : ""
              }`}
            >
              {isUpdating ? "Updating..." : "Update Users"}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}
