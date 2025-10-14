"use client"

import type React from "react"
import { useEffect, useState } from "react"
import { useUpdateAppointmentTypeMutation, useGetAppointmentTypeByIdQuery } from "./appointmentTypesApi"
import { toast } from "react-toastify"
import { Modal } from "../../components/modal"

interface EditAppointmentTypeModalProps {
  isOpen: boolean
  onClose: () => void
  appointmentTypeId: number | null
}

export default function EditAppointmentTypeModal({ isOpen, onClose, appointmentTypeId }: EditAppointmentTypeModalProps) {
  const { data: appointmentTypeData, isLoading: isLoadingAppointmentType } = useGetAppointmentTypeByIdQuery(appointmentTypeId || 0, {
    skip: !appointmentTypeId,
  })
  const [updateAppointmentType, { isLoading: isUpdating }] = useUpdateAppointmentTypeMutation()

  // Form state
  const [formData, setFormData] = useState({
    id: 0,
    name: "",
    description: "",
    sort: 0,
  })

  // Validation state
  const [errors, setErrors] = useState({
    name: "",
    description: "",
  })

  // Load appointment type data when available
  useEffect(() => {
    if (appointmentTypeData?.data) {
      setFormData({
        id: appointmentTypeData.data.id || 0,
        name: appointmentTypeData.data.name || "",
        description: appointmentTypeData.data.description || "",
        sort: appointmentTypeData.data.sort || 0,
      })
    }
  }, [appointmentTypeData])

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
      await updateAppointmentType(formData).unwrap()
      toast.success("Appointment type updated successfully!")
      onClose()
    } catch (error: any) {
      console.error("Failed to update appointment type:", error)
      toast.error(error.data?.message || "Failed to update appointment type. Please try again.")
    }
  }

  if (!isOpen) return null

  return (
    <Modal isOpen={isOpen} onClose={onClose} className="max-w-lg mx-auto pt-4">
      <div className="flex justify-between items-center p-4 border-b">
        <h3 className="text-lg font-semibold">Edit Appointment Type</h3>
      </div>

      {isLoadingAppointmentType ? (
        <div className="p-6 text-center">Loading appointment type data...</div>
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
                placeholder="Enter appointment type name"
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
                placeholder="Enter appointment type description"
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
                value={formData.sort}
                onChange={handleChange}
                min="1"
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
              {isUpdating ? "Updating..." : "Update Appointment Type"}
            </button>
          </div>
        </form>
      )}
    </Modal>
  )
}
