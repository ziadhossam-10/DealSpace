"use client"
import { useState } from "react"
import { Mail, Send, Eye, Clock, ArrowUpRight, ArrowDownLeft, Star } from "lucide-react"
import { useGetEmailsQuery, useSendEmailMutation } from "../emailsApi"
import SendEmailModal from "./SendEmailModal"

interface PersonEmailsProps {
  personId: number
  personEmails?: Array<{ value: string; is_primary: boolean }>
  personName?: string
  onToast: (message: string, type?: "success" | "error") => void
}

export const PersonEmails = ({ personId, personEmails = [], personName = "", onToast }: PersonEmailsProps) => {
  const [sendEmailModal, setSendEmailModal] = useState<{ isOpen: boolean; email: string }>({
    isOpen: false,
    email: "",
  })
  const [selectedEmail, setSelectedEmail] = useState<any>(null)
  const [showEmailDetails, setShowEmailDetails] = useState(false)

  const { data: emailsData, isLoading, refetch } = useGetEmailsQuery({ person_id: personId })
  const [sendEmail, { isLoading: isSending }] = useSendEmailMutation()

  const handleSendEmail = async (emailData: {
    account_id: number
    to_email: string
    subject: string
    body: string
    body_html: string
  }) => {
    try {
      await sendEmail({
        ...emailData,
        person_id: personId,
      }).unwrap()
      onToast("Email sent successfully")
      refetch()
    } catch (error: any) {
      onToast("Failed to send email", "error")
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

  const getPrimaryEmail = () => {
    const primary = personEmails.find((email) => email.is_primary)
    return primary?.value || personEmails[0]?.value || ""
  }

  const truncateText = (text: string, maxLength: number) => {
    if (text.length <= maxLength) return text
    return text.substring(0, maxLength) + "..."
  }

  return (
    <div className="space-y-4">
      {/* Send Email Button */}
      <div className="flex justify-between items-center">
        <h3 className="text-lg font-medium flex items-center">
          <Mail size={20} className="mr-2" />
          Emails
        </h3>
        <button
          onClick={() => setSendEmailModal({ isOpen: true, email: getPrimaryEmail() })}
          className="flex items-center px-3 py-1.5 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700"
          disabled={!getPrimaryEmail()}
        >
          <Send size={16} className="mr-1" />
          Send Email
        </button>
      </div>

      {/* Quick Send Options */}
      {personEmails.length > 0 && (
        <div className="bg-gray-50 rounded-lg p-3">
          <div className="text-sm font-medium text-gray-700 mb-2">Quick Send To:</div>
          <div className="flex flex-wrap gap-2">
            {personEmails.map((email, index) => (
              <button
                key={index}
                onClick={() => setSendEmailModal({ isOpen: true, email: email.value })}
                className="flex items-center px-3 py-1 text-sm bg-white border border-gray-300 rounded-md hover:bg-gray-100"
              >
                <Mail size={14} className="mr-1" />
                {email.value}
                {email.is_primary && <Star size={12} className="ml-1 text-yellow-500" />}
              </button>
            ))}
          </div>
        </div>
      )}

      {/* Emails List */}
      <div className="space-y-3">
        {isLoading ? (
          <div className="text-center py-4 text-gray-500">Loading emails...</div>
        ) : emailsData?.data?.items?.length ? (
          emailsData.data.items.map((email) => (
            <div key={email.id} className="border border-gray-200 rounded-lg p-4 bg-[#cccccc0a] shadow-sm">
              <div className="flex justify-between items-start mb-2">
                <div className="flex items-center space-x-2">
                  <div className="flex items-center space-x-1">
                    {email.is_incoming ? (
                      <ArrowDownLeft size={16} className="text-green-600" />
                    ) : (
                      <ArrowUpRight size={16} className="text-blue-600" />
                    )}
                    <span className="text-sm font-medium text-gray-600">{email.is_incoming ? "Received" : "Sent"}</span>
                  </div>
                  {email.is_starred && <Star size={14} className="text-yellow-500" />}
                </div>
                <button
                  onClick={() => {
                    setSelectedEmail(email)
                    setShowEmailDetails(true)
                  }}
                  className="p-1 text-gray-400 hover:text-blue-500 rounded"
                >
                  <Eye size={14} />
                </button>
              </div>

              <h4 className="font-medium text-gray-900 mb-1">{email.subject}</h4>

              <div className="text-sm text-gray-600 mb-2">
                <div className="flex items-center justify-between">
                  <div>
                    <span className="font-medium">From:</span> {email.from_email}
                    {email.from_name && ` (${email.from_name})`}
                  </div>
                  <div>
                    <span className="font-medium">To:</span> {email.to_email}
                    {email.to_name && ` (${email.to_name})`}
                  </div>
                </div>
              </div>

              {email.body && (
                <div className="text-gray-700 mb-3 text-sm">
                  {email.body_html ? (
                    <div
                      className="prose prose-sm max-w-none"
                      dangerouslySetInnerHTML={{
                        __html: truncateText(email.body_html.replace(/<[^>]*>/g, ""), 150),
                      }}
                    />
                  ) : (
                    <p>{truncateText(email.body, 150)}</p>
                  )}
                </div>
              )}

              <div className="flex items-center justify-between text-sm text-gray-500">
                <div className="flex items-center space-x-1">
                  <Clock size={14} />
                  <span>
                    {email.is_incoming
                      ? email.received_at && formatDate(email.received_at)
                      : email.sent_at && formatDate(email.sent_at)}
                  </span>
                </div>
                <div className="flex items-center space-x-2">
                  {email.attachments && <span className="text-xs bg-gray-100 px-2 py-1 rounded">Has Attachments</span>}
                  <span className="text-xs bg-gray-100 px-2 py-1 rounded">
                    {email.is_incoming ? "Incoming" : "Outgoing"}
                  </span>
                </div>
              </div>
            </div>
          ))
        ) : (
          <div className="text-center py-8 text-gray-500">
            <Mail size={48} className="mx-auto mb-2 text-gray-300" />
            <p>No emails yet</p>
            <p className="text-sm">Send your first email to get started</p>
          </div>
        )}
      </div>

      {/* Send Email Modal */}
      <SendEmailModal
        isOpen={sendEmailModal.isOpen}
        onClose={() => setSendEmailModal({ isOpen: false, email: "" })}
        onSend={handleSendEmail}
        personEmail={sendEmailModal.email}
        personName={personName}
      />

      {/* Email Details Modal */}
      {showEmailDetails && selectedEmail && (
        <div className="fixed inset-0 bg-black bg-opacity-50 z-50 flex justify-center items-center p-4">
          <div className="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-hidden">
            <div className="flex justify-between items-center p-6 border-b border-gray-200">
              <h2 className="text-xl font-semibold text-gray-900">Email Details</h2>
              <button
                onClick={() => setShowEmailDetails(false)}
                className="p-1 rounded-md hover:bg-gray-100 transition-colors"
              >
                <Eye className="h-4 w-4 text-gray-500" />
              </button>
            </div>
            <div className="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
              <div className="space-y-4">
                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">From:</label>
                    <div className="p-3 bg-gray-50 rounded-md">
                      {selectedEmail.from_email}
                      {selectedEmail.from_name && ` (${selectedEmail.from_name})`}
                    </div>
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">To:</label>
                    <div className="p-3 bg-gray-50 rounded-md">
                      {selectedEmail.to_email}
                      {selectedEmail.to_name && ` (${selectedEmail.to_name})`}
                    </div>
                  </div>
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Subject:</label>
                  <div className="p-3 bg-gray-50 rounded-md">{selectedEmail.subject}</div>
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Date:</label>
                  <div className="p-3 bg-gray-50 rounded-md">
                    {selectedEmail.is_incoming
                      ? selectedEmail.received_at && formatDate(selectedEmail.received_at)
                      : selectedEmail.sent_at && formatDate(selectedEmail.sent_at)}
                  </div>
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Message:</label>
                  <div className="p-4 bg-gray-50 rounded-md">
                    {selectedEmail.body_html ? (
                      <div className="prose max-w-none" dangerouslySetInnerHTML={{ __html: selectedEmail.body_html }} />
                    ) : (
                      <div className="whitespace-pre-wrap">{selectedEmail.body}</div>
                    )}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}
