"use client"

import type React from "react"
import { useState, useEffect } from "react"
import { X, Send, LayoutTemplateIcon as Template } from "lucide-react"
import { RichTextEditor } from "./richTextEditor"
import { useGetUsersQuery } from "../../users/usersApi"
import EmailTemplateSelectionModal from "./EmailTemplateSelectionModal"
import type { EmailTemplate } from "../../../types/emailTemplates"
import { EmailAccount } from "../../../types/emailAccounts"
import { Modal } from "../../../components/modal"

interface CampaignData {
  name: string
  description: string
  subject: string
  body: string
  body_html: string
  email_account_id: number
  use_all_emails: boolean
  recipient_ids?: number[]
  is_all_selected: boolean
  // Filter options
  stage_id?: number | null
  team_id?: number | null
  user_ids?: number[]
  search?: string
  deal_type_id?: number | null
}

interface CampaignModalProps {
  isOpen: boolean
  onClose: () => void
  onSubmit: (data: CampaignData) => void
  emailAccounts: EmailAccount[]
  selectedCount: number
  isLoading: boolean
}

export default function CampaignModal({
  isOpen,
  onClose,
  onSubmit,
  emailAccounts,
  selectedCount,
  isLoading,
}: CampaignModalProps) {
  const [formData, setFormData] = useState<
    Omit<
      CampaignData,
      "recipient_ids" | "is_all_selected" | "stage_id" | "team_id" | "user_ids" | "search" | "deal_type_id"
    >
  >({
    name: "",
    description: "",
    subject: "",
    body: "",
    body_html: "",
    email_account_id: 0,
    use_all_emails: false,
  })

  const [errors, setErrors] = useState<Record<string, string>>({})
  const [mentionedUsers, setMentionedUsers] = useState<number[]>([])
  const [showTemplateModal, setShowTemplateModal] = useState(false)
  const [selectedTemplate, setSelectedTemplate] = useState<EmailTemplate | null>(null)

  const { data: usersData } = useGetUsersQuery({ page: 1, per_page: 1000 })

  // Reset form when modal opens/closes
  useEffect(() => {
    if (isOpen) {
      setFormData({
        name: "",
        description: "",
        subject: "",
        body: "",
        body_html: "",
        email_account_id: emailAccounts.length > 0 ? emailAccounts[0].id : 0,
        use_all_emails: false,
      })
      setErrors({})
      setMentionedUsers([])
      setSelectedTemplate(null)
      setShowTemplateModal(false)
    }
  }, [isOpen, emailAccounts])

  const handleInputChange = (field: keyof typeof formData, value: any) => {
    setFormData((prev) => ({
      ...prev,
      [field]: value,
    }))
    // Clear error when user starts typing
    if (errors[field]) {
      setErrors((prev) => ({
        ...prev,
        [field]: "",
      }))
    }
  }

  const handleMentionSelect = (userId: number) => {
    if (!mentionedUsers.includes(userId)) {
      setMentionedUsers((prev) => [...prev, userId])
    }
  }

  const handleTemplateSelect = (template: EmailTemplate) => {
    setSelectedTemplate(template)
    handleInputChange("subject", template.subject)
    handleInputChange("body", template.body)
    setShowTemplateModal(false)
  }

  const validateForm = () => {
    const newErrors: Record<string, string> = {}

    if (!formData.name.trim()) {
      newErrors.name = "Campaign name is required"
    }
    if (!formData.subject.trim()) {
      newErrors.subject = "Email subject is required"
    }
    if (!formData.body.trim()) {
      newErrors.body = "Email content is required"
    }
    if (!formData.email_account_id) {
      newErrors.email_account_id = "Email account is required"
    }

    setErrors(newErrors)
    return Object.keys(newErrors).length === 0
  }

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    if (validateForm()) {
      const submitData: CampaignData = {
        ...formData,
        body_html: formData.body,
        body: formData.body.replace(/<[^>]*>/g, ""), // Strip HTML for plain text
        recipient_ids: undefined, // Will be set by parent component
        is_all_selected: false, // Will be set by parent component
      }
      onSubmit(submitData)
    }
  }

  if (!isOpen) return null

  const users = usersData?.data?.items || []

  return (
    <>
      <Modal isOpen={isOpen} onClose={onClose} className="max-w-4xl">
        <div>
          <div className="flex justify-between items-center p-6 border-b border-gray-200">
            <div className="flex items-center gap-3">
              <Send className="w-6 h-6 text-green-600" />
              <div>
                <h2 className="text-xl font-semibold text-gray-900">Send Email Campaign</h2>
                <p className="text-sm text-gray-600">{selectedCount} people selected</p>
              </div>
            </div>
            <button onClick={onClose} className="p-1 rounded-md hover:bg-gray-100 transition-colors">
              <X className="h-4 w-4 text-gray-500" />
            </button>
          </div>

          <form onSubmit={handleSubmit} className="p-6 space-y-6 overflow-y-auto max-h-[calc(90vh-120px)]">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              {/* Campaign Name */}
              <div>
                <label className="block text-sm font-medium mb-2 text-gray-700">
                  Campaign Name <span className="text-red-500">*</span>
                </label>
                <input
                  type="text"
                  value={formData.name}
                  onChange={(e) => handleInputChange("name", e.target.value)}
                  className={`w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 ${
                    errors.name ? "border-red-500" : "border-gray-300"
                  }`}
                  placeholder="Enter campaign name"
                />
                {errors.name && <p className="text-red-500 text-sm mt-1">{errors.name}</p>}
              </div>

              {/* Email Account */}
              <div>
                <label className="block text-sm font-medium mb-2 text-gray-700">
                  From Account <span className="text-red-500">*</span>
                </label>
                <select
                  value={formData.email_account_id || ""}
                  onChange={(e) => handleInputChange("email_account_id", Number.parseInt(e.target.value))}
                  className={`w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white ${
                    errors.email_account_id ? "border-red-500" : "border-gray-300"
                  }`}
                >
                  <option value="">Select email account</option>
                  {emailAccounts.map((account) => (
                    <option key={account.id} value={account.id}>
                      {account.email} ({account.provider})
                    </option>
                  ))}
                </select>
                {errors.email_account_id && <p className="text-red-500 text-sm mt-1">{errors.email_account_id}</p>}
              </div>
            </div>

            {/* Description */}
            <div>
              <label className="block text-sm font-medium mb-2 text-gray-700">Description</label>
              <textarea
                value={formData.description}
                onChange={(e) => handleInputChange("description", e.target.value)}
                rows={3}
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
                placeholder="Optional campaign description"
              />
            </div>

            {/* Subject with Template Button */}
            <div>
              <div className="flex items-center justify-between mb-2">
                <label className="block text-sm font-medium text-gray-700">
                  Email Subject <span className="text-red-500">*</span>
                </label>
                <button
                  type="button"
                  onClick={() => setShowTemplateModal(true)}
                  className="flex items-center px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200"
                >
                  <Template className="h-4 w-4 mr-1" />
                  Use Template
                </button>
              </div>
              <input
                type="text"
                value={formData.subject}
                onChange={(e) => handleInputChange("subject", e.target.value)}
                className={`w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 ${
                  errors.subject ? "border-red-500" : "border-gray-300"
                }`}
                placeholder="Enter email subject"
              />
              {errors.subject && <p className="text-red-500 text-sm mt-1">{errors.subject}</p>}

              {selectedTemplate && (
                <div className="mt-2 flex items-center text-sm text-blue-600">
                  <Template className="h-4 w-4 mr-1" />
                  Using template: {selectedTemplate.name}
                  <button
                    type="button"
                    onClick={() => {
                      setSelectedTemplate(null)
                      handleInputChange("subject", "")
                      handleInputChange("body", "")
                    }}
                    className="ml-2 text-red-500 hover:text-red-700"
                  >
                    <X className="h-4 w-4" />
                  </button>
                </div>
              )}
            </div>

            {/* Email Content */}
            <div>
              <label className="block text-sm font-medium mb-2 text-gray-700">
                Message <span className="text-red-500">*</span>
              </label>
              <RichTextEditor
                value={formData.body}
                onChange={(value) => handleInputChange("body", value)}
                onMentionSelect={handleMentionSelect}
                users={users}
                placeholder="Type your email message here..."
                className="min-h-[300px]"
              />
              {errors.body && <p className="text-red-500 text-sm mt-1">{errors.body}</p>}
            </div>

            {/* Mentioned Users */}
            {mentionedUsers.length > 0 && (
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
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

            {/* Email Options */}
            <div className="bg-gray-50 p-4 rounded-lg">
              <h3 className="text-sm font-medium text-gray-700 mb-3">Email Options</h3>
              <div className="flex items-center">
                <input
                  type="checkbox"
                  id="use_all_emails"
                  checked={formData.use_all_emails}
                  onChange={(e) => handleInputChange("use_all_emails", e.target.checked)}
                  className="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                />
                <label htmlFor="use_all_emails" className="ml-2 text-sm text-gray-700">
                  Send to all email addresses (if person has multiple emails)
                </label>
              </div>
            </div>

            {/* Campaign Preview */}
            <div className="bg-green-50 p-4 rounded-lg">
              <h3 className="text-sm font-medium text-green-800 mb-2">Campaign Summary</h3>
              <div className="text-sm text-green-700 space-y-1">
                <p>
                  <strong>Recipients:</strong> {selectedCount} people
                </p>
                <p>
                  <strong>Action:</strong> Send Email Campaign Immediately
                </p>
                {formData.email_account_id && (
                  <p>
                    <strong>From:</strong> {emailAccounts.find((acc) => acc.id === formData.email_account_id)?.email}
                  </p>
                )}
              </div>
            </div>

            {/* Modal Actions */}
            <div className="flex justify-end gap-3 pt-6 border-t border-gray-200">
              <button
                type="button"
                onClick={onClose}
                className="px-4 py-2 border border-gray-300 text-gray-700 rounded-md bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
                disabled={isLoading}
              >
                Cancel
              </button>
              <button
                type="submit"
                disabled={isLoading || emailAccounts.length === 0}
                className={`flex items-center px-4 py-2 border border-transparent rounded-md text-white focus:outline-none focus:ring-2 focus:ring-green-500 bg-green-600 hover:bg-green-700 ${
                  isLoading || emailAccounts.length === 0 ? "opacity-50 cursor-not-allowed" : ""
                }`}
              >
                {isLoading ? (
                  <>
                    <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin mr-2"></div>
                    Sending Campaign...
                  </>
                ) : (
                  <>
                    <Send className="h-4 w-4 mr-2" />
                    Send Campaign
                  </>
                )}
              </button>
            </div>
          </form>
        </div>
      </Modal>

      {/* Template Selection Modal */}
      <EmailTemplateSelectionModal
        isOpen={showTemplateModal}
        onClose={() => setShowTemplateModal(false)}
        onSelect={handleTemplateSelect}
      />
    </>
  )
}
