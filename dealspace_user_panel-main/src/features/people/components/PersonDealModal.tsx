"use client"

import type React from "react"
import { useState, useEffect } from "react"
import { X, Plus, Download, Trash2 } from "lucide-react"

// Import correct types from deals module
import type { Deal, DealAttachment, DealStage, User } from "../../../types/deals"
import type { Person } from "../../../types/people"
import { Modal } from "../../../components/modal"

interface PersonDealModalProps {
  isOpen: boolean
  onClose: () => void
  onSubmit: (data: any) => Promise<void>
  deal?: Deal | null
  personId: number
  dealStages: DealStage[]
  users: User[]
  people: Person[]
  isLoading?: boolean
  isLoadingStages?: boolean
  isLoadingUsers?: boolean
  isLoadingPeople?: boolean
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

interface Attachment {
  id: number
  name: string
  size: number
  type: string
  path: string
}

export const PersonDealModal = ({
  isOpen,
  onClose,
  onSubmit,
  deal,
  personId,
  dealStages,
  users,
  people,
  isLoading = false,
  isLoadingStages = false,
  isLoadingUsers = false,
  isLoadingPeople = false,
}: PersonDealModalProps) => {
  const [formData, setFormData] = useState({
    name: "",
    description: "",
    price: 0,
    projected_close_date: "",
    commission_value: 0,
    agent_commission: 0,
    team_commission: 0,
    stage_id: 0,
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

  useEffect(() => {
    if (isOpen) {
      if (deal) {
        // Edit mode
        setFormData({
          name: deal.name,
          description: deal.description || "",
          price: deal.price,
          projected_close_date: deal.projected_close_date || "",
          commission_value: deal.commission_value || 0,
          agent_commission: deal.agent_commission || 0,
          team_commission: deal.team_commission || 0,
          stage_id: deal.stage_id,
        })

        // Set selected users from deal
        if (deal.users) {
          setSelectedUsers(
            deal.users.map((user) => ({
              id: user.id,
              name: user.name,
              email: user.email,
            })),
          )
        }

        // Set selected people from deal
        if (deal.people) {
          setSelectedPeople(
            deal.people.map((person) => ({
              id: person.id,
              name: person.name,
              first_name: person.first_name,
              last_name: person.last_name,
            })),
          )
        }
      } else {
        // Create mode - include current person by default
        const currentPerson = people.find((p) => p.id === personId)
        setFormData({
          name: "",
          description: "",
          price: 0,
          projected_close_date: "",
          commission_value: 0,
          agent_commission: 0,
          team_commission: 0,
          stage_id: dealStages[0]?.id || 0,
        })

        if (currentPerson) {
          setSelectedPeople([
            {
              id: currentPerson.id,
              name: currentPerson.name,
              first_name: currentPerson.first_name,
              last_name: currentPerson.last_name,
            },
          ])
        }
      }

      // Reset attachment and search states
      setAttachments([])
      setAttachmentsToDelete([])
      setUserSearchTerm("")
      setPeopleSearchTerm("")
      setShowUserSearch(false)
      setShowPeopleSearch(false)
    }
  }, [isOpen, deal, personId, people, dealStages])

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => {
    const { name, value } = e.target
    setFormData((prev) => ({
      ...prev,
      [name]:
        name === "price" ||
        name === "commission_value" ||
        name === "agent_commission" ||
        name === "team_commission" ||
        name === "stage_id"
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

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files) {
      const newFiles = Array.from(e.target.files)
      setAttachments((prev) => [...prev, ...newFiles])
    }
  }

  const handleAttachmentRemove = (index: number) => {
    setAttachments((prev) => prev.filter((_, i) => i !== index))
  }

  const handleExistingAttachmentDelete = (attachmentId: number) => {
    setAttachmentsToDelete((prev) => [...prev, attachmentId])
  }

  const handleAttachmentDownload = (attachment: DealAttachment) => {
    const link = document.createElement("a")
    link.href = `/api/deal-attachments/${attachment.id}/download`
    link.download = attachment.name
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  }

  const formatFileSize = (bytes: number): string => {
    if (bytes === 0) return "0 Bytes"
    const k = 1024
    const sizes = ["Bytes", "KB", "MB", "GB"]
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    return Number.parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i]
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()

    const submitData = {
      ...formData,
      people_ids: selectedPeople.map((p) => p.id),
      users_ids: selectedUsers.map((u) => u.id),
      attachments: attachments.length > 0 ? attachments : undefined,
      attachments_to_delete: attachmentsToDelete.length > 0 ? attachmentsToDelete : undefined,
    }

    try {
      await onSubmit(submitData)
      onClose()
    } catch (error) {
      console.error("Error submitting deal:", error)
    }
  }

  const filteredUsers = users.filter(
    (user) =>
      user.name.toLowerCase().includes(userSearchTerm.toLowerCase()) ||
      user.email.toLowerCase().includes(userSearchTerm.toLowerCase()),
  )

  const filteredPeople = people.filter(
    (person) =>
      person.name.toLowerCase().includes(peopleSearchTerm.toLowerCase()) ||
      person.first_name.toLowerCase().includes(peopleSearchTerm.toLowerCase()) ||
      person.last_name.toLowerCase().includes(peopleSearchTerm.toLowerCase()),
  )

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

  if (!isOpen) return null

  return (
    <>
      <Modal isOpen={isOpen} onClose={onClose} className="max-w-4xl w-full">
        <div>
          <div className="flex justify-between items-center p-6 border-b border-gray-200">
            <h2 className="text-xl font-semibold text-gray-900">{deal ? "Edit Deal" : "Create New Deal"}</h2>
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

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Stage *</label>
                  <select
                    name="stage_id"
                    value={formData.stage_id}
                    onChange={handleChange}
                    required
                    disabled={isLoadingStages}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100"
                  >
                    <option value={0}>Select Stage</option>
                    {dealStages.map((stage) => (
                      <option key={stage.id} value={stage.id}>
                        {stage.name}
                      </option>
                    ))}
                  </select>
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

              {/* Right Column - Users, People, and Attachments */}
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
                          {filteredUsers.map((user) => (
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
                          {filteredUsers.length === 0 && (
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
                          {filteredPeople.map((person) => (
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
                          {filteredPeople.length === 0 && (
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

                  {/* File Upload */}
                  <div className="mb-4">
                    <input
                      type="file"
                      multiple
                      onChange={handleFileChange}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>

                  {/* New Attachments */}
                  {attachments.length > 0 && (
                    <div className="mb-4">
                      <h4 className="text-sm font-medium text-gray-700 mb-2">New Files</h4>
                      <div className="space-y-2">
                        {attachments.map((file, index) => (
                          <div key={index} className="flex items-center justify-between p-2 bg-green-50 rounded-md">
                            <div className="flex-1">
                              <div className="text-sm font-medium text-gray-900">{file.name}</div>
                              <div className="text-xs text-gray-500">{formatFileSize(file.size)}</div>
                            </div>
                            <button
                              type="button"
                              onClick={() => handleAttachmentRemove(index)}
                              className="p-1 text-red-600 hover:text-red-800 hover:bg-red-100 rounded transition-colors"
                            >
                              <Trash2 className="w-4 h-4" />
                            </button>
                          </div>
                        ))}
                      </div>
                    </div>
                  )}

                  {/* Existing Attachments */}
                  {deal?.attachments && deal.attachments.length > 0 && (
                    <div>
                      <h4 className="text-sm font-medium text-gray-700 mb-2">Existing Files</h4>
                      <div className="space-y-2">
                        {deal.attachments
                          .filter((att) => !attachmentsToDelete.includes(att.id))
                          .map((attachment) => (
                            <div
                              key={attachment.id}
                              className="flex items-center justify-between p-2 bg-gray-50 rounded-md"
                            >
                              <div className="flex-1">
                                <div className="text-sm font-medium text-gray-900">{attachment.name}</div>
                                <div className="text-xs text-gray-500">{formatFileSize(attachment.size)}</div>
                              </div>
                              <div className="flex items-center space-x-1">
                                <button
                                  type="button"
                                  onClick={() => handleAttachmentDownload(attachment)}
                                  className="p-1 text-blue-600 hover:text-blue-800 hover:bg-blue-100 rounded transition-colors"
                                >
                                  <Download className="w-4 h-4" />
                                </button>
                                <button
                                  type="button"
                                  onClick={() => handleExistingAttachmentDelete(attachment.id)}
                                  className="p-1 text-red-600 hover:text-red-800 hover:bg-red-100 rounded transition-colors"
                                >
                                  <Trash2 className="w-4 h-4" />
                                </button>
                              </div>
                            </div>
                          ))}
                      </div>
                    </div>
                  )}
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
                {isLoading ? "Saving..." : deal ? "Update Deal" : "Create Deal"}
              </button>
            </div>
          </form>
        </div>
      </Modal>
    </>
  )
}
