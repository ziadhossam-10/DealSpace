"use client"

import type React from "react"

import { useState } from "react"
import { useCreateStageMutation } from "./stagesApi"
import { toast } from "react-toastify"
import { X } from "lucide-react"
import { Modal } from "../../components/modal"

interface CreateStageModalProps {
  isOpen: boolean
  onClose: () => void
}

export default function CreateStageModal({ isOpen, onClose }: CreateStageModalProps) {
  const [createStage, { isLoading }] = useCreateStageMutation()

  // Form state
  const [formData, setFormData] = useState({
    name: "",
    description: "",
  })

  // Validation state
  const [errors, setErrors] = useState({
    name: "",
    description: "",
  })

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
      // Call API to create stage
      await createStage(formData).unwrap()

      // Show success message and close modal
      toast.success("Stage created successfully!")
      resetForm()
      onClose()
    } catch (error: any) {
      console.error("Failed to create stage:", error)
      toast.error(error.data?.message || "Failed to create stage. Please try again.")
    }
  }

  // Reset form
  const resetForm = () => {
    setFormData({
      name: "",
      description: "",
    })
    setErrors({
      name: "",
      description: "",
    })
  }

  return (
    <Modal isOpen={isOpen} onClose={onClose} className="max-w-lg mx-auto pt-4">
      <div className="flex justify-between items-center p-4 border-b">
        <h3 className="text-lg font-semibold">Create New Stage</h3>
      </div>

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
            onClick={() => {
              resetForm()
              onClose()
            }}
            className="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          >
            Cancel
          </button>
          <button
            type="submit"
            disabled={isLoading}
            className={`px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 ${
              isLoading ? "opacity-75 cursor-not-allowed" : ""
            }`}
          >
            {isLoading ? "Creating..." : "Create Stage"}
          </button>
        </div>
      </form>
    </Modal>
  )
}
