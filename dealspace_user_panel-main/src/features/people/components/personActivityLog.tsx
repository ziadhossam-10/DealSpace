"use client"
import { useState } from "react"
import { useNavigate } from "react-router"
import { PenTool, Mail, Phone, FileText, ChevronLeft, ChevronRight } from "lucide-react"
import { PersonNotes } from "./personNotes"
import { PersonCalls } from "./personCalls"
import { PersonTexts } from "./personTexts"
import { PersonEmails } from "./personEmails"
import { PersonActivityFeed } from "./PersonActivityFeed"

interface PersonActivityLogProps {
  contact: any
  navigation?: {
    current_position: number
    total_count: number
    next_id: number | null
    previous_id: number | null
  }
  filters?: {
    search?: string
    stage_id?: number | null
    team_id?: number | null
    user_ids?: number[]
    deal_type_id?: number | null
  }
  onToast: (message: string, type?: "success" | "error") => void
}

export const PersonActivityLog = ({ contact, navigation, filters, onToast }: PersonActivityLogProps) => {
  const [activeTab, setActiveTab] = useState("all")
  const navigate = useNavigate()

  // Helper function to serialize filters for URL
  const serializeFilters = (filters?: PersonActivityLogProps["filters"]): string => {
    if (!filters) return ""

    const params = new URLSearchParams()

    if (filters.search) params.set("search", filters.search)
    if (filters.stage_id) params.set("stage_id", filters.stage_id.toString())
    if (filters.team_id) params.set("team_id", filters.team_id.toString())
    if (filters.deal_type_id) params.set("deal_type_id", filters.deal_type_id.toString())
    if (filters.user_ids && filters.user_ids.length > 0) {
      params.set("user_ids", filters.user_ids.join(","))
    }

    return params.toString()
  }

  const handleNavigation = (personId: number | null) => {
    if (!personId) return

    const filterParams = serializeFilters(filters)
    const url = filterParams ? `/people/${personId}?${filterParams}` : `/people/${personId}`
    navigate(url)
  }

  const handlePrevious = () => {
    if (navigation?.previous_id) {
      handleNavigation(navigation.previous_id)
    }
  }

  const handleNext = () => {
    if (navigation?.next_id) {
      handleNavigation(navigation.next_id)
    }
  }

  const renderTabContent = () => {
    switch (activeTab) {
      case "notes":
        return <PersonNotes personId={contact?.id} onToast={onToast} />
      case "calls":
        return <PersonCalls personId={contact?.id} onToast={onToast} />
      case "texts":
        return <PersonTexts personId={contact?.id} onToast={onToast} />
      case "emails":
        return (
          <PersonEmails
            personId={contact?.id}
            personEmails={contact?.emails || []}
            personName={contact?.name}
            onToast={onToast}
          />
        )
      case "all":
        return <PersonActivityFeed personId={contact?.id} onToast={onToast} />
      default:
        return (
          <div className="text-center py-8 text-gray-500">
            <p>No {activeTab} found</p>
          </div>
        )
    }
  }

  return (
    <div className="flex-1 flex flex-col border-r bg-white">
      <div className="flex justify-between items-center p-4 border-b">
        <div className="flex space-x-2">
          <button
            className={`p-1 rounded transition-colors ${
              navigation?.previous_id ? "text-gray-700 hover:bg-gray-100" : "text-gray-300 cursor-not-allowed"
            }`}
            onClick={handlePrevious}
            disabled={!navigation?.previous_id}
            title={navigation?.previous_id ? "Previous person" : "No previous person"}
          >
            <ChevronLeft size={20} />
          </button>

          <span className="text-gray-600 flex items-center">
            {navigation ? (
              <>
                Person {navigation.current_position} of {navigation.total_count.toLocaleString()}
                {filters && Object.keys(filters).some((key) => filters[key as keyof typeof filters]) && (
                  <span className="ml-2 px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">Filtered</span>
                )}
              </>
            ) : (
              `Person ${contact?.id}`
            )}
          </span>

          <button
            className={`p-1 rounded transition-colors ${
              navigation?.next_id ? "text-gray-700 hover:bg-gray-100" : "text-gray-300 cursor-not-allowed"
            }`}
            onClick={handleNext}
            disabled={!navigation?.next_id}
            title={navigation?.next_id ? "Next person" : "No next person"}
          >
            <ChevronRight size={20} />
          </button>
        </div>
      </div>

      <div className="p-4 border-b">
        <div className="flex space-x-2 mb-4">
          <button
            className={`text-gray-600 flex items-center px-3 py-1 rounded text-sm transition-colors ${
              activeTab === "all" ? "bg-gray-200" : "hover:bg-gray-100"
            }`}
            onClick={() => setActiveTab("all")}
          >
            <FileText size={16} className="mr-1" />
            All
          </button>

          <button
            className={`text-gray-600 flex items-center px-3 py-1 rounded text-sm transition-colors ${
              activeTab === "emails" ? "bg-gray-200" : "hover:bg-gray-100"
            }`}
            onClick={() => setActiveTab("emails")}
          >
            <Mail size={16} className="mr-1" />
            Emails
          </button>

          <button
            className={`text-gray-600 flex items-center px-3 py-1 rounded text-sm transition-colors ${
              activeTab === "calls" ? "bg-gray-200" : "hover:bg-gray-100"
            }`}
            onClick={() => setActiveTab("calls")}
          >
            <Phone size={16} className="mr-1" />
            Calls
          </button>

          <button
            className={`text-gray-600 flex items-center px-3 py-1 rounded text-sm transition-colors ${
              activeTab === "notes" ? "bg-blue-100" : "hover:bg-gray-100"
            }`}
            onClick={() => setActiveTab("notes")}
          >
            <PenTool size={16} className="mr-1" />
            Notes
          </button>
        </div>
      </div>

      <div className="flex-1 overflow-y-auto p-4 pb-[100px]">{renderTabContent()}</div>
    </div>
  )
}
