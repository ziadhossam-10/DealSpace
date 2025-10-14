"use client"

import type React from "react"
import { useState, useEffect } from "react"
import { X, LayoutTemplate, CheckCircle, XCircle } from "lucide-react"
import { useGetTextMessagesQuery, type CreateTextMessageRequest } from "../textMessagesApi"
import TextMessageTemplateSelectionModal from "./TextMessageTemplateSelectionModal"
import type { TextMessageTemplate } from "../../../types/textMessageTemplates"
import { MessageSquare, Clock, User, MessageCircle, ArrowDown, ArrowUp, ExternalLink } from "lucide-react"
import {
  useCreateTextMessageMutation,
} from "../textMessagesApi"


interface PersonTextsProps {
  personId: number
  onToast: (message: string, type?: "success" | "error") => void
}

interface TextMessageDialogProps {
  isOpen: boolean
  onClose: () => void
  onSubmit: (data: Omit<CreateTextMessageRequest, "person_id">) => void
  personId: number
}

const TextMessageDialog = ({ isOpen, onClose, onSubmit, personId }: TextMessageDialogProps) => {
  const [message, setMessage] = useState("")
  const [toNumber, setToNumber] = useState("")
  const [externalLabel, setExternalLabel] = useState("")
  const [externalUrl, setExternalUrl] = useState("")
  const [isSending, setIsSending] = useState(false)
  const [sendSuccess, setSendSuccess] = useState<boolean | null>(null)

  const [showTemplateModal, setShowTemplateModal] = useState(false)
  const [selectedTemplate, setSelectedTemplate] = useState<TextMessageTemplate | null>(null)

  useEffect(() => {
    if (isOpen) {
      setMessage("")
      setToNumber("")
      setExternalLabel("")
      setExternalUrl("")
      setSendSuccess(null)
      setSelectedTemplate(null)
    }
  }, [isOpen])

  const handleTemplateSelect = (template: TextMessageTemplate) => {
    setSelectedTemplate(template)
    setMessage(template.message)
    setShowTemplateModal(false)
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setIsSending(true)
    setSendSuccess(null)

    try {
      await onSubmit({
        message,
        to_number: toNumber,
        external_label: externalLabel || undefined,
        external_url: externalUrl || undefined,
      })
      setSendSuccess(true)
      setTimeout(() => {
        onClose()
      }, 1500)
    } catch (error) {
      console.error("Failed to send message:", error)
      setSendSuccess(false)
    } finally {
      setIsSending(false)
    }
  }

  if (!isOpen) return null

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div className="bg-white rounded-lg shadow-lg w-full max-w-2xl p-6 max-h-[90vh] overflow-y-auto">
        <div className="flex justify-between items-center mb-4">
          <h3 className="text-lg font-medium">Send Message</h3>
          <button onClick={onClose} className="text-gray-400 hover:text-gray-600">
            <X size={20} />
          </button>
        </div>

        {sendSuccess === true && (
          <div className="rounded-md bg-green-50 p-4 mb-4">
            <div className="flex">
              <div className="flex-shrink-0">
                <CheckCircle className="h-5 w-5 text-green-400" />
              </div>
              <div className="ml-3">
                <p className="text-sm font-medium text-green-800">Message sent successfully!</p>
              </div>
            </div>
          </div>
        )}

        {sendSuccess === false && (
          <div className="rounded-md bg-red-50 p-4 mb-4">
            <div className="flex">
              <div className="flex-shrink-0">
                <XCircle className="h-5 w-5 text-red-400" />
              </div>
              <div className="ml-3">
                <p className="text-sm font-medium text-red-800">Failed to send message. Please try again.</p>
              </div>
            </div>
          </div>
        )}

        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="space-y-2">
            <label htmlFor="toNumber" className="block text-sm font-medium text-gray-700">
              Recipient Phone Number
            </label>
            <input
              id="toNumber"
              type="tel"
              value={toNumber}
              onChange={(e) => setToNumber(e.target.value)}
              placeholder="+1234567890"
              className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              required
            />
          </div>

          <div className="space-y-2">
            <div className="flex items-center justify-between">
              <label htmlFor="message" className="block text-sm font-medium text-gray-700">
                Message
              </label>
              <button
                type="button"
                onClick={() => setShowTemplateModal(true)}
                className="flex items-center px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200"
              >
                <LayoutTemplate className="h-4 w-4 mr-1" />
                Use Template
              </button>
            </div>
            <textarea
              id="message"
              value={message}
              onChange={(e) => setMessage(e.target.value)}
              placeholder="Type your message here..."
              rows={4}
              className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              required
            />
            <div className="text-xs text-gray-500 text-right">{message.length} characters</div>
            {selectedTemplate && (
              <div className="mt-2 flex items-center text-sm text-blue-600">
                <LayoutTemplate className="h-4 w-4 mr-1" />
                Using template: {selectedTemplate.name}
                <button
                  type="button"
                  onClick={() => {
                    setSelectedTemplate(null)
                    setMessage("")
                  }}
                  className="ml-2 text-red-500 hover:text-red-700"
                >
                  <X className="h-4 w-4" />
                </button>
              </div>
            )}
          </div>

          <div className="border-t pt-4">
            <h4 className="text-sm font-medium text-gray-700 mb-3">Optional Link Attachment</h4>
            <div className="space-y-3">
              <div className="space-y-2">
                <label htmlFor="externalUrl" className="block text-sm font-medium text-gray-700">
                  Link URL
                </label>
                <input
                  id="externalUrl"
                  type="url"
                  value={externalUrl}
                  onChange={(e) => setExternalUrl(e.target.value)}
                  placeholder="https://example.com/document"
                  className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>
              <div className="space-y-2">
                <label htmlFor="externalLabel" className="block text-sm font-medium text-gray-700">
                  Link Label
                </label>
                <input
                  id="externalLabel"
                  type="text"
                  value={externalLabel}
                  onChange={(e) => setExternalLabel(e.target.value)}
                  placeholder="e.g., Property Details, Contract, Photos"
                  className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
                <p className="text-xs text-gray-500">This label will be shown with the link</p>
              </div>
            </div>
          </div>

          <div className="flex justify-end space-x-2 pt-4">
            <button
              type="button"
              onClick={onClose}
              className="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={isSending}
              className="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
            >
              {isSending ? "Sending..." : "Send Message"}
            </button>
          </div>
        </form>

        {/* Template Selection Modal */}
        <TextMessageTemplateSelectionModal
          isOpen={showTemplateModal}
          onClose={() => setShowTemplateModal(false)}
          onSelect={handleTemplateSelect}
        />
      </div>
    </div>
  )
}

