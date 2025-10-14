"use client"

import type React from "react"

import { useState, useEffect } from "react"
import { toast } from "react-toastify"
import { X, Plus } from "lucide-react"
import { useCreatePondMutation } from "./pondsApi"
import type { CreatePondRequest } from "../../types/ponds"
import { useGetUsersQuery } from "../users/usersApi"

interface CreatePondModalProps {
  isOpen: boolean
  onClose: () => void
  onSuccess: () => void
}

interface SelectedUser {
  id: number
  name: string
  email: string
}

export default function CreatePondModal({ isOpen, onClose, onSuccess }: CreatePondModalProps) {
  const [createPond, { isLoading }] = useCreatePondMutation()

  const [formData, setFormData] = useState<CreatePondRequest>({
    name: "",
    user_id: 0,
    user_ids: [],
  })

  const [selectedUsers, setSelectedUsers] = useState<SelectedUser[]>([])
  const [searchTerm, setSearchTerm] = useState("")
  const [showUserSearch, setShowUserSearch] = useState(false)

  const [ownerSearchTerm, setOwnerSearchTerm] = useState("")
  const [showOwnerSearch, setShowOwnerSearch] = useState(false)
  const [selectedOwner, setSelectedOwner] = useState<{ id: number; name: string; email: string } | null>(null)

  // Add this new query for owner selection (fetch all users)
  const { data: allUsersData, isLoading: isLoadingAllUsers } = useGetUsersQuery(
    { page: 1, per_page: 1000 }, // Fetch a large number to get all users
    { skip: !isOpen },
  )

  // Keep the existing query for user search functionality
  const { data: usersData, isLoading: isLoadingUsers } = useGetUsersQuery(
    { search: searchTerm, page: 1, per_page: 100 },
    { skip: !showUserSearch },
  )

  const { data: ownerSearchData, isLoading: isLoadingOwnerSearch } = useGetUsersQuery(
    { search: ownerSearchTerm, page: 1, per_page: 50 },
    { skip: !showOwnerSearch },
  )

  const [errors, setErrors] = useState({
    name: "",
    user_id: "",
    users: "",
  })

  useEffect(() => {
    if (!isOpen) {
      setFormData({
        name: "",
        user_id: 0,
        user_ids: [],
      })
      setSelectedUsers([])
      setSearchTerm("")
      setShowUserSearch(false)
      setSelectedOwner(null)
      setOwnerSearchTerm("")
      setShowOwnerSearch(false)
      setErrors({ name: "", user_id: "", users: "" })
    }
  }, [isOpen])

  const handleChange = (field: string, value: any) => {
    setFormData((prev) => ({
      ...prev,
      [field]: value,
    }))

    if (field in errors) {
      setErrors((prev) => ({ ...prev, [field]: "" }))
    }
  }

  const handleUserSelect = (user: any, checked: boolean) => {
    if (checked) {
      const newUser = { id: user.id, name: user.name, email: user.email }
      setSelectedUsers((prev) => [...prev, newUser])
    } else {
      setSelectedUsers((prev) => prev.filter((u) => u.id !== user.id))
    }
  }

  const handleUserRemove = (userId: number) => {
    setSelectedUsers((prev) => prev.filter((u) => u.id !== userId))
  }

  const validateForm = (): boolean => {
    let isValid = true
    const newErrors = { name: "", user_id: "", users: "" }

    if (!formData.name.trim()) {
      newErrors.name = "Pond name is required"
      isValid = false
    }

    if (!formData.user_id) {
      newErrors.user_id = "Owner is required"
      isValid = false
    }

    setErrors(newErrors)
    return isValid
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()

    if (!validateForm()) {
      return
    }

    try {
      const submitData = {
        ...formData,
        user_ids: selectedUsers.map((u) => u.id),
      }

      await createPond(submitData).unwrap()
      toast.success("Pond created successfully!")
      onSuccess()
      onClose()
    } catch (error: any) {
      console.error("Failed to create pond:", error)
      toast.error(error.data?.message || "Failed to create pond. Please try again.")
    }
  }

  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (showOwnerSearch && !(event.target as Element).closest(".owner-search-container")) {
        setShowOwnerSearch(false)
      }
    }

    document.addEventListener("mousedown", handleClickOutside)
    return () => document.removeEventListener("mousedown", handleClickOutside)
  }, [showOwnerSearch])

  if (!isOpen) return null

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 z-50 flex justify-center items-center p-4">
      <div className="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-hidden">
        <div className="flex justify-between items-center p-6 border-b border-gray-200">
          <h2 className="text-xl font-semibold text-gray-900">Create New Pond</h2>
          <button onClick={onClose} className="p-1 rounded-md hover:bg-gray-100 transition-colors">
            <X className="h-4 w-4 text-gray-500" />
          </button>
        </div>

        <form onSubmit={handleSubmit} className="p-6 space-y-6 overflow-y-auto max-h-[calc(90vh-120px)]">
          {/* Pond Name */}
          <div>
            <label htmlFor="name" className="block text-sm font-medium text-gray-700 mb-1">
              Pond Name*
            </label>
            <input
              id="name"
              type="text"
              value={formData.name}
              onChange={(e) => handleChange("name", e.target.value)}
              className={`w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 ${
                errors.name ? "border-red-500" : "border-gray-300"
              }`}
            />
            {errors.name && <p className="mt-1 text-sm text-red-500">{errors.name}</p>}
          </div>

          {/* Owner */}
          <div className="owner-search-container">
            <label className="block text-sm font-medium text-gray-700 mb-1">Owner*</label>
            {!selectedOwner ? (
              <div className="relative">
                <input
                  type="text"
                  placeholder="Search and select owner..."
                  value={ownerSearchTerm}
                  onChange={(e) => {
                    setOwnerSearchTerm(e.target.value)
                    setShowOwnerSearch(true)
                  }}
                  onFocus={() => setShowOwnerSearch(true)}
                  className={`w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 ${
                    errors.user_id ? "border-red-500" : "border-gray-300"
                  }`}
                />

                {showOwnerSearch && (
                  <div className="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto">
                    {isLoadingOwnerSearch ? (
                      <div className="p-3 text-center text-gray-500">Loading...</div>
                    ) : ownerSearchData?.data?.items?.length ? (
                      ownerSearchData.data.items.map((user: any) => (
                        <button
                          key={user.id}
                          type="button"
                          onClick={() => {
                            setSelectedOwner({ id: user.id, name: user.name, email: user.email })
                            handleChange("user_id", user.id)
                            setShowOwnerSearch(false)
                            setOwnerSearchTerm("")
                          }}
                          className="w-full text-left px-3 py-2 hover:bg-gray-100 focus:bg-gray-100 focus:outline-none"
                        >
                          <div className="font-medium text-gray-900">{user.name}</div>
                          <div className="text-sm text-gray-500">{user.email}</div>
                        </button>
                      ))
                    ) : (
                      <div className="p-3 text-center text-gray-500">No users found</div>
                    )}
                  </div>
                )}
              </div>
            ) : (
              <div className="flex items-center justify-between p-3 bg-gray-50 border border-gray-300 rounded-md">
                <div>
                  <div className="font-medium text-gray-900">{selectedOwner.name}</div>
                  <div className="text-sm text-gray-500">{selectedOwner.email}</div>
                </div>
                <button
                  type="button"
                  onClick={() => {
                    setSelectedOwner(null)
                    handleChange("user_id", 0)
                  }}
                  className="p-1 rounded-md hover:bg-gray-200 transition-colors"
                >
                  <X className="h-4 w-4 text-gray-500" />
                </button>
              </div>
            )}
            {errors.user_id && <p className="mt-1 text-sm text-red-500">{errors.user_id}</p>}
          </div>

          {/* Users Selection */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Users</label>
            {!showUserSearch ? (
              <button
                type="button"
                onClick={() => setShowUserSearch(true)}
                className="w-full px-4 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 flex items-center justify-center"
              >
                <Plus className="mr-2 h-4 w-4" />
                Add Users
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
                    {usersData?.data?.items?.map((user: any) => (
                      <div key={user.id} className="flex items-center space-x-2 py-2">
                        <input
                          type="checkbox"
                          checked={selectedUsers.some((u) => u.id === user.id)}
                          onChange={(e) => handleUserSelect(user, e.target.checked)}
                          className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        />
                        <div className="flex-1">
                          <div className="font-medium text-gray-900">{user.name}</div>
                          <div className="text-sm text-gray-500">{user.email}</div>
                        </div>
                      </div>
                    ))}
                    {usersData?.data?.items?.length === 0 && (
                      <div className="text-center py-4 text-gray-500">No users found</div>
                    )}
                  </div>
                )}
              </div>
            )}
          </div>

          {/* Selected Users */}
          {selectedUsers.length > 0 && (
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Selected Users ({selectedUsers.length})
              </label>
              <div className="space-y-2 max-h-40 overflow-y-auto">
                {selectedUsers.map((user) => (
                  <div key={user.id} className="flex items-center justify-between p-2 bg-gray-50 rounded-md">
                    <div>
                      <div className="font-medium text-gray-900">{user.name}</div>
                      <div className="text-sm text-gray-500">{user.email}</div>
                    </div>
                    <button
                      type="button"
                      onClick={() => handleUserRemove(user.id)}
                      className="p-1 rounded-md hover:bg-gray-200 transition-colors"
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
              disabled={isLoading}
              className={`px-4 py-2 border border-transparent rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 ${
                isLoading ? "opacity-75 cursor-not-allowed" : ""
              }`}
            >
              {isLoading ? "Creating..." : "Create Pond"}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}
