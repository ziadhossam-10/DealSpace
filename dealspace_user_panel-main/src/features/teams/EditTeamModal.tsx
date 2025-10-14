"use client"

import type React from "react"
import { useState, useEffect } from "react"
import { useUpdateTeamMutation } from "./teamsApi"
import type { Team, UpdateTeamRequest } from "../../types/teams"
import { X } from "lucide-react"

interface EditTeamModalProps {
  isOpen: boolean
  onClose: () => void
  team: Team | null
  onTeamUpdated: () => void
}

const EditTeamModal: React.FC<EditTeamModalProps> = ({ isOpen, onClose, team, onTeamUpdated }) => {
  const [updateTeam, { isLoading: isUpdating }] = useUpdateTeamMutation()
  const [formData, setFormData] = useState<UpdateTeamRequest>({
    name: "",
  })

  const [errors, setErrors] = useState({
    name: "",
  })

  useEffect(() => {
    if (isOpen && team) {
      setFormData({
        name: team.name,
      })
      setErrors({ name: "" })
    }
  }, [isOpen, team])

  const handleChange = (key: keyof UpdateTeamRequest, value: any) => {
    setFormData({ ...formData, [key]: value })
  }

  const validateForm = (): boolean => {
    let isValid = true
    const newErrors = { name: "" }

    if (!formData.name?.trim()) {
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

    if (!team) {
      return
    }

    try {
      await updateTeam({ id: team.id, ...formData }).unwrap()
      onTeamUpdated()
      onClose()
    } catch (error) {
      console.error("Failed to update team:", error)
    }
  }

  if (!isOpen) return null

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 z-50 flex justify-center items-center p-4">
      <div className="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-hidden">
        <div className="flex justify-between items-center p-6 border-b border-gray-200">
          <h2 className="text-xl font-semibold text-gray-900">Edit Team</h2>
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
              type="text"
              id="name"
              value={formData.name || ""}
              onChange={(e) => handleChange("name", e.target.value)}
              className={`w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 ${
                errors.name ? "border-red-500" : "border-gray-300"
              }`}
            />
            {errors.name && <p className="mt-1 text-sm text-red-500">{errors.name}</p>}
          </div>

          {/* Submit Button */}
          <div className="flex justify-end">
            <button
              type="submit"
              className="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-blue-300"
              disabled={isUpdating}
            >
              {isUpdating ? "Updating..." : "Update Team"}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}

export default EditTeamModal
