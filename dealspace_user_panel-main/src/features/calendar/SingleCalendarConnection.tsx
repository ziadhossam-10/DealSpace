"use client"
import { useState, useEffect } from "react"
import { Calendar, Mail, AlertCircle, CheckCircle, RefreshCw } from "lucide-react"
import { BASE_URL } from "../../utils/helpers"
import { useAppSelector } from "../../app/hooks"

interface CalendarAccount {
  id: string
  email: string
  provider: "google" | "outlook"
  calendar_name?: string
  is_active: boolean
  last_sync_at?: string
}

interface SingleCalendarConnectionProps {
  onConnectionChange?: (account: CalendarAccount | null) => void
}

const SingleCalendarConnection = ({ onConnectionChange }: SingleCalendarConnectionProps) => {
  const [account, setAccount] = useState<CalendarAccount | null>(null)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [success, setSuccess] = useState<string | null>(null)
  const [syncing, setSyncing] = useState(false)

  const token = useAppSelector((state) => state.auth.token)

  // Handle OAuth callback messages
  useEffect(() => {
    const handleMessage = (event: MessageEvent) => {
      if (event.origin !== window.location.origin) return

      const { success: isSuccess, error: errorMessage, provider, message } = event.data

      if (isSuccess) {
        setSuccess(message || `${provider === "google" ? "Google" : "Outlook"} Calendar connected successfully`)
        fetchAccount()
      } else if (errorMessage) {
        setError(errorMessage)
      }
    }

    window.addEventListener("message", handleMessage)
    return () => window.removeEventListener("message", handleMessage)
  }, [])

  // Fetch connected account
  const fetchAccount = async () => {
    try {
      setLoading(true)
      const response = await fetch(`${BASE_URL}/calendar-accounts`, {
        method: "GET",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
        },
      })

      const data = await response.json()

      if (data.success) {
        // Get the first active account or null
        const activeAccount = data.data.find((acc: CalendarAccount) => acc.is_active) || null
        setAccount(activeAccount)
        onConnectionChange?.(activeAccount)
      } else {
        setError(data.message || "Failed to fetch account")
      }
    } catch (err) {
      setError("Network error occurred")
    } finally {
      setLoading(false)
    }
  }

  // Connect to Google Calendar
  const connectGoogle = async () => {
    if (account) {
      setError("You can only connect one calendar account. Please disconnect the current one first.")
      return
    }

    try {
      setLoading(true)
      setError(null)

      const response = await fetch(`${BASE_URL}/calendars/google/auth`, {
        method: "GET",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
        },
      })

      const data = await response.json()

      if (data.success && data.auth_url) {
        const popup = window.open(
          data.auth_url,
          "google-calendar-auth",
          "width=600,height=700,scrollbars=yes,resizable=yes",
        )

        if (popup) {
          const checkClosed = setInterval(() => {
            if (popup.closed) {
              clearInterval(checkClosed)
              setTimeout(() => {
                fetchAccount()
              }, 1000)
            }
          }, 1000)
        } else {
          setError("Failed to open authentication window. Please check your popup blocker.")
        }
      } else {
        setError(data.message || "Failed to get authentication URL")
      }
    } catch (err) {
      setError("Failed to initiate Google Calendar connection")
    } finally {
      setLoading(false)
    }
  }

  // Connect to Outlook Calendar
  const connectOutlook = async () => {
    if (account) {
      setError("You can only connect one calendar account. Please disconnect the current one first.")
      return
    }

    try {
      setLoading(true)
      setError(null)

      const response = await fetch(`${BASE_URL}/calendars/outlook/auth`, {
        method: "GET",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
        },
      })

      const data = await response.json()

      if (data.success && data.auth_url) {
        const popup = window.open(
          data.auth_url,
          "outlook-calendar-auth",
          "width=600,height=700,scrollbars=yes,resizable=yes",
        )

        if (popup) {
          const checkClosed = setInterval(() => {
            if (popup.closed) {
              clearInterval(checkClosed)
              setTimeout(() => {
                fetchAccount()
              }, 1000)
            }
          }, 1000)
        } else {
          setError("Failed to open authentication window. Please check your popup blocker.")
        }
      } else {
        setError(data.message || "Failed to get authentication URL")
      }
    } catch (err) {
      setError("Failed to initiate Outlook Calendar connection")
    } finally {
      setLoading(false)
    }
  }

  // Disconnect account
  const disconnectAccount = async () => {
    if (!account) return

    try {
      const response = await fetch(`${BASE_URL}/calendar-accounts/${account.id}/disconnect`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
        },
      })

      const data = await response.json()

      if (data.success) {
        setSuccess("Calendar disconnected successfully")
        setAccount(null)
        onConnectionChange?.(null)
      } else {
        setError(data.message || "Failed to disconnect")
      }
    } catch (err) {
      setError("Network error occurred")
    }
  }

  // Sync calendar
  const syncCalendar = async () => {
    if (!account) return

    try {
      setSyncing(true)
      const response = await fetch(`${BASE_URL}/calendar-accounts/${account.id}/sync`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
        },
      })

      const data = await response.json()

      if (data.success) {
        setSuccess(`Synced ${data.synced_count?.events || 0} events successfully`)
        fetchAccount()
      } else {
        setError(data.message || "Sync failed")
      }
    } catch (err) {
      setError("Sync failed")
    } finally {
      setSyncing(false)
    }
  }

  // Auto-dismiss messages
  useEffect(() => {
    if (success) {
      const timer = setTimeout(() => setSuccess(null), 5000)
      return () => clearTimeout(timer)
    }
  }, [success])

  useEffect(() => {
    if (error) {
      const timer = setTimeout(() => setError(null), 5000)
      return () => clearTimeout(timer)
    }
  }, [error])

  // Load account on mount
  useEffect(() => {
    fetchAccount()
  }, [])

  return (
    <div className="w-full max-w-2xl bg-white rounded-lg border border-gray-200 shadow-sm">
      {/* Header */}
      <div className="p-6 border-b border-gray-200">
        <div className="flex items-center gap-2 mb-2">
          <Calendar className="w-5 h-5 text-blue-600" />
          <h2 className="text-xl font-semibold text-gray-900">Calendar Connection</h2>
        </div>
        <p className="text-sm text-gray-600">
          Connect your calendar to sync events automatically. Only one calendar can be connected at a time.
        </p>
      </div>

      {/* Content */}
      <div className="p-6 space-y-4">
        {/* Status Messages */}
        {error && (
          <div className="flex items-center gap-2 p-4 bg-red-50 border border-red-200 rounded-lg">
            <AlertCircle className="h-4 w-4 text-red-600 flex-shrink-0" />
            <span className="text-sm text-red-700">{error}</span>
          </div>
        )}

        {success && (
          <div className="flex items-center gap-2 p-4 bg-green-50 border border-green-200 rounded-lg">
            <CheckCircle className="h-4 w-4 text-green-600 flex-shrink-0" />
            <span className="text-sm text-green-700">{success}</span>
          </div>
        )}

        {/* Connected Account or Connection Options */}
        {account ? (
          <div className="p-4 border rounded-lg bg-green-50 border-green-200">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-3">
                <div className="w-3 h-3 bg-green-500 rounded-full" />
                <div>
                  <h4 className="font-medium text-gray-800">{account.calendar_name || account.email}</h4>
                  <p className="text-sm text-gray-600">
                    {account.provider === "google" ? "Google Calendar" : "Outlook Calendar"} • {account.email}
                  </p>
                  {account.last_sync_at && (
                    <p className="text-xs text-gray-500 mt-1">
                      Last synced: {new Date(account.last_sync_at).toLocaleString()}
                    </p>
                  )}
                </div>
              </div>

              <div className="flex items-center gap-2">
                <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                  Connected
                </span>
                <button
                  onClick={syncCalendar}
                  disabled={syncing}
                  className="inline-flex items-center px-3 py-1.5 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <RefreshCw className={`w-4 h-4 mr-1 ${syncing ? "animate-spin" : ""}`} />
                  {syncing ? "Syncing..." : "Sync"}
                </button>
                <button
                  onClick={disconnectAccount}
                  className="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                >
                  Disconnect
                </button>
              </div>
            </div>
          </div>
        ) : (
          <div className="space-y-3">
            <p className="text-sm text-gray-600">Choose a calendar provider to connect:</p>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
              <button
                onClick={connectGoogle}
                disabled={loading}
                className="flex items-center justify-center gap-2 h-12 px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <Calendar className="w-5 h-5" />
                Connect Google Calendar
              </button>
              {/* <button
                onClick={connectOutlook}
                disabled={loading}
                className="flex items-center justify-center gap-2 h-12 px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <Mail className="w-5 h-5" />
                Connect Outlook Calendar
              </button> */}
            </div>
          </div>
        )}
      </div>
    </div>
  )
}

export default SingleCalendarConnection
