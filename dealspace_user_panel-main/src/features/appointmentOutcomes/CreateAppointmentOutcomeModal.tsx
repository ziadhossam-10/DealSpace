"use client"

import type React from "react"
import { useState } from "react"
import { useCreateAppointmentOutcomeMutation, useGetAppointmentOutcomesQuery } from "./appointmentOutcomesApi"
import { toast } from "react-toastify"
import { Modal } from "../../components/modal"

interface CreateAppointmentOutcomeModalProps {
  isOpen: boolean
  onClose: () => void
}

export default function CreateAppointmentOutcomeModal({ isOpen, onClose }: CreateAppointmentOutcomeModalProps) {
  const [createAppointmentOutcome, { isLoading }] = useCreateAppointmentOutcomeMutation()
  const { data: appointmentOutcomesData } = useGetAppointmentOutcomesQuery()

  // Form state
  const [formData, setFormData] = useState({
    name: "",
    description: "",
    sort: 0,
  })

  // Validation state
  const [errors, setErrors] = useState({
    name: "",
    description: "",
  })

  // Calculate next sort order
  const getNextSortOrder = () => {
    const appointmentOutcomes = appointmentOutcomesData?.data || []
    if (appointmentOutcomes.length === 0) return 1
    return Math.max(...appointmentOutcomes.map((dt) => dt.sort)) + 1
  }

  // Handle input changes
  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const { name, value } = e.target
    setFormData((prev) => ({
      ...prev,
      [name]: name === "sort" ? Number.parseInt(value) || 0 : value,
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
      // Use next sort order if not specified
      const sortOrder = formData.sort || getNextSortOrder()

      await createAppointmentOutcome({
        name: formData.name,
        description: "", // Assuming description is optional and not provided
        sort: sortOrder,
      }).unwrap()

      toast.success("Appointment outcome created successfully!")
      resetForm()
      onClose()
    } catch (error: any) {
      console.error("Failed to create appointment outcome:", error)
      toast.error(error.data?.message || "Failed to create appointment outcome. Please try again.")
    }
  }

  // Reset form
  const resetForm = () => {
    setFormData({
      name: "",
      description: "",
      sort: 0,
    })
    setErrors({
      name: "",
      description: "",
    })
  }

  return (
    <Modal isOpen={isOpen} onClose={onClose} className="max-w-lg mx-auto pt-4">
      <div className="flex justify-between items-center p-4 border-b">
        <h3 className="text-lg font-semibold">Create New Appointment Outcome</h3>
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
              placeholder="Enter appointment outcome name"
            />
            {errors.name && <p className="mt-1 text-sm text-red-500">{errors.name}</p>}
          </div>
          {/* Description */}
          <div>
            <label htmlFor="description" className="block text-sm font-medium text-gray-700 mb-1">
              Description*
            </label>
            <input
              type="text"
              id="description"
              name="description"
              value={formData.description}
              onChange={handleChange}
              className={`w-full px-3 py-2 border ${
                errors.description ? "border-red-500" : "border-gray-300"
              } rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500`}
              placeholder="Enter appointment outcome description"
            />
            {errors.description && <p className="mt-1 text-sm text-red-500">{errors.description}</p>}
          </div>

          {/* Sort Order */}
          <div>
            <label htmlFor="sort" className="block text-sm font-medium text-gray-700 mb-1">
              Sort Order
            </label>
            <input
              type="number"
              id="sort"
              name="sort"
              value={formData.sort || ""}
              onChange={handleChange}
              min="1"
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder={`Auto-assign (${getNextSortOrder()})`}
            />
            <p className="mt-1 text-xs text-gray-500">Leave empty to auto-assign the next available order</p>
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
            {isLoading ? "Creating..." : "Create Appointment Outcome"}
          </button>
        </div>
      </form>
    </Modal>
  )
}
