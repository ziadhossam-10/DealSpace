"use client"

import { useState } from "react"
import { Plus, AlertCircle, CheckCircle, X } from "lucide-react"
import { BASE_URL } from "../../utils/helpers"

interface ConnectAnotherEmailModalProps {
  isOpen: boolean
  onClose: () => void
  onAccountConnected?: () => void
}

export default function ConnectAnotherEmailModal({
  isOpen,
  onClose,
  onAccountConnected,
}: ConnectAnotherEmailModalProps) {
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState("")
  const [success, setSuccess] = useState("")

  if (!isOpen) return null

  const connectAccount = async (provider: "google" | "microsoft") => {
    setLoading(true)
    setError("")

    try {
      const response = await fetch(`${BASE_URL}/oauth/${provider}`, {
        headers: {
          Authorization: `Bearer ${localStorage.getItem("token")}`,
          Accept: "application/json",
        },
      })
      const data = await response.json()

      if (data.auth_url) {
        // Open popup window for OAuth
        const popup = window.open(data.auth_url, "oauth", "width=600,height=700,scrollbars=yes,resizable=yes")

        // Listen for messages from the popup
        const messageListener = (event: MessageEvent) => {
          if (event.origin !== window.location.origin) return

          if (event.data.success) {
            setSuccess(`${provider} account connected successfully!`)
            setTimeout(() => {
              setSuccess("")
              onClose()
              onAccountConnected?.()
            }, 2000)
          } else if (event.data.error) {
            setError(event.data.error)
          }

          window.removeEventListener("message", messageListener)
          setLoading(false)
        }

        window.addEventListener("message", messageListener)

        // Fallback: Check if popup is closed without message
        const checkClosed = setInterval(() => {
          if (popup?.closed) {
            clearInterval(checkClosed)
            window.removeEventListener("message", messageListener)
            if (loading) {
              setTimeout(() => {
                onAccountConnected?.()
                onClose()
                setLoading(false)
              }, 1000)
            }
          }
        }, 1000)
      }
    } catch (err) {
      setError(`Failed to connect ${provider} account`)
      setLoading(false)
    }
  }

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div className="w-full max-w-md bg-white rounded-lg shadow-lg border border-gray-200 m-4">
        <div className="flex items-center justify-between p-6 border-b border-gray-200">
          <h2 className="text-xl font-semibold text-gray-900">Connect Another Email</h2>
          <button onClick={onClose} className="text-gray-400 hover:text-gray-600 transition-colors">
            <X className="h-5 w-5" />
          </button>
        </div>

        <div className="p-6 space-y-4">
          {/* Success Message */}
          {success && (
            <div className="border border-green-200 bg-green-50 rounded-lg p-4 flex items-center">
              <CheckCircle className="h-4 w-4 text-green-600 mr-2 flex-shrink-0" />
              <span className="text-green-700">{success}</span>
            </div>
          )}

          {/* Error Message */}
          {error && (
            <div className="border border-red-200 bg-red-50 rounded-lg p-4 flex items-center">
              <AlertCircle className="h-4 w-4 text-red-600 mr-2 flex-shrink-0" />
              <span className="text-red-700">{error}</span>
            </div>
          )}

          {/* Connect Buttons */}
          <div className="space-y-3">
            <button
              onClick={() => connectAccount("google")}
              disabled={loading}
              className="w-full bg-red-500 hover:bg-red-600 disabled:opacity-50 disabled:cursor-not-allowed text-white font-medium py-3 px-4 rounded-lg transition-colors flex items-center justify-center"
            >
              <Plus className="mr-2 h-4 w-4" />
              Connect Gmail
            </button>

            {/* <button
              onClick={() => connectAccount("microsoft")}
              disabled={loading}
              className="w-full bg-blue-500 hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed text-white font-medium py-3 px-4 rounded-lg transition-colors flex items-center justify-center"
            >
              <Plus className="mr-2 h-4 w-4" />
              Connect Outlook
            </button> */}
          </div>

          <div className="text-center text-sm text-gray-500">Add another email account to manage more inboxes</div>
        </div>
      </div>

      {/* Loading Overlay */}
      {loading && (
        <div className="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-60">
          <div className="bg-white p-6 rounded-lg shadow-lg">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mx-auto"></div>
            <p className="mt-2 text-gray-600">Connecting account...</p>
          </div>
        </div>
      )}
    </div>
  )
}