export const PersonTexts = ({ personId, onToast }: PersonTextsProps) => {
  const [textDialog, setTextDialog] = useState<{ isOpen: boolean }>({
    isOpen: false,
  })

  const { data: textsData, isLoading, refetch } = useGetTextMessagesQuery({ person_id: personId })
  const [createTextMessage] = useCreateTextMessageMutation()

  const handleCreateTextMessage = async (data: Omit<CreateTextMessageRequest, "person_id">) => {
    try {
      await createTextMessage({ ...data, person_id: personId }).unwrap()
      onToast("Message sent successfully")
      refetch()
    } catch (error) {
      onToast("Failed to send message", "error")
      console.error(error)
    }
  }

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString("en-US", {
      year: "numeric",
      month: "short",
      day: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    })
  }

  return (
    <div className="space-y-4">
      {/* Send Message Button */}
      <div className="flex justify-between items-center">
        <h3 className="text-lg font-medium flex items-center">
          <MessageCircle size={20} className="mr-2" />
          Messages
        </h3>
        <button
          onClick={() => setTextDialog({ isOpen: true })}
          className="flex items-center px-3 py-1.5 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700"
        >
          <MessageSquare size={16} className="mr-1" />
          Send Message
        </button>
      </div>

      {/* Messages List */}
      <div className="space-y-3">
        {isLoading ? (
          <div className="text-center py-4 text-gray-500">Loading messages...</div>
        ) : textsData?.data?.items?.length ? (
          textsData.data.items.map((textMessage) => (
            <div key={textMessage.id} className="border border-gray-200 rounded-lg p-4 bg-[#cccccc0a] shadow-sm">
              <div className="flex justify-between items-start mb-3">
                <div className="flex items-center space-x-2">
                  {textMessage.is_incoming ? (
                    <ArrowDown size={16} className="text-green-600" />
                  ) : (
                    <ArrowUp size={16} className="text-blue-600" />
                  )}
                  <span className="text-sm font-medium text-gray-700">
                    {textMessage.is_incoming ? "Received" : "Sent"}
                  </span>
                  {
                    textMessage.is_incoming ? (
                      <span className="text-xs text-gray-500">From: {textMessage.from_number}</span>
                    ) : (
                      <span className="text-xs text-gray-500">To: {textMessage.to_number}</span>
                    )
                  }
                </div>
                {textMessage.external_url && textMessage.external_label && (
                  <button
                    onClick={() => window.open(textMessage.external_url!, "_blank")}
                    className="flex items-center space-x-1 text-blue-600 hover:text-blue-800 text-sm"
                  >
                    <ExternalLink size={14} />
                    <span>{textMessage.external_label}</span>
                  </button>
                )}
              </div>

              <div className={`rounded-lg p-3 mb-3 ${textMessage.is_incoming ? "bg-gray-50" : "bg-blue-50"}`}>
                <p className="text-gray-800 whitespace-pre-wrap">{textMessage.message}</p>
              </div>

              {textMessage.external_url && textMessage.external_label && (
                <div className="mb-3 p-2 bg-gray-50 rounded border-l-4 border-blue-500">
                  <div className="flex items-center space-x-2">
                    <ExternalLink size={14} className="text-blue-600" />
                    <span className="text-sm font-medium text-blue-600">{textMessage.external_label}</span>
                  </div>
                  <p className="text-xs text-gray-500 mt-1 truncate">{textMessage.external_url}</p>
                </div>
              )}

              <div className="flex items-center justify-between text-sm text-gray-500">
                <div className="flex items-center space-x-4">
                  <div className="flex items-center space-x-1">
                    <User size={14} />
                    <span>By {textMessage.user.name}</span>
                  </div>
                  <div className="flex items-center space-x-1">
                    <Clock size={14} />
                    <span>{formatDate(textMessage.created_at)}</span>
                  </div>
                </div>
                <div className="flex items-center space-x-1">
                  <MessageSquare size={12} />
                  <span>{textMessage.message.length} chars</span>
                </div>
              </div>
            </div>
          ))
        ) : (
          <div className="text-center py-8 text-gray-500">
            <MessageCircle size={48} className="mx-auto mb-2 text-gray-300" />
            <p>No messages yet</p>
            <p className="text-sm">Send your first message to get started</p>
          </div>
        )}
      </div>

      {/* Send Message Dialog */}
      <TextMessageDialog
        isOpen={textDialog.isOpen}
        onClose={() => setTextDialog({ isOpen: false })}
        onSubmit={handleCreateTextMessage}
        personId={personId}
      />
    </div>
  )
}
