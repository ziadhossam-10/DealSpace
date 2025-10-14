"use client"

import type React from "react"
import { useState, useEffect } from "react"
import { toast } from "react-toastify"
import { X, Plus } from "lucide-react"
import { useCreateTeamMutation } from "./teamsApi"
import type { CreateTeamRequest } from "../../types/teams"
import { useGetUsersQuery } from "../users/usersApi"

interface CreateTeamModalProps {
  isOpen: boolean
  onClose: () => void
  onSuccess: () => void
}

interface SelectedUser {
  id: number
  name: string
  email: string
}

export default function CreateTeamModal({ isOpen, onClose, onSuccess }: CreateTeamModalProps) {
  const [createTeam, { isLoading }] = useCreateTeamMutation()
  const [formData, setFormData] = useState<CreateTeamRequest>({
    name: "",
    userIds: [],
    leaderIds: [],
  })
  const [selectedUsers, setSelectedUsers] = useState<SelectedUser[]>([])
  const [selectedLeaders, setSelectedLeaders] = useState<SelectedUser[]>([])
  const [searchTerm, setSearchTerm] = useState("")
  const [leaderSearchTerm, setLeaderSearchTerm] = useState("")
  const [showUserSearch, setShowUserSearch] = useState(false)
  const [showLeaderSearch, setShowLeaderSearch] = useState(false)

  const { data: usersData, isLoading: isLoadingUsers } = useGetUsersQuery(
    { search: searchTerm, page: 1, per_page: 100 },
    { skip: !showUserSearch },
  )

  const { data: leadersData, isLoading: isLoadingLeaders } = useGetUsersQuery(
    { search: leaderSearchTerm, page: 1, per_page: 100 },
    { skip: !showLeaderSearch },
  )

  const [errors, setErrors] = useState({
    name: "",
  })

  useEffect(() => {
    if (!isOpen) {
      setFormData({
        name: "",
        userIds: [],
        leaderIds: [],
      })
      setSelectedUsers([])
      setSelectedLeaders([])
      setSearchTerm("")
      setLeaderSearchTerm("")
      setShowUserSearch(false)
      setShowLeaderSearch(false)
      setErrors({ name: "" })
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
      // Remove from leaders if being added as member
      setSelectedLeaders((prev) => prev.filter((u) => u.id !== user.id))
      setSelectedUsers((prev) => [...prev, newUser])
    } else {
      setSelectedUsers((prev) => prev.filter((u) => u.id !== user.id))
    }
  }

  const handleLeaderSelect = (user: any, checked: boolean) => {
    if (checked) {
      const newLeader = { id: user.id, name: user.name, email: user.email }
      // Remove from members if being added as leader
      setSelectedUsers((prev) => prev.filter((u) => u.id !== user.id))
      setSelectedLeaders((prev) => [...prev, newLeader])
    } else {
      setSelectedLeaders((prev) => prev.filter((u) => u.id !== user.id))
    }
  }

  const handleUserRemove = (userId: number) => {
    setSelectedUsers((prev) => prev.filter((u) => u.id !== userId))
  }

  const handleLeaderRemove = (userId: number) => {
    setSelectedLeaders((prev) => prev.filter((u) => u.id !== userId))
  }

  const validateForm = (): boolean => {
    let isValid = true
    const newErrors = { name: "" }

    if (!formData.name.trim()) {
      newErrors.name = "Team name is required"
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
        userIds: selectedUsers.map((u) => u.id),
        leaderIds: selectedLeaders.map((u) => u.id),
      }
      await createTeam(submitData).unwrap()
      toast.success("Team created successfully!")
      onSuccess()
      onClose()
    } catch (error: any) {
      console.error("Failed to create team:", error)
      toast.error(error.data?.message || "Failed to create team. Please try again.")
    }
  }

  if (!isOpen) return null

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 z-50 flex justify-center items-center p-4">
      <div className="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-hidden">
        <div className="flex justify-between items-center p-6 border-b border-gray-200">
          <h2 className="text-xl font-semibold text-gray-900">Create New Team</h2>
          <button onClick={onClose} className="p-1 rounded-md hover:bg-gray-100 transition-colors">
            <X className="h-4 w-4 text-gray-500" />
          </button>
        </div>

        <form onSubmit={handleSubmit} className="p-6 space-y-6 overflow-y-auto max-h-[calc(90vh-120px)]">
          {/* Team Name */}
          <div>
            <label htmlFor="name" className="block text-sm font-medium text-gray-700 mb-1">
              Team Name*
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

          {/* Users Selection */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Team Members</label>
            {!showUserSearch ? (
              <button
                type="button"
                onClick={() => setShowUserSearch(true)}
                className="w-full px-4 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 flex items-center justify-center"
              >
                <Plus className="mr-2 h-4 w-4" />
                Add Team Members
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
                    {usersData?.data?.items?.map((user: any) => {
                      const isSelectedAsMember = selectedUsers.some((u) => u.id === user.id)
                      const isSelectedAsLeader = selectedLeaders.some((u) => u.id === user.id)

                      return (
                        <div key={user.id} className="flex items-center space-x-2 py-2">
                          <input
                            type="checkbox"
                            checked={isSelectedAsMember}
                            onChange={(e) => handleUserSelect(user, e.target.checked)}
                            className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                          />
                          <div className="flex-1">
                            <div className="font-medium text-gray-900 flex items-center">
                              {user.name}
                              {isSelectedAsLeader && (
                                <span className="ml-2 px-2 py-1 text-xs bg-yellow-100 text-yellow-800 rounded-full">
                                  Selected as Leader
                                </span>
                              )}
                            </div>
                            <div className="text-sm text-gray-500">{user.email}</div>
                          </div>
                        </div>
                      )
                    })}
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
                Selected Members ({selectedUsers.length})
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

          {/* Leaders Selection */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Team Leaders</label>
            {!showLeaderSearch ? (
              <button
                type="button"
                onClick={() => setShowLeaderSearch(true)}
                className="w-full px-4 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 flex items-center justify-center"
              >
                <Plus className="mr-2 h-4 w-4" />
                Add Team Leaders
              </button>
            ) : (
              <div className="space-y-4">
                <input
                  type="text"
                  placeholder="Search leaders..."
                  value={leaderSearchTerm}
                  onChange={(e) => setLeaderSearchTerm(e.target.value)}
                  className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
                {isLoadingLeaders ? (
                  <div className="text-center py-4 text-gray-500">Loading users...</div>
                ) : (
                  <div className="max-h-40 overflow-y-auto border border-gray-200 rounded-md p-2">
                    {leadersData?.data?.items?.map((user: any) => {
                      const isSelectedAsMember = selectedUsers.some((u) => u.id === user.id)
                      const isSelectedAsLeader = selectedLeaders.some((u) => u.id === user.id)

                      return (
                        <div key={user.id} className="flex items-center space-x-2 py-2">
                          <input
                            type="checkbox"
                            checked={isSelectedAsLeader}
                            onChange={(e) => handleLeaderSelect(user, e.target.checked)}
                            className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                          />
                          <div className="flex-1">
                            <div className="font-medium text-gray-900 flex items-center">
                              {user.name}
                              {isSelectedAsMember && (
                                <span className="ml-2 px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full">
                                  Selected as Member
                                </span>
                              )}
                            </div>
                            <div className="text-sm text-gray-500">{user.email}</div>
                          </div>
                        </div>
                      )
                    })}
                    {leadersData?.data?.items?.length === 0 && (
                      <div className="text-center py-4 text-gray-500">No users found</div>
                    )}
                  </div>
                )}
              </div>
            )}
          </div>

          {/* Selected Leaders */}
          {selectedLeaders.length > 0 && (
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Selected Leaders ({selectedLeaders.length})
              </label>
              <div className="space-y-2 max-h-40 overflow-y-auto">
                {selectedLeaders.map((user) => (
                  <div key={user.id} className="flex items-center justify-between p-2 bg-blue-50 rounded-md">
                    <div>
                      <div className="font-medium text-gray-900">{user.name}</div>
                      <div className="text-sm text-gray-500">{user.email}</div>
                    </div>
                    <button
                      type="button"
                      onClick={() => handleLeaderRemove(user.id)}
                      className="p-1 rounded-md hover:bg-blue-200 transition-colors"
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
              {isLoading ? "Creating..." : "Create Team"}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}
