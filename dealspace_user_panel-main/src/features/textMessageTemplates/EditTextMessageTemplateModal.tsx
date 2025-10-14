"use client"

import type React from "react"
import { useState, useEffect } from "react"
import { toast } from "react-toastify"
import { X } from "lucide-react"
import { useUpdateTextMessageTemplateMutation } from "./textMessageTemplatesApi"
import type { TextMessageTemplate, UpdateTextMessageTemplateRequest } from "../../types/textMessageTemplates"

interface EditTextMessageTemplateModalProps {
  isOpen: boolean
  onClose: () => void
  template: TextMessageTemplate | null
  onTemplateUpdated: () => void
}

const EditTextMessageTemplateModal: React.FC<EditTextMessageTemplateModalProps> = ({
  isOpen,
  onClose,
  template,
  onTemplateUpdated,
}) => {
  const [updateTextMessageTemplate, { isLoading: isUpdating }] = useUpdateTextMessageTemplateMutation()

  const [formData, setFormData] = useState<UpdateTextMessageTemplateRequest>({
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
    if (isOpen && template) {
      setFormData({
        name: template.name,
        message: template.message,
        is_shared: template.is_shared,
      })
      setCharacterCount(template.message.length)
      setErrors({ name: "", message: "" })
    }
  }, [isOpen, template])

  const handleChange = (key: keyof UpdateTextMessageTemplateRequest, value: any) => {
    setFormData({ ...formData, [key]: value })

    if (key === "message") {
      setCharacterCount(value.length)
    }

    if (key in errors) {
      setErrors((prev) => ({ ...prev, [key]: "" }))
    }
  }

  const validateForm = (): boolean => {
    let isValid = true
    const newErrors = { name: "", message: "" }

    if (!formData.name?.trim()) {
      newErrors.name = "Template name is required"
      isValid = false
    }

    if (!formData.message?.trim()) {
      newErrors.message = "Message content is required"
      isValid = false
    }

    if (formData.message && formData.message.length > 1600) {
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

    if (!template) {
      return
    }

    try {
      await updateTextMessageTemplate({ id: template.id, ...formData }).unwrap()
      toast.success("Text message template updated successfully!")
      onTemplateUpdated()
      onClose()
    } catch (error: any) {
      console.error("Failed to update text message template:", error)
      toast.error(error.data?.message || "Failed to update text message template. Please try again.")
    }
  }

  if (!isOpen) return null

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 z-50 flex justify-center items-center p-4">
      <div className="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-hidden">
        <div className="flex justify-between items-center p-6 border-b border-gray-200">
          <h2 className="text-xl font-semibold text-gray-900">Edit Text Message Template</h2>
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
              type="text"
              id="name"
              value={formData.name || ""}
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
                value={formData.message || ""}
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
              checked={formData.is_shared || false}
              onChange={(e) => handleChange("is_shared", e.target.checked)}
              className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
            />
            <label htmlFor="is_shared" className="ml-2 block text-sm text-gray-700">
              Share this template with other users
            </label>
          </div>

          {/* Submit Button */}
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
              className="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-blue-300"
              disabled={isUpdating}
            >
              {isUpdating ? "Updating..." : "Update Template"}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}

export default EditTextMessageTemplateModal
