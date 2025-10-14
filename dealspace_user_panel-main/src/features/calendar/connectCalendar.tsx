"use client"

import { useState, useEffect } from "react"
import { Calendar, Mail, AlertCircle, CheckCircle, Clock, Trash2, RefreshCw } from "lucide-react"
import { BASE_URL } from "../../utils/helpers"
import { useAppSelector } from "../../app/hooks"

// Define types for better TypeScript support
interface CalendarAccount {
  id: string
  email: string
  provider: "google" | "outlook"
  calendar_name?: string
  is_active: boolean
  last_sync_at?: string
}

type SyncStatus = "syncing" | "success" | "error"

const CalendarConnectionComponent = () => {
  const [accounts, setAccounts] = useState<CalendarAccount[]>([])
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [success, setSuccess] = useState<string | null>(null)
  const [syncStatus, setSyncStatus] = useState<Record<string, SyncStatus>>({})
  const token = useAppSelector((state) => state.auth.token)

  // Handle OAuth callback messages
  useEffect(() => {
    const handleMessage = (event: MessageEvent) => {
      if (event.origin !== window.location.origin) return
      
      const { success: isSuccess, error: errorMessage, provider, message } = event.data
      
      if (isSuccess) {
        setSuccess(message || `${provider === "google" ? "Google" : "Outlook"} Calendar connected successfully`)
        fetchAccounts() // Refresh accounts list
      } else if (errorMessage) {
        setError(errorMessage)
      }
    }

    window.addEventListener("message", handleMessage)
    return () => window.removeEventListener("message", handleMessage)
  }, [])

  // Fetch connected accounts
  const fetchAccounts = async () => {
    try {
      setLoading(true);
      const response = await fetch(`${BASE_URL}/calendar-accounts`, {
        method: "GET",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
        },
      });

      const data = await response.json();

      if (data.success) {
        setAccounts(data.data);
      } else {
        setError(data.message || "Failed to fetch accounts");
      }
    } catch (err) {
      setError("Network error occurred");
    } finally {
      setLoading(false);
    }
  };

  // Connect to Google Calendar
  const connectGoogle = async () => {
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
        // Open popup window for OAuth
        const popup = window.open(
          data.auth_url,
          "google-calendar-auth",
          "width=600,height=700,scrollbars=yes,resizable=yes",
        )

        // Check if popup was successfully opened
        if (popup) {
          // Listen for popup completion
          const checkClosed = setInterval(() => {
            if (popup.closed) {
              clearInterval(checkClosed)
              // Add a small delay to allow backend processing
              setTimeout(() => {
                fetchAccounts() // Refresh accounts after connection
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
                fetchAccounts()
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

  // Toggle account connection
  const toggleConnection = async (accountId: string, isActive: boolean) => {
    try {
      const endpoint = isActive ? "disconnect" : "connect"
      const response = await fetch(`${BASE_URL}/calendar-accounts/${accountId}/${endpoint}`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
        },
      })

      const data = await response.json()

      if (data.success) {
        setSuccess(data.message)
        fetchAccounts()
      } else {
        setError(data.message || "Failed to toggle connection")
      }
    } catch (err) {
      setError("Network error occurred")
    }
  }

  // Sync calendar events
  const syncCalendar = async (accountId: string) => {
    try {
      setSyncStatus((prev) => ({ ...prev, [accountId]: "syncing" }))

      const response = await fetch(`${BASE_URL}/calendar-accounts/${accountId}/sync`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
        },
      })

      const data = await response.json()

      if (data.success) {
        setSyncStatus((prev) => ({ ...prev, [accountId]: "success" }))
        setSuccess(`Synced ${data.synced_count.events + data.synced_count.tasks	 + data.synced_count.appointments || 0} events`)
        fetchAccounts()
      } else {
        setSyncStatus((prev) => ({ ...prev, [accountId]: "error" }))
        setError(data.message || "Sync failed")
      }
    } catch (err) {
      setSyncStatus((prev) => ({ ...prev, [accountId]: "error" }))
      setError("Sync failed")
    }
  }

  // Delete account
  const deleteAccount = async (accountId: string) => {
    if (!window.confirm("Are you sure you want to delete this calendar connection?")) {
      return
    }

    try {
      const response = await fetch(`${BASE_URL}/calendar-accounts/${accountId}`, {
        method: "DELETE",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
        },
      })

      const data = await response.json()

      if (data.success) {
        setSuccess("Calendar connection deleted successfully")
        fetchAccounts()
      } else {
        setError(data.message || "Failed to delete connection")
      }
    } catch (err) {
      setError("Network error occurred")
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

  // Load accounts on mount
  useEffect(() => {
    fetchAccounts()
  }, [])

  return (
    <div className="max-w-4xl mx-auto p-6 bg-white rounded-lg shadow-lg">
      <div className="flex items-center justify-between mb-6">
        <div className="flex items-center space-x-3">
          <Calendar className="w-8 h-8 text-blue-600" />
          <h2 className="text-2xl font-bold text-gray-800">Calendar Connections</h2>
        </div>
        <button
          onClick={fetchAccounts}
          disabled={loading}
          className="flex items-center space-x-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 disabled:opacity-50"
        >
          <RefreshCw className={`w-4 h-4 ${loading ? "animate-spin" : ""}`} />
          <span>Refresh</span>
        </button>
      </div>

      {/* Status Messages */}
      {error && (
        <div className="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg flex items-center space-x-2">
          <AlertCircle className="w-5 h-5 text-red-600" />
          <span className="text-red-700">{error}</span>
        </div>
      )}

      {success && (
        <div className="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg flex items-center space-x-2">
          <CheckCircle className="w-5 h-5 text-green-600" />
          <span className="text-green-700">{success}</span>
        </div>
      )}

      {/* Connection Buttons */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
        <button
          onClick={connectGoogle}
          disabled={loading}
          className="flex items-center justify-center space-x-3 p-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 transition-colors"
        >
          <Calendar className="w-5 h-5" />
          <span>Connect Google Calendar</span>
        </button>

        <button
          onClick={connectOutlook}
          disabled={loading}
          className="flex items-center justify-center space-x-3 p-4 bg-blue-500 text-white rounded-lg hover:bg-blue-600 disabled:opacity-50 transition-colors"
        >
          <Mail className="w-5 h-5" />
          <span>Connect Outlook Calendar</span>
        </button>
      </div>

      {/* Connected Accounts */}
      <div className="space-y-4">
        <h3 className="text-lg font-semibold text-gray-800">Connected Accounts</h3>

        {loading && accounts.length === 0 ? (
          <div className="text-center py-8">
            <RefreshCw className="w-8 h-8 mx-auto mb-4 text-gray-400 animate-spin" />
            <p className="text-gray-500">Loading accounts...</p>
          </div>
        ) : accounts.length === 0 ? (
          <div className="text-center py-8">
            <Calendar className="w-12 h-12 mx-auto mb-4 text-gray-400" />
            <p className="text-gray-500">No calendar accounts connected</p>
            <p className="text-sm text-gray-400 mt-2">Connect your calendar to sync events automatically</p>
          </div>
        ) : (
          <div className="grid gap-4">
            {accounts.map((account) => (
              <div
                key={account.id}
                className={`p-4 rounded-lg border-2 transition-all ${
                  account.is_active ? "border-green-200 bg-green-50" : "border-gray-200 bg-gray-50"
                }`}
              >
                <div className="flex items-center justify-between">
                  <div className="flex items-center space-x-3">
                    <div className={`w-3 h-3 rounded-full ${account.is_active ? "bg-green-500" : "bg-gray-400"}`} />
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

                  <div className="flex items-center space-x-2">
                    <button
                      onClick={() => syncCalendar(account.id)}
                      disabled={syncStatus[account.id] === "syncing"}
                      className="flex items-center space-x-1 px-3 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200 disabled:opacity-50 text-sm"
                    >
                      <RefreshCw className={`w-4 h-4 ${syncStatus[account.id] === "syncing" ? "animate-spin" : ""}`} />
                      <span>Sync</span>
                    </button>

                    <button
                      onClick={() => toggleConnection(account.id, account.is_active)}
                      className={`px-3 py-1 rounded text-sm ${
                        account.is_active
                          ? "bg-red-100 text-red-700 hover:bg-red-200"
                          : "bg-green-100 text-green-700 hover:bg-green-200"
                      }`}
                    >
                      {account.is_active ? "Disconnect" : "Connect"}
                    </button>

                    <button
                      onClick={() => deleteAccount(account.id)}
                      className="p-1 text-red-600 hover:bg-red-100 rounded"
                    >
                      <Trash2 className="w-4 h-4" />
                    </button>
                  </div>
                </div>

                {/* Sync Status Indicator */}
                {syncStatus[account.id] && (
                  <div className="mt-2 flex items-center space-x-2 text-sm">
                    {syncStatus[account.id] === "syncing" && (
                      <>
                        <Clock className="w-4 h-4 text-blue-600 animate-spin" />
                        <span className="text-blue-600">Syncing...</span>
                      </>
                    )}
                    {syncStatus[account.id] === "success" && (
                      <>
                        <CheckCircle className="w-4 h-4 text-green-600" />
                        <span className="text-green-600">Synced successfully</span>
                      </>
                    )}
                    {syncStatus[account.id] === "error" && (
                      <>
                        <AlertCircle className="w-4 h-4 text-red-600" />
                        <span className="text-red-600">Sync failed</span>
                      </>
                    )}
                  </div>
                )}
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  )
}

export default CalendarConnectionComponent