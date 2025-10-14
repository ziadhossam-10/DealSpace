"use client"

import type React from "react"
import { useState, useEffect } from "react"
import { toast } from "react-toastify"
import { X } from "lucide-react"
import { useCreateEmailTemplateMutation } from "./emailTemplatesApi"
import type { CreateEmailTemplateRequest } from "../../types/emailTemplates" 
import { RichTextEditor } from "../people/components/richTextEditor" 
import { useGetUsersQuery } from "../users/usersApi"

interface CreateEmailTemplateModalProps {
  isOpen: boolean
  onClose: () => void
  onSuccess: () => void
}

export default function CreateEmailTemplateModal({ isOpen, onClose, onSuccess }: CreateEmailTemplateModalProps) {
  const [createEmailTemplate, { isLoading }] = useCreateEmailTemplateMutation()
  const { data: usersData } = useGetUsersQuery({ page: 1, per_page: 1000 }, { skip: !isOpen })

  const [formData, setFormData] = useState<CreateEmailTemplateRequest>({
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
    if (!isOpen) {
      setFormData({
        name: "",
        subject: "",
        body: "",
        is_shared: false,
      })
      setErrors({ name: "", subject: "", body: "" })
      setMentionedUsers([])
    }
  }, [isOpen])

  const handleChange = (field: string, value: any) => {
    setFormData((prev) => ({
      ...prev,
      [field]: value,
    }))
    if (field in errors) {
      setErrors((prev) => ({ ...prev, [field]: "" }))
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

    if (!formData.name.trim()) {
      newErrors.name = "Template name is required"
      isValid = false
    }

    if (!formData.subject.trim()) {
      newErrors.subject = "Subject is required"
      isValid = false
    }

    if (!formData.body.trim() || formData.body === "<p></p>") {
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

    try {
      await createEmailTemplate(formData).unwrap()
      toast.success("Email template created successfully!")
      onSuccess()
      onClose()
    } catch (error: any) {
      console.error("Failed to create email template:", error)
      toast.error(error.data?.message || "Failed to create email template. Please try again.")
    }
  }

  if (!isOpen) return null

  const users = usersData?.data?.items || []

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 z-50 flex justify-center items-center p-4">
      <div className="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-hidden">
        <div className="flex justify-between items-center p-6 border-b border-gray-200">
          <h2 className="text-xl font-semibold text-gray-900">Create New Email Template</h2>
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

          {/* Subject */}
          <div>
            <label htmlFor="subject" className="block text-sm font-medium text-gray-700 mb-1">
              Subject*
            </label>
            <input
              id="subject"
              type="text"
              value={formData.subject}
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
                value={formData.body}
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
              checked={formData.is_shared}
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
