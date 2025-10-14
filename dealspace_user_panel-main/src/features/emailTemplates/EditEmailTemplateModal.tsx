"use client"

import type React from "react"
import { useState, useEffect } from "react"
import { toast } from "react-toastify"
import { X } from "lucide-react"
import { useUpdateEmailTemplateMutation } from "./emailTemplatesApi"
import type { EmailTemplate, UpdateEmailTemplateRequest }  from "../../types/emailTemplates" 
import { RichTextEditor } from "../people/components/richTextEditor"
import { useGetUsersQuery } from "../users/usersApi"

interface EditEmailTemplateModalProps {
  isOpen: boolean
  onClose: () => void
  template: EmailTemplate | null
  onTemplateUpdated: () => void
}

const EditEmailTemplateModal: React.FC<EditEmailTemplateModalProps> = ({
  isOpen,
  onClose,
  template,
  onTemplateUpdated,
}) => {
  const [updateEmailTemplate, { isLoading: isUpdating }] = useUpdateEmailTemplateMutation()
  const { data: usersData } = useGetUsersQuery({ page: 1, per_page: 1000 }, { skip: !isOpen })

  const [formData, setFormData] = useState<UpdateEmailTemplateRequest>({
    name: "",
    subject: "",
    body: "",
    is_shared: false,
  })

  const [errors, setErrors] = useState({
    name: "",
    subject: "",
    body: "",
  })

  const [mentionedUsers, setMentionedUsers] = useState<number[]>([])

  useEffect(() => {
    if (isOpen && template) {
      setFormData({
        name: template.name,
        subject: template.subject,
        body: template.body,
        is_shared: template.is_shared,
      })
      setErrors({ name: "", subject: "", body: "" })
      setMentionedUsers([])
    }
  }, [isOpen, template])

  const handleChange = (key: keyof UpdateEmailTemplateRequest, value: any) => {
    setFormData({ ...formData, [key]: value })
    if (key in errors) {
      setErrors((prev) => ({ ...prev, [key]: "" }))
    }
  }

  const handleMentionSelect = (userId: number) => {
    if (!mentionedUsers.includes(userId)) {
      setMentionedUsers((prev) => [...prev, userId])
    }
  }

  const validateForm = (): boolean => {
    let isValid = true
    const newErrors = { name: "", subject: "", body: "" }

    if (!formData.name?.trim()) {
      newErrors.name = "Template name is required"
      isValid = false
    }

    if (!formData.subject?.trim()) {
      newErrors.subject = "Subject is required"
      isValid = false
    }

    if (!formData.body?.trim() || formData.body === "<p></p>") {
      newErrors.body = "Body content is required"
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
      await updateEmailTemplate({ id: template.id, ...formData }).unwrap()
      toast.success("Email template updated successfully!")
      onTemplateUpdated()
      onClose()
    } catch (error: any) {
      console.error("Failed to update email template:", error)
      toast.error(error.data?.message || "Failed to update email template. Please try again.")
    }
  }

  if (!isOpen) return null

  const users = usersData?.data?.items || []

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 z-50 flex justify-center items-center p-4">
      <div className="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-hidden">
        <div className="flex justify-between items-center p-6 border-b border-gray-200">
          <h2 className="text-xl font-semibold text-gray-900">Edit Email Template</h2>
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

          {/* Subject */}
          <div>
            <label htmlFor="subject" className="block text-sm font-medium text-gray-700 mb-1">
              Subject*
            </label>
            <input
              type="text"
              id="subject"
              value={formData.subject || ""}
              onChange={(e) => handleChange("subject", e.target.value)}
              className={`w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 ${
                errors.subject ? "border-red-500" : "border-gray-300"
              }`}
              placeholder="Enter email subject"
            />
            {errors.subject && <p className="mt-1 text-sm text-red-500">{errors.subject}</p>}
          </div>

          {/* Body */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Body Content*</label>
            <div className={`${errors.body ? "border-red-500 border rounded-lg" : ""}`}>
              <RichTextEditor
                value={formData.body || ""}
                onChange={(value) => handleChange("body", value)}
                onMentionSelect={handleMentionSelect}
                users={users}
                placeholder="Type your email content here..."
                className="min-h-[300px]"
              />
            </div>
            {errors.body && <p className="mt-1 text-sm text-red-500">{errors.body}</p>}
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

          {/* Mentioned Users */}
          {mentionedUsers.length > 0 && (
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Mentioned Users ({mentionedUsers.length})
              </label>
              <div className="flex flex-wrap gap-2">
                {mentionedUsers.map((userId) => {
                  const user = users.find((u) => u.id === userId)
                  return user ? (
                    <span
                      key={userId}
                      className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                    >
                      {user.name}
                    </span>
                  ) : null
                })}
              </div>
            </div>
          )}

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

export default EditEmailTemplateModal
