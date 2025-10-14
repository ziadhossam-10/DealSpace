"use client"

import type React from "react"
import { useEffect, useState } from "react"
import { useUpdateDealTypeMutation, useGetDealTypeByIdQuery } from "./dealTypesApi"
import { toast } from "react-toastify"
import { Modal } from "../../components/modal"

interface EditDealTypeModalProps {
  isOpen: boolean
  onClose: () => void
  dealTypeId: number | null
}

export default function EditDealTypeModal({ isOpen, onClose, dealTypeId }: EditDealTypeModalProps) {
  const { data: dealTypeData, isLoading: isLoadingDealType } = useGetDealTypeByIdQuery(dealTypeId || 0, {
    skip: !dealTypeId,
  })
  const [updateDealType, { isLoading: isUpdating }] = useUpdateDealTypeMutation()

  // Form state
  const [formData, setFormData] = useState({
    id: 0,
    name: "",
    sort: 0,
  })

  // Validation state
  const [errors, setErrors] = useState({
    name: "",
  })

  // Load deal type data when available
  useEffect(() => {
    if (dealTypeData?.data) {
      setFormData({
        id: dealTypeData.data.id || 0,
        name: dealTypeData.data.name || "",
        sort: dealTypeData.data.sort || 0,
      })
    }
  }, [dealTypeData])

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
      await updateDealType(formData).unwrap()
      toast.success("Deal type updated successfully!")
      onClose()
    } catch (error: any) {
      console.error("Failed to update deal type:", error)
      toast.error(error.data?.message || "Failed to update deal type. Please try again.")
    }
  }

  if (!isOpen) return null

  return (
    <Modal isOpen={isOpen} onClose={onClose} className="max-w-lg mx-auto pt-4">
      <div className="flex justify-between items-center p-4 border-b">
        <h3 className="text-lg font-semibold">Edit Deal Type</h3>
      </div>

      {isLoadingDealType ? (
        <div className="p-6 text-center">Loading deal type data...</div>
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
                placeholder="Enter deal type name"
              />
              {errors.name && <p className="mt-1 text-sm text-red-500">{errors.name}</p>}
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
              {isUpdating ? "Updating..." : "Update Deal Type"}
            </button>
          </div>
        </form>
      )}
    </Modal>
  )
}
