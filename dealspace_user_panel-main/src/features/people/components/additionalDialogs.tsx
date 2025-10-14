"use client"

import type React from "react"
import { useState, useEffect } from "react"
import { X } from "lucide-react"
import { Modal } from "../../../components/modal"
import { ASSETS_URL, getInitials } from "../../../utils/helpers"

// Custom Fields dialog component
export const CustomFieldsDialog = ({
  isOpen,
  onClose,
  customFieldsWithValues,
  onSubmit,
}: {
  isOpen: boolean
  onClose: () => void
  customFieldsWithValues: any[]
  onSubmit: (data: Array<{ id: number; value: string }>) => void
}) => {
  const [fieldValues, setFieldValues] = useState<{ [key: number]: string }>({})

  useEffect(() => {
    if (isOpen && customFieldsWithValues) {
      const initialValues: { [key: number]: string } = {}
      customFieldsWithValues.forEach((field) => {
        initialValues[field.id] = field.value || ""
      })
      setFieldValues(initialValues)
    }
  }, [isOpen, customFieldsWithValues])

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    const customFieldsArray = Object.entries(fieldValues).map(([id, value]) => ({
      id: Number(id),
      value: value,
    }))
    onSubmit(customFieldsArray)
    onClose()
  }

  const handleFieldChange = (fieldId: number, value: string) => {
    setFieldValues((prev) => ({
      ...prev,
      [fieldId]: value,
    }))
  }

  const renderFieldInput = (field: any) => {
    const value = fieldValues[field.id] || ""

    switch (field.type) {
      case 0: // TEXT
        return (
          <input
            type="text"
            value={value}
            onChange={(e) => handleFieldChange(field.id, e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder={`Enter ${field.label.toLowerCase()}`}
          />
        )
      case 1: // DATE
        return (
          <input
            type="date"
            value={value}
            onChange={(e) => handleFieldChange(field.id, e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        )
      case 2: // NUMBER
        return (
          <input
            type="number"
            value={value}
            onChange={(e) => handleFieldChange(field.id, e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder={`Enter ${field.label.toLowerCase()}`}
          />
        )
      case 3: // DROPDOWN
        return (
          <select
            value={value}
            onChange={(e) => handleFieldChange(field.id, e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="">Select {field.label.toLowerCase()}</option>
            {field.options?.map((option: string, index: number) => (
              <option key={index} value={option}>
                {option}
              </option>
            ))}
          </select>
        )
      default:
        return (
          <input
            type="text"
            value={value}
            onChange={(e) => handleFieldChange(field.id, e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder={`Enter ${field.label.toLowerCase()}`}
          />
        )
    }
  }

  if (!isOpen) return null

  return (
    <Modal isOpen={isOpen} onClose={onClose} className="bg-white shadow-lg w-full max-w-lg p-6">
      <div className="flex justify-between items-center mb-4">
        <h3 className="text-lg font-medium">Edit Custom Fields</h3>
        <button onClick={onClose} className="text-gray-400 hover:text-gray-600">
          <X size={20} />
        </button>
      </div>

      <form onSubmit={handleSubmit} className="space-y-4">
        <div className="space-y-4 max-h-96 overflow-y-auto">
          {customFieldsWithValues.map((field) => (
            <div key={field.id} className="space-y-2">
              <label className="block text-sm font-medium text-gray-700">
                {field.label}
                {field.type === 3 && field.options && <span className="text-xs text-gray-500 ml-1">(Dropdown)</span>}
              </label>
              {renderFieldInput(field)}
            </div>
          ))}
          {customFieldsWithValues.length === 0 && (
            <div className="text-center py-8">
              <p className="text-gray-500">No custom fields available.</p>
              <p className="text-sm text-gray-400 mt-1">Create custom fields in the admin panel to use them here.</p>
            </div>
          )}
        </div>

        <div className="flex justify-end space-x-2 pt-4 border-t">
          <button
            type="button"
            onClick={onClose}
            className="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          >
            Cancel
          </button>
          <button
            type="submit"
            className="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          >
            Save Custom Fields
          </button>
        </div>
      </form>
    </Modal>
  )
}

// Background dialog component
export const BackgroundDialog = ({
  isOpen,
  onClose,
  initialData,
  onSubmit,
}: {
  isOpen: boolean
  onClose: () => void
  initialData: string
  onSubmit: (data: string) => void
}) => {
  const [backgroundText, setBackgroundText] = useState(initialData || "")

  useEffect(() => {
    if (isOpen) {
      setBackgroundText(initialData || "")
    }
  }, [isOpen, initialData])

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    onSubmit(backgroundText)
    onClose()
  }

  if (!isOpen) return null

  return (
    <Modal isOpen={isOpen} onClose={onClose} className="bg-white shadow-lg w-full max-w-lg p-6">
      <div className="flex justify-between items-center mb-4">
        <h3 className="text-lg font-medium">Edit Background</h3>
        <button onClick={onClose} className="text-gray-400 hover:text-gray-600">
          <X size={20} />
        </button>
      </div>

      <form onSubmit={handleSubmit} className="space-y-4">
        <div className="space-y-2">
          <label htmlFor="background" className="block text-sm font-medium text-gray-700">
            Background Information
          </label>
          <textarea
            id="background"
            value={backgroundText}
            onChange={(e) => setBackgroundText(e.target.value)}
            placeholder="Add background information about this person..."
            className="w-full min-h-[200px] px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
        </div>

        <div className="flex justify-end space-x-2 pt-4">
          <button
            type="button"
            onClick={onClose}
            className="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          >
            Cancel
          </button>
          <button
            type="submit"
            className="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          >
            Save
          </button>
        </div>
      </form>
    </Modal>
  )
}

// Collaborator dialog component
export const CollaboratorDialog = ({
  isOpen,
  onClose,
  usersData,
  isLoadingUsers,
  contact,
  showCollaboratorSearch,
  setShowCollaboratorSearch,
  collaboratorSearchTerm,
  setCollaboratorSearchTerm,
  handleAddCollaborator,
}: {
  isOpen: boolean
  onClose: () => void
  usersData: any
  isLoadingUsers: boolean
  contact: any
  showCollaboratorSearch: boolean
  setShowCollaboratorSearch: (show: boolean) => void
  collaboratorSearchTerm: string
  setCollaboratorSearchTerm: (term: string) => void
  handleAddCollaborator: (userId: number) => void
}) => {
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (showCollaboratorSearch && !(event.target as Element).closest(".collaborator-search-container")) {
        setShowCollaboratorSearch(false)
      }
    }

    document.addEventListener("mousedown", handleClickOutside)
    return () => document.removeEventListener("mousedown", handleClickOutside)
  }, [showCollaboratorSearch, setShowCollaboratorSearch])

  if (!isOpen) return null

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div className="bg-white rounded-lg shadow-lg w-full max-w-md p-6">
        <div className="flex justify-between items-center mb-4">
          <h3 className="text-lg font-medium">Add Collaborator</h3>
          <button onClick={onClose} className="text-gray-400 hover:text-gray-600">
            <X size={20} />
          </button>
        </div>

        <div className="collaborator-search-container">
          <div className="space-y-4">
            <input
              type="text"
              placeholder="Search users by name or email..."
              value={collaboratorSearchTerm}
              onChange={(e) => {
                setCollaboratorSearchTerm(e.target.value)
                setShowCollaboratorSearch(true)
              }}
              onFocus={() => setShowCollaboratorSearch(true)}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            />

            {showCollaboratorSearch && (
              <div className="max-h-60 overflow-y-auto border border-gray-200 rounded-md">
                {isLoadingUsers ? (
                  <div className="p-3 text-center text-gray-500">Loading users...</div>
                ) : (
                  <div>
                    {usersData?.data?.items?.map((user: any) => {
                      const isAlreadyCollaborator = contact?.data.collaborators?.some((c: any) => c.id === user.id)
                      return (
                        <button
                          key={user.id}
                          type="button"
                          onClick={() => !isAlreadyCollaborator && handleAddCollaborator(user.id)}
                          disabled={isAlreadyCollaborator}
                          className={`w-full text-left px-3 py-2 transition-colors ${
                            isAlreadyCollaborator
                              ? "bg-gray-100 text-gray-500 cursor-not-allowed"
                              : "hover:bg-gray-100 focus:bg-gray-100 focus:outline-none"
                          }`}
                        >
                          <div className="flex items-center space-x-2">
                            <div className="h-8 w-8 bg-gray-200 text-gray-600 text-sm rounded-full flex items-center justify-center">
                              {user.avatar ? (
                                <img
                                  src={ASSETS_URL + "/storage/" + user.avatar || "/placeholder.svg"}
                                  alt={user.name}
                                  className="h-full w-full object-cover rounded-full"
                                />
                              ) : (
                                <span>{getInitials(user.name)}</span>
                              )}
                            </div>
                            <div>
                              <div className="font-medium text-gray-900">{user.name}</div>
                              <div className="text-sm text-gray-500">{user.email}</div>
                              {isAlreadyCollaborator && (
                                <div className="text-xs text-green-600">Already a collaborator</div>
                              )}
                            </div>
                          </div>
                        </button>
                      )
                    })}
                    {usersData?.data?.items?.length === 0 && (
                      <div className="p-3 text-center text-gray-500">No users found</div>
                    )}
                  </div>
                )}
              </div>
            )}
          </div>
        </div>

        <div className="flex justify-end space-x-2 pt-4 mt-4 border-t">
          <button
            type="button"
            onClick={onClose}
            className="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          >
            Cancel
          </button>
        </div>
      </div>
    </div>
  )
}

// Toast notification component
export const Toast = ({
  message,
  type = "success",
  onClose,
}: {
  message: string
  type?: "success" | "error"
  onClose: () => void
}) => {
  useEffect(() => {
    const timer = setTimeout(() => {
      onClose()
    }, 3000)

    return () => clearTimeout(timer)
  }, [onClose])

  return (
    <div
      className={`fixed bottom-4 right-4 px-4 py-3 rounded-md shadow-lg z-50 ${
        type === "success" ? "bg-green-500" : "bg-red-500"
      } text-white flex items-center justify-between`}
    >
      <span>{message}</span>
      <button onClick={onClose} className="ml-4 text-white">
        <X size={16} />
      </button>
    </div>
  )
}
