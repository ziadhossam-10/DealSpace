"use client"

import type React from "react"
import { useState, useEffect } from "react"
import { X, Plus } from "lucide-react"
import { useUpdateDealMutation, useGetUsersQuery, useGetPeopleQuery, useDeleteAttachmentMutation } from "./dealsApi"
import type { Deal, UpdateDealRequest, User, Person } from "../../types/deals"
import FileUpload from "./FileUpload"
import { Modal } from "../../components/modal"
import { toast } from "react-toastify"

interface EditDealModalProps {
  isOpen: boolean
  onClose: () => void
  onSuccess: () => void
  deal: Deal | null
}

interface SelectedUser {
  id: number
  name: string
  email: string
}

interface SelectedPerson {
  id: number
  name: string
  first_name: string
  last_name: string
}

export default function EditDealModal({ isOpen, onClose, onSuccess, deal }: EditDealModalProps) {
  const [updateDeal, { isLoading }] = useUpdateDealMutation()
  const [deleteAttachment] = useDeleteAttachmentMutation()

  const [formData, setFormData] = useState<UpdateDealRequest>({
    name: "",
    description: "",
    price: 0,
    projected_close_date: "",
    commission_value: 0,
    agent_commission: 0,
    team_commission: 0,
  })

  const [selectedUsers, setSelectedUsers] = useState<SelectedUser[]>([])
  const [selectedPeople, setSelectedPeople] = useState<SelectedPerson[]>([])
  const [attachments, setAttachments] = useState<File[]>([])
  const [attachmentsToDelete, setAttachmentsToDelete] = useState<number[]>([])

  // Search states
  const [userSearchTerm, setUserSearchTerm] = useState("")
  const [peopleSearchTerm, setPeopleSearchTerm] = useState("")
  const [showUserSearch, setShowUserSearch] = useState(false)
  const [showPeopleSearch, setShowPeopleSearch] = useState(false)

  // API queries
  const { data: usersData, isLoading: isLoadingUsers } = useGetUsersQuery(
    { search: userSearchTerm, page: 1, per_page: 100 },
    { skip: !showUserSearch },
  )

  const { data: peopleData, isLoading: isLoadingPeople } = useGetPeopleQuery(
    { search: peopleSearchTerm, page: 1, per_page: 100 },
    { skip: !showPeopleSearch },
  )

  // Initialize form data when deal changes
  useEffect(() => {
    if (deal && isOpen) {
      setFormData({
        name: deal.name,
        description: deal.description,
        price: deal.price,
        projected_close_date: deal.projected_close_date,
        commission_value: deal.commission_value,
        agent_commission: deal.agent_commission,
        team_commission: deal.team_commission,
      })

      // Set selected users
      setSelectedUsers(
        deal.users.map((user) => ({
          id: user.id,
          name: user.name,
          email: user.email,
        })),
      )

      // Set selected people
      setSelectedPeople(
        deal.people.map((person) => ({
          id: person.id,
          name: person.name,
          first_name: person.first_name,
          last_name: person.last_name,
        })),
      )

      // Reset attachment states
      setAttachments([])
      setAttachmentsToDelete([])

      // Reset search states
      setUserSearchTerm("")
      setPeopleSearchTerm("")
      setShowUserSearch(false)
      setShowPeopleSearch(false)
    }
  }, [deal, isOpen])

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    const { name, value } = e.target
    setFormData((prev) => ({
      ...prev,
      [name]:
        name === "price" || name === "commission_value" || name === "agent_commission" || name === "team_commission"
          ? Number(value)
          : value,
    }))
  }

  const handleUserSelect = (user: User, checked: boolean) => {
    if (checked) {
      const newUser = { id: user.id, name: user.name, email: user.email }
      setSelectedUsers((prev) => [...prev, newUser])
    } else {
      setSelectedUsers((prev) => prev.filter((u) => u.id !== user.id))
    }
  }

  const handlePersonSelect = (person: Person, checked: boolean) => {
    if (checked) {
      const newPerson = {
        id: person.id,
        name: person.name,
        first_name: person.first_name,
        last_name: person.last_name,
      }
      setSelectedPeople((prev) => [...prev, newPerson])
    } else {
      setSelectedPeople((prev) => prev.filter((p) => p.id !== person.id))
    }
  }

  const handleUserRemove = (userId: number) => {
    setSelectedUsers((prev) => prev.filter((u) => u.id !== userId))
  }

  const handlePersonRemove = (personId: number) => {
    setSelectedPeople((prev) => prev.filter((p) => p.id !== personId))
  }

  const handleAttachmentDelete = async (attachmentId: number) => {
    setAttachmentsToDelete((prev) => [...prev, attachmentId])
  }

  const handleAttachmentDownload = (attachmentId: number) => {
    // Create a temporary link to download the file
    const link = document.createElement("a")
    link.href = `/api/deal-attachments/${attachmentId}/download`
    link.download = ""
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!deal) return

    try {
      const submitData = {
        ...formData,
        // Ensure these are always arrays, even if empty
        users_ids: selectedUsers.map((u) => u.id),
        people_ids: selectedPeople.map((p) => p.id),
        attachments: attachments.length > 0 ? attachments : undefined,
        attachments_to_delete: attachmentsToDelete.length > 0 ? attachmentsToDelete : undefined,
      }

      console.log("Submitting data:", {
        ...submitData,
        attachments: submitData.attachments ? `${submitData.attachments.length} files` : "none",
      }) // Debug log

      const response = await updateDeal({ id: deal.id, ...submitData }).unwrap()
      if (response.status) {
        onSuccess()
        toast.success("Deal updated successfully")
        onClose()
      }
    } catch (error) {
      console.error("Error updating deal:", error)
    }
  }

  // Close search dropdowns when clicking outside
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (showUserSearch && !(event.target as Element).closest(".user-search-container")) {
        setShowUserSearch(false)
      }
      if (showPeopleSearch && !(event.target as Element).closest(".people-search-container")) {
        setShowPeopleSearch(false)
      }
    }

    document.addEventListener("mousedown", handleClickOutside)
    return () => document.removeEventListener("mousedown", handleClickOutside)
  }, [showUserSearch, showPeopleSearch])

  if (!isOpen || !deal) return null

  return (
    <Modal isOpen={isOpen} onClose={onClose} className="max-w-4xl">
      <div>
        <div className="flex justify-between items-center p-6 border-b border-gray-200">
          <h2 className="text-xl font-semibold text-gray-900">Edit Deal</h2>
        </div>

        <form onSubmit={handleSubmit} className="p-6 space-y-6 overflow-y-auto">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            {/* Left Column - Basic Info */}
            <div className="space-y-6">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Deal Name *</label>
                <input
                  type="text"
                  name="name"
                  value={formData.name}
                  onChange={handleChange}
                  required
                  className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea
                  name="description"
                  value={formData.description}
                  onChange={handleChange}
                  rows={3}
                  className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Price *</label>
                <input
                  type="number"
                  name="price"
                  value={formData.price}
                  onChange={handleChange}
                  required
                  min="0"
                  step="0.01"
                  className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Projected Close Date</label>
                <input
                  type="date"
                  name="projected_close_date"
                  value={formData.projected_close_date}
                  onChange={handleChange}
                  className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>

              <div className="grid grid-cols-1 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Commission Value</label>
                  <input
                    type="number"
                    name="commission_value"
                    value={formData.commission_value}
                    onChange={handleChange}
                    min="0"
                    step="0.01"
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Agent Commission</label>
                  <input
                    type="number"
                    name="agent_commission"
                    value={formData.agent_commission}
                    onChange={handleChange}
                    min="0"
                    step="0.01"
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Team Commission</label>
                  <input
                    type="number"
                    name="team_commission"
                    value={formData.team_commission}
                    onChange={handleChange}
                    min="0"
                    step="0.01"
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>
              </div>
            </div>

            {/* Right Column - Users and People */}
            <div className="space-y-6">
              {/* Users Selection */}
              <div className="user-search-container">
                <label className="block text-sm font-medium text-gray-700 mb-1">Users</label>
                {!showUserSearch ? (
                  <button
                    type="button"
                    onClick={() => setShowUserSearch(true)}
                    className="w-full px-4 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 flex items-center justify-center"
                  >
                    <Plus className="mr-2 h-4 w-4" />
                    Manage Users
                  </button>
                ) : (
                  <div className="space-y-4">
                    <input
                      type="text"
                      placeholder="Search users..."
                      value={userSearchTerm}
                      onChange={(e) => setUserSearchTerm(e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                    {isLoadingUsers ? (
                      <div className="text-center py-4 text-gray-500">Loading users...</div>
                    ) : (
                      <div className="max-h-40 overflow-y-auto border border-gray-200 rounded-md p-2">
                        {usersData?.data?.items?.map((user: User) => (
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
                  <div className="space-y-2 max-h-32 overflow-y-auto">
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

              {/* People Selection */}
              <div className="people-search-container">
                <label className="block text-sm font-medium text-gray-700 mb-1">People</label>
                {!showPeopleSearch ? (
                  <button
                    type="button"
                    onClick={() => setShowPeopleSearch(true)}
                    className="w-full px-4 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 flex items-center justify-center"
                  >
                    <Plus className="mr-2 h-4 w-4" />
                    Manage People
                  </button>
                ) : (
                  <div className="space-y-4">
                    <input
                      type="text"
                      placeholder="Search people..."
                      value={peopleSearchTerm}
                      onChange={(e) => setPeopleSearchTerm(e.target.value)}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                    {isLoadingPeople ? (
                      <div className="text-center py-4 text-gray-500">Loading people...</div>
                    ) : (
                      <div className="max-h-40 overflow-y-auto border border-gray-200 rounded-md p-2">
                        {peopleData?.data?.items?.map((person: Person) => (
                          <div key={person.id} className="flex items-center space-x-2 py-2">
                            <input
                              type="checkbox"
                              checked={selectedPeople.some((p) => p.id === person.id)}
                              onChange={(e) => handlePersonSelect(person, e.target.checked)}
                              className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            />
                            <div className="flex-1">
                              <div className="font-medium text-gray-900">{person.name}</div>
                              <div className="text-sm text-gray-500">{person.emails?.[0]?.value || "No email"}</div>
                            </div>
                          </div>
                        ))}
                        {peopleData?.data?.items?.length === 0 && (
                          <div className="text-center py-4 text-gray-500">No people found</div>
                        )}
                      </div>
                    )}
                  </div>
                )}
              </div>

              {/* Selected People */}
              {selectedPeople.length > 0 && (
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Selected People ({selectedPeople.length})
                  </label>
                  <div className="space-y-2 max-h-32 overflow-y-auto">
                    {selectedPeople.map((person) => (
                      <div key={person.id} className="flex items-center justify-between p-2 bg-blue-50 rounded-md">
                        <div>
                          <div className="font-medium text-gray-900">{person.name}</div>
                          <div className="text-sm text-gray-500">
                            {person.first_name} {person.last_name}
                          </div>
                        </div>
                        <button
                          type="button"
                          onClick={() => handlePersonRemove(person.id)}
                          className="p-1 rounded-md hover:bg-blue-200 transition-colors"
                        >
                          <X className="h-4 w-4 text-gray-500" />
                        </button>
                      </div>
                    ))}
                  </div>
                </div>
              )}

              {/* File Attachments */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Attachments</label>
                <FileUpload
                  files={attachments}
                  existingAttachments={deal?.attachments?.filter((att) => !attachmentsToDelete.includes(att.id)) || []}
                  onFilesChange={setAttachments}
                  onAttachmentDelete={handleAttachmentDelete}
                  onAttachmentDownload={handleAttachmentDownload}
                  maxFiles={10}
                  maxFileSize={10}
                />
              </div>
            </div>
          </div>

          <div className="flex justify-end space-x-3 pt-4 border-t border-gray-200">
            <button
              type="button"
              onClick={onClose}
              className="px-4 py-2 text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={isLoading}
              className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
            >
              {isLoading ? "Updating..." : "Update Deal"}
            </button>
          </div>
        </form>
      </div>
    </Modal>
  )
}
