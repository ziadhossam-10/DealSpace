"use client"

import type React from "react"

import { useEffect, useState } from "react"
import { useUpdateStageMutation, useGetStageByIdQuery } from "./stagesApi"
import { toast } from "react-toastify"
import { X } from "lucide-react"
import { Modal } from "../../components/modal"

interface EditStageModalProps {
  isOpen: boolean
  onClose: () => void
  stageId: number | null
}

export default function EditStageModal({ isOpen, onClose, stageId }: EditStageModalProps) {
  const { data: stageData, isLoading: isLoadingStage } = useGetStageByIdQuery(stageId || 0, {
    skip: !stageId,
  })
  const [updateStage, { isLoading: isUpdating }] = useUpdateStageMutation()

  // Form state
  const [formData, setFormData] = useState({
    id: 0,
    name: "",
    description: "",
  })

  // Validation state
  const [errors, setErrors] = useState({
    name: "",
  })

  // Load stage data when available
  useEffect(() => {
    if (stageData?.data) {
      setFormData({
        id: stageData.data.id || 0,
        name: stageData.data.name || "",
        description: stageData.data.description || "",
      })
    }
  }, [stageData])

  // Handle input changes
  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    const { name, value } = e.target
    setFormData((prev) => ({
      ...prev,
      [name]: value,
    }))

    // Clear error when field is edited
    if (name in errors) {
      setErrors((prev) => ({ ...prev, [name]: "" }))
    }
  }

  // Validate form
  const validateForm = (): boolean => {
    let isValid = true
    const newErrors = { ...errors }

    // Validate name
    if (!formData.name.trim()) {
      newErrors.name = "Name is required"
      isValid = false
    } else {
      newErrors.name = ""
    }

    setErrors(newErrors)
    return isValid
  }

  // Handle form submission
  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()

    if (!validateForm()) {
      return
    }

    try {
      // Call API to update stage
      await updateStage(formData).unwrap()

      // Show success message and close modal
      toast.success("Stage updated successfully!")
      onClose()
    } catch (error: any) {
      console.error("Failed to update stage:", error)
      toast.error(error.data?.message || "Failed to update stage. Please try again.")
    }
  }

  if (!isOpen) return null

  return (
    <Modal isOpen={isOpen} onClose={onClose} className="max-w-lg mx-auto pt-4">
        <div className="flex justify-between items-center p-4 border-b">
          <h3 className="text-lg font-semibold">Edit Stage</h3>
        </div>

        {isLoadingStage ? (
          <div className="p-6 text-center">Loading stage data...</div>
        ) : (
          <form onSubmit={handleSubmit} className="p-4">
            <div className="space-y-4">
              {/* Name */}
              <div>
                <label htmlFor="name" className="block text-sm font-medium text-gray-700 mb-1">
                  Name*
                </label>
                <input
                  type="text"
                  id="name"
                  name="name"
                  value={formData.name}
                  onChange={handleChange}
                  className={`w-full px-3 py-2 border ${
                    errors.name ? "border-red-500" : "border-gray-300"
                  } rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500`}
                />
                {errors.name && <p className="mt-1 text-sm text-red-500">{errors.name}</p>}
              </div>

              {/* Description */}
              <div>
                <label htmlFor="description" className="block text-sm font-medium text-gray-700 mb-1">
                  Description
                </label>
                <textarea
                  id="description"
                  name="description"
                  value={formData.description}
                  onChange={handleChange}
                  rows={3}
                  className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>
            </div>

            {/* Form Actions */}
            <div className="mt-6 flex justify-end space-x-3">
              <button
                type="button"
                onClick={onClose}
                className="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
              >
                Cancel
              </button>
              <button
                type="submit"
                disabled={isUpdating}
                className={`px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 ${
                  isUpdating ? "opacity-75 cursor-not-allowed" : ""
                }`}
              >
                {isUpdating ? "Updating..." : "Update Stage"}
              </button>
            </div>
          </form>
        )}
    </Modal>
  )
}
