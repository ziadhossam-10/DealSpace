"use client"

import type React from "react"
import { useState, useEffect } from "react"
import { X, LayoutTemplateIcon as Template, Send } from "lucide-react"
import { RichTextEditor } from "./richTextEditor" 
import { useGetUsersQuery } from "../../users/usersApi"
import { useGetActiveEmailAccountsQuery } from "../emailsApi"
import type { EmailTemplate } from "../../../types/emailTemplates"
import type { EmailAccount } from "../../../types/emails"
import EmailTemplateSelectionModal from "./EmailTemplateSelectionModal"
import { Link } from "react-router"

interface SendEmailModalProps {
  isOpen: boolean
  onClose: () => void
  onSend: (emailData: {
    account_id: number
    to_email: string
    subject: string
    body: string
    body_html: string
  }) => void
  personEmail?: string
  personName?: string
}

const SendEmailModal: React.FC<SendEmailModalProps> = ({
  isOpen,
  onClose,
  onSend,
  personEmail = "",
  personName = "",
}) => {
  const [selectedAccount, setSelectedAccount] = useState<EmailAccount | null>(null)
  const [toEmail, setToEmail] = useState(personEmail)
  const [subject, setSubject] = useState("")
  const [body, setBody] = useState("")
  const [mentionedUsers, setMentionedUsers] = useState<number[]>([])
  const [showTemplateModal, setShowTemplateModal] = useState(false)
  const [selectedTemplate, setSelectedTemplate] = useState<EmailTemplate | null>(null)

  const { data: usersData } = useGetUsersQuery({ page: 1, per_page: 1000 })
  const { data: accountsData, isLoading: isLoadingAccounts } = useGetActiveEmailAccountsQuery()

  useEffect(() => {
    if (isOpen) {
      setToEmail(personEmail)
      setSubject("")
      setBody("")
      setMentionedUsers([])
      setSelectedTemplate(null)
      // Auto-select first active account if available
      if (accountsData?.data && accountsData.data.length > 0 && !selectedAccount) {
        setSelectedAccount(accountsData.data[0])
      }
    }
  }, [isOpen, personEmail, accountsData, selectedAccount])

  const handleMentionSelect = (userId: number) => {
    if (!mentionedUsers.includes(userId)) {
      setMentionedUsers((prev) => [...prev, userId])
    }
  }

  const handleTemplateSelect = (template: EmailTemplate) => {
    setSelectedTemplate(template)
    setSubject(template.subject)
    setBody(template.body)
    setShowTemplateModal(false)
  }

  const handleSend = () => {
    if (!selectedAccount || !toEmail || !subject || !body) {
      return
    }

    onSend({
      account_id: selectedAccount.id,
      to_email: toEmail,
      subject,
      body: body.replace(/<[^>]*>/g, ""), // Strip HTML for plain text body
      body_html: body,
    })

    onClose()
  }

  if (!isOpen) return null

  const users = usersData?.data?.items || []
  const accounts = accountsData?.data || []

  return (
    <>
      <div className="fixed inset-0 bg-black bg-opacity-50 z-50 flex justify-center items-center p-4">
        <div className="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-hidden">
          <div className="flex justify-between items-center p-6 border-b border-gray-200">
            <h2 className="text-xl font-semibold text-gray-900">Send Email {personName && `to ${personName}`}</h2>
            <button onClick={onClose} className="p-1 rounded-md hover:bg-gray-100 transition-colors">
              <X className="h-4 w-4 text-gray-500" />
            </button>
          </div>

          <div className="p-6 space-y-6 overflow-y-auto max-h-[calc(90vh-120px)]">
            {/* Email Account Selection */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">From Account*</label>
              {isLoadingAccounts ? (
                <div className="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 text-gray-500">
                  Loading accounts...
                </div>
              ) : accounts.length > 0 ? (
                <select
                  value={selectedAccount?.id || ""}
                  onChange={(e) => {
                    const account = accounts.find((acc) => acc.id === Number(e.target.value))
                    setSelectedAccount(account || null)
                  }}
                  className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  required
                >
                  <option value="">Select email account</option>
                  {accounts.map((account) => (
                    <option key={account.id} value={account.id}>
                      {account.email} ({account.provider})
                    </option>
                  ))}
                </select>
              ) : (
                <div className="w-full px-3 py-2 border border-red-300 rounded-md bg-red-50 text-red-600">
                  No active email accounts found. Please connect an email account first. 
                  <Link
                    to="/admin/manage-emails"
                    className="ml-1 text-blue-600 hover:underline"
                  >
                    Connect Email Account
                  </Link>
                </div>
              )}
            </div>

            {/* To Email */}
            <div>
              <label htmlFor="toEmail" className="block text-sm font-medium text-gray-700 mb-1">
                To*
              </label>
              <input
                id="toEmail"
                type="email"
                value={toEmail}
                onChange={(e) => setToEmail(e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="recipient@example.com"
                required
              />
            </div>

            {/* Subject with Template Button */}
            <div>
              <div className="flex items-center justify-between mb-1">
                <label htmlFor="subject" className="block text-sm font-medium text-gray-700">
                  Subject*
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
                id="subject"
                type="text"
                value={subject}
                onChange={(e) => setSubject(e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="Email subject"
                required
              />
              {selectedTemplate && (
                <div className="mt-2 flex items-center text-sm text-blue-600">
                  <Template className="h-4 w-4 mr-1" />
                  Using template: {selectedTemplate.name}
                  <button
                    type="button"
                    onClick={() => {
                      setSelectedTemplate(null)
                      setSubject("")
                      setBody("")
                    }}
                    className="ml-2 text-red-500 hover:text-red-700"
                  >
                    <X className="h-4 w-4" />
                  </button>
                </div>
              )}
            </div>

            {/* Body */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Message*</label>
              <RichTextEditor
                value={body}
                onChange={setBody}
                onMentionSelect={handleMentionSelect}
                users={users}
                placeholder="Type your email message here..."
                className="min-h-[300px]"
              />
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
          </div>

          {/* Actions */}
          <div className="flex justify-end space-x-3 p-6 border-t border-gray-200">
            <button
              type="button"
              onClick={onClose}
              className="px-4 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              Cancel
            </button>
            <button
              onClick={handleSend}
              disabled={!selectedAccount || !toEmail || !subject || !body || accounts.length === 0}
              className={`flex items-center px-4 py-2 border border-transparent rounded-md text-white focus:outline-none focus:ring-2 focus:ring-blue-500 ${
                !selectedAccount || !toEmail || !subject || !body || accounts.length === 0
                  ? "bg-gray-400 cursor-not-allowed"
                  : "bg-blue-600 hover:bg-blue-700"
              }`}
            >
              <Send className="h-4 w-4 mr-2" />
              Send Email
            </button>
          </div>
        </div>
      </div>

      {/* Template Selection Modal */}
      <EmailTemplateSelectionModal
        isOpen={showTemplateModal}
        onClose={() => setShowTemplateModal(false)}
        onSelect={handleTemplateSelect}
      />
    </>
  )
}

export default SendEmailModal
