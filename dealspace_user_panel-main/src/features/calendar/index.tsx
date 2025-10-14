"use client"
import { useState, useEffect } from "react"
import SingleCalendarConnection from "./SingleCalendarConnection"
import DynamicCalendar from "./DynamicCalendar"
import { Calendar, Settings } from "lucide-react"
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

const IntegratedCalendarApp = () => {
  const [connectedAccount, setConnectedAccount] = useState<CalendarAccount | null>(null)
  const [activeTab, setActiveTab] = useState<"calendar" | "settings">("calendar")
  const [isInitialized, setIsInitialized] = useState(false)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const token = useAppSelector((state) => state.auth.token)

  const handleConnectionChange = (account: CalendarAccount | null) => {
    setConnectedAccount(account)
    // If account is connected and we're on settings tab, switch to calendar
    // if (account && activeTab === "settings") {
    //   setActiveTab("calendar")
    // }
  }
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
        setConnectedAccount(activeAccount)
      } else {
        setError(data.message || "Failed to fetch account")
      }
    } catch (err) {
      setError("Network error occurred")
    } finally {
      setLoading(false)
    }
  }

  // Initialize the app
  useEffect(() => {
    setIsInitialized(true)
    fetchAccount()
  }, [])

  // Force calendar re-render when tab becomes active and account exists
  useEffect(() => {
    if (activeTab === "calendar" && connectedAccount && isInitialized) {
      // Small delay to ensure DOM is ready
      setTimeout(() => {
        window.dispatchEvent(new Event("resize"))
      }, 150)
    }
  }, [activeTab, connectedAccount, isInitialized])

  return (
    <div className="min-h-screen bg-gray-50 relative">
      <div className="bg-white border-b border-gray-200">
        <div className="mx-auto px-6">
          <nav className="flex space-x-8">
            <button
              onClick={() => setActiveTab("calendar")}
              className={`flex items-center gap-2 py-4 px-1 border-b-2 font-medium text-sm transition-colors ${
                activeTab === "calendar"
                  ? "border-blue-500 text-blue-600"
                  : "border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300"
              }`}
            >
              <Calendar className="w-4 h-4" />
              Calendar
              {connectedAccount && (
                <span className="ml-1 inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                  Connected
                </span>
              )}
            </button>
            <button
              onClick={() => setActiveTab("settings")}
              className={`flex items-center gap-2 py-4 px-1 border-b-2 font-medium text-sm transition-colors ${
                activeTab === "settings"
                  ? "border-blue-500 text-blue-600"
                  : "border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300"
              }`}
            >
              <Settings className="w-4 h-4" />
              Connection
            </button>
          </nav>
        </div>
      </div>

      {/* Tab Content - Full Screen */}
      <div className="flex-1">
        {activeTab === "calendar" && isInitialized && (
          <div className="h-full">
            <DynamicCalendar connectedAccount={connectedAccount} />
          </div>
        )}

        {activeTab === "settings" && (
          <div className="max-w-7xl mx-auto p-6">
            <SingleCalendarConnection onConnectionChange={handleConnectionChange} />
          </div>
        )}
      </div>
    </div>
  )
}

export default IntegratedCalendarApp
