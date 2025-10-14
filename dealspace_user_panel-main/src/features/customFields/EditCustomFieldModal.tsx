"use client"

import type React from "react"

import { useEffect, useState } from "react"
import { useUpdateCustomFieldMutation, useGetCustomFieldByIdQuery } from "./customFieldsApi"
import { toast } from "react-toastify"
import { Plus, Trash2 } from "lucide-react"
import { Modal } from "../../components/modal"

interface EditCustomFieldModalProps {
  isOpen: boolean
  onClose: () => void
  customFieldId: number | null
}

const FIELD_TYPES = [
  { value: 0, label: "Text" },
  { value: 1, label: "Date" },
  { value: 2, label: "Number" },
  { value: 3, label: "Dropdown" },
]

export default function EditCustomFieldModal({ isOpen, onClose, customFieldId }: EditCustomFieldModalProps) {
  const { data: customFieldData, isLoading: isLoadingCustomField } = useGetCustomFieldByIdQuery(customFieldId || 0, {
    skip: !customFieldId,
  })
  const [updateCustomField, { isLoading: isUpdating }] = useUpdateCustomFieldMutation()

  // Form state
  const [formData, setFormData] = useState({
    id: 0,
    label: "",
    type: 0,
    options: [""],
  })

  // Validation state
  const [errors, setErrors] = useState({
    label: "",
    options: "",
  })

  // Load custom field data when available
  useEffect(() => {
    if (customFieldData?.data) {
      setFormData({
        id: customFieldData.data.id || 0,
        label: customFieldData.data.label || "",
        type: customFieldData.data.type || 0,
        options:
          customFieldData.data.options && customFieldData.data.options.length > 0 ? customFieldData.data.options : [""],
      })
    }
  }, [customFieldData])

  // Handle input changes
  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
    const { name, value } = e.target
    setFormData((prev) => ({
      ...prev,
      [name]: name === "type" ? Number.parseInt(value) : value,
    }))

    // Clear error when field is edited
    if (name in errors) {
      setErrors((prev) => ({ ...prev, [name]: "" }))
    }
  }

  // Handle option changes
  const handleOptionChange = (index: number, value: string) => {
    const newOptions = [...formData.options]
    newOptions[index] = value
    setFormData((prev) => ({
      ...prev,
      options: newOptions,
    }))

    // Clear options error
    setErrors((prev) => ({ ...prev, options: "" }))
  }

  // Add new option
  const addOption = () => {
    setFormData((prev) => ({
      ...prev,
      options: [...prev.options, ""],
    }))
  }

  // Remove option
  const removeOption = (index: number) => {
    if (formData.options.length > 1) {
      const newOptions = formData.options.filter((_, i) => i !== index)
      setFormData((prev) => ({
        ...prev,
        options: newOptions,
      }))
    }
  }

  // Validate form
  const validateForm = (): boolean => {
    let isValid = true
    const newErrors = { ...errors }

    // Validate label
    if (!formData.label.trim()) {
      newErrors.label = "Label is required"
      isValid = false
    } else {
      newErrors.label = ""
    }

    // Validate options for dropdown type
    if (formData.type === 3) {
      const validOptions = formData.options.filter((option) => option.trim() !== "")
      if (validOptions.length === 0) {
        newErrors.options = "At least one option is required for dropdown fields"
        isValid = false
      } else {
        newErrors.options = ""
      }
    } else {
      newErrors.options = ""
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
      const submitData = {
        id: formData.id,
        label: formData.label,
        type: formData.type,
        ...(formData.type === 3 && {
          options: formData.options.filter((option) => option.trim() !== ""),
        }),
      }

      // Call API to update custom field
      await updateCustomField(submitData).unwrap()

      // Show success message and close modal
      toast.success("Custom field updated successfully!")
      onClose()
    } catch (error: any) {
      console.error("Failed to update custom field:", error)
      toast.error(error.data?.message || "Failed to update custom field. Please try again.")
    }
  }

  if (!isOpen) return null

  return (
    <Modal isOpen={isOpen} onClose={onClose} className="max-w-lg mx-auto pt-4">
      <div className="flex justify-between items-center p-4 border-b">
        <h3 className="text-lg font-semibold">Edit Custom Field</h3>
      </div>

      {isLoadingCustomField ? (
        <div className="p-6 text-center">Loading custom field data...</div>
      ) : (
        <form onSubmit={handleSubmit} className="p-4">
          <div className="space-y-4">
            {/* Label */}
            <div>
              <label htmlFor="label" className="block text-sm font-medium text-gray-700 mb-1">
                Label*
              </label>
              <input
                type="text"
                id="label"
                name="label"
                value={formData.label}
                onChange={handleChange}
                className={`w-full px-3 py-2 border ${
                  errors.label ? "border-red-500" : "border-gray-300"
                } rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500`}
              />
              {errors.label && <p className="mt-1 text-sm text-red-500">{errors.label}</p>}
            </div>

            {/* Type */}
            <div>
              <label htmlFor="type" className="block text-sm font-medium text-gray-700 mb-1">
                Type*
              </label>
              <select
                id="type"
                name="type"
                value={formData.type}
                onChange={handleChange}
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                {FIELD_TYPES.map((type) => (
                  <option key={type.value} value={type.value}>
                    {type.label}
                  </option>
                ))}
              </select>
            </div>

            {/* Options (only for dropdown type) */}
            {formData.type === 3 && (
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Options*</label>
                <div className="space-y-2">
                  {formData.options.map((option, index) => (
                    <div key={index} className="flex items-center gap-2">
                      <input
                        type="text"
                        value={option}
                        onChange={(e) => handleOptionChange(index, e.target.value)}
                        placeholder={`Option ${index + 1}`}
                        className="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                      />
                      {formData.options.length > 1 && (
                        <button
                          type="button"
                          onClick={() => removeOption(index)}
                          className="p-2 text-red-500 hover:text-red-700"
                        >
                          <Trash2 size={16} />
                        </button>
                      )}
                    </div>
                  ))}
                  <button
                    type="button"
                    onClick={addOption}
                    className="flex items-center gap-2 text-blue-600 hover:text-blue-800 text-sm"
                  >
                    <Plus size={16} />
                    Add Option
                  </button>
                </div>
                {errors.options && <p className="mt-1 text-sm text-red-500">{errors.options}</p>}
              </div>
            )}
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
              {isUpdating ? "Updating..." : "Update Custom Field"}
            </button>
          </div>
        </form>
      )}
    </Modal>
  )
}
