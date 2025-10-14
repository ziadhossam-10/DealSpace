"use client"

import type React from "react"
import { useState, useEffect } from "react"
import { toast } from "react-toastify"
import { X } from "lucide-react"
import { useCreateTextMessageTemplateMutation } from "./textMessageTemplatesApi"
import type { CreateTextMessageTemplateRequest } from "../../types/textMessageTemplates"

interface CreateTextMessageTemplateModalProps {
  isOpen: boolean
  onClose: () => void
  onSuccess: () => void
}

export default function CreateTextMessageTemplateModal({
  isOpen,
  onClose,
  onSuccess,
}: CreateTextMessageTemplateModalProps) {
  const [createTextMessageTemplate, { isLoading }] = useCreateTextMessageTemplateMutation()

  const [formData, setFormData] = useState<CreateTextMessageTemplateRequest>({
    name: "",
    message: "",
    is_shared: false,
  })

  const [errors, setErrors] = useState({
    name: "",
    message: "",
  })

  const [characterCount, setCharacterCount] = useState(0)

  useEffect(() => {
    if (!isOpen) {
      setFormData({
        name: "",
        message: "",
        is_shared: false,
      })
      setErrors({ name: "", message: "" })
      setCharacterCount(0)
    }
  }, [isOpen])

  const handleChange = (field: string, value: any) => {
    setFormData((prev) => ({
      ...prev,
      [field]: value,
    }))

    if (field === "message") {
      setCharacterCount(value.length)
    }

    if (field in errors) {
      setErrors((prev) => ({ ...prev, [field]: "" }))
    }
  }

  const validateForm = (): boolean => {
    let isValid = true
    const newErrors = { name: "", message: "" }

    if (!formData.name.trim()) {
      newErrors.name = "Template name is required"
      isValid = false
    }

    if (!formData.message.trim()) {
      newErrors.message = "Message content is required"
      isValid = false
    }

    if (formData.message.length > 1600) {
      newErrors.message = "Message is too long (max 1600 characters)"
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

    try {
      await createTextMessageTemplate(formData).unwrap()
      toast.success("Text message template created successfully!")
      onSuccess()
      onClose()
    } catch (error: any) {
      console.error("Failed to create text message template:", error)
      toast.error(error.data?.message || "Failed to create text message template. Please try again.")
    }
  }

  if (!isOpen) return null

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 z-50 flex justify-center items-center p-4">
      <div className="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-hidden">
        <div className="flex justify-between items-center p-6 border-b border-gray-200">
          <h2 className="text-xl font-semibold text-gray-900">Create New Text Message Template</h2>
          <button onClick={onClose} className="p-1 rounded-md hover:bg-gray-100 transition-colors">
            <X className="h-4 w-4 text-gray-500" />
          </button>
        </div>

        <form onSubmit={handleSubmit} className="p-6 space-y-6 overflow-y-auto max-h-[calc(90vh-120px)]">
          {/* Template Name */}
          <div>
            <label htmlFor="name" className="block text-sm font-medium text-gray-700 mb-1">
              Template Name*
            </label>
            <input
              id="name"
              type="text"
              value={formData.name}
              onChange={(e) => handleChange("name", e.target.value)}
              className={`w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 ${
                errors.name ? "border-red-500" : "border-gray-300"
              }`}
              placeholder="Enter template name"
            />
            {errors.name && <p className="mt-1 text-sm text-red-500">{errors.name}</p>}
          </div>

          {/* Message */}
          <div>
            <label htmlFor="message" className="block text-sm font-medium text-gray-700 mb-1">
              Message Content*
            </label>
            <div className="relative">
              <textarea
                id="message"
                value={formData.message}
                onChange={(e) => handleChange("message", e.target.value)}
                rows={6}
                className={`w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none ${
                  errors.message ? "border-red-500" : "border-gray-300"
                }`}
                placeholder="Type your text message here..."
                maxLength={1600}
              />
              <div className="absolute bottom-2 right-2 text-xs text-gray-500">{characterCount}/1600</div>
            </div>
            {errors.message && <p className="mt-1 text-sm text-red-500">{errors.message}</p>}
            <p className="mt-1 text-xs text-gray-500">Tip: Use variables like User_name to personalize messages</p>
          </div>

          {/* Is Shared */}
          <div className="flex items-center">
            <input
              id="is_shared"
              type="checkbox"
              checked={formData.is_shared}
              onChange={(e) => handleChange("is_shared", e.target.checked)}
              className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
            />
            <label htmlFor="is_shared" className="ml-2 block text-sm text-gray-700">
              Share this template with other users
            </label>
          </div>

          {/* Form Actions */}
          <div className="flex justify-end space-x-3 pt-4 border-t border-gray-200">
            <button
              type="button"
              onClick={onClose}
              className="px-4 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={isLoading}
              className={`px-4 py-2 border border-transparent rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 ${
                isLoading ? "opacity-75 cursor-not-allowed" : ""
              }`}
            >
              {isLoading ? "Creating..." : "Create Template"}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}
