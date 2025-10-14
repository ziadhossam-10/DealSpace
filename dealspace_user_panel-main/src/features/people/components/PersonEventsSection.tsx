"use client"

import { useState } from "react"
import {
  Activity,
  Eye,
  FileText,
  Home,
  Mail,
  MousePointer,
  Search,
  User,
  ChevronDown,
  Phone,
  Globe,
  UserPlus,
  Heart,
  Calendar,
  Edit3,
  Send,
  ExternalLink,
  ChevronRight,
} from "lucide-react"
import { EventDetailsModal } from "./EventDetailsModal"

interface PersonEvent {
  id: number
  source: string
  system: string
  type: string
  message: string
  description: string
  person: {
    name: string
    email: string
    phone?: string
    message?: string
  } | null
  property: {
    street: string
    city: string
    state: string
    code: string
    mlsNumber: string
    price: number
    forRent: boolean
    url: string
    type: string
    bedrooms: string
    bathrooms: string
    area: string
    lot: string
  } | null
  property_search: any | null
  campaign: {
    source: string
    medium: string
    campaign: string
  } | null
  page_title: string
  page_url: string
  page_referrer: string | null
  page_duration: number | null
  occurred_at: string
  is_historical: boolean
  person_full_name: string | null
  property_address: string | null
  tenant_id: string
  person_id: number | null
  created_at: string
  updated_at: string
}

interface PersonEventsSectionProps {
  personId: number
  events: PersonEvent[]
  totalEvents: number
  currentPage: number
  hasMore: boolean
  isLoading: boolean
  isLoadingMore: boolean
  isExpanded: boolean
  onToggleExpanded: () => void
  onLoadMore: () => void
  onFilterChange: (filters: any) => void
}

export const PersonEventsSection = ({
  personId,
  events,
  totalEvents,
  currentPage,
  hasMore,
  isLoading,
  isLoadingMore,
  isExpanded,
  onToggleExpanded,
  onLoadMore,
  onFilterChange,
}: PersonEventsSectionProps) => {
  const [filters, setFilters] = useState({
    search: "",
    type: "",
    source: "",
    date_from: "",
    date_to: "",
  })

  const [selectedEvent, setSelectedEvent] = useState<PersonEvent | null>(null)
  const [isModalOpen, setIsModalOpen] = useState(false)

  const getEventIcon = (type: string) => {
    switch (type.toLowerCase()) {
      case "viewed page":
        return <Eye className="w-4 h-4 text-blue-500" />
      case "viewed property":
        return <Home className="w-4 h-4 text-green-500" />
      case "form started":
        return <Edit3 className="w-4 h-4 text-yellow-500" />
      case "form filled":
        return <FileText className="w-4 h-4 text-orange-500" />
      case "form submitted":
      case "inquiry":
      case "seller inquiry":
      case "property inquiry":
      case "general inquiry":
        return <Send className="w-4 h-4 text-purple-500" />
      case "visitor identified":
      case "registration":
        return <User className="w-4 h-4 text-orange-500" />
      case "scroll milestone":
        return <MousePointer className="w-4 h-4 text-gray-500" />
      case "email opened":
        return <Mail className="w-4 h-4 text-red-500" />
      case "property search":
      case "saved property search":
        return <Search className="w-4 h-4 text-indigo-500" />
      case "incoming call":
        return <Phone className="w-4 h-4 text-green-600" />
      case "visited website":
        return <Globe className="w-4 h-4 text-blue-600" />
      case "saved property":
        return <Heart className="w-4 h-4 text-red-600" />
      case "visited open house":
        return <Calendar className="w-4 h-4 text-purple-600" />
      case "unsubscribed":
        return <UserPlus className="w-4 h-4 text-gray-600" />
      default:
        return <Activity className="w-4 h-4 text-gray-400" />
    }
  }

  const getEventTypeColor = (type: string) => {
    switch (type.toLowerCase()) {
      case "viewed page":
        return "bg-blue-100 text-blue-800"
      case "viewed property":
        return "bg-green-100 text-green-800"
      case "form started":
        return "bg-yellow-100 text-yellow-800"
      case "form filled":
        return "bg-orange-100 text-orange-800"
      case "form submitted":
      case "inquiry":
      case "seller inquiry":
      case "property inquiry":
      case "general inquiry":
        return "bg-purple-100 text-purple-800"
      case "visitor identified":
      case "registration":
        return "bg-orange-100 text-orange-800"
      case "scroll milestone":
        return "bg-gray-100 text-gray-800"
      case "email opened":
        return "bg-red-100 text-red-800"
      case "property search":
      case "saved property search":
        return "bg-indigo-100 text-indigo-800"
      case "incoming call":
        return "bg-green-100 text-green-800"
      case "visited website":
        return "bg-blue-100 text-blue-800"
      case "saved property":
        return "bg-red-100 text-red-800"
      case "visited open house":
        return "bg-purple-100 text-purple-800"
      case "unsubscribed":
        return "bg-gray-100 text-gray-800"
      default:
        return "bg-gray-100 text-gray-600"
    }
  }

  const formatDate = (dateString: string) => {
    const date = new Date(dateString)
    return date.toLocaleDateString("en-US", {
      month: "short",
      day: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    })
  }

  const handleFilterChange = (key: string, value: string) => {
    const newFilters = { ...filters, [key]: value }
    setFilters(newFilters)
    onFilterChange(newFilters)
  }

  const clearFilters = () => {
    const clearedFilters = {
      search: "",
      type: "",
      source: "",
      date_from: "",
      date_to: "",
    }
    setFilters(clearedFilters)
    onFilterChange(clearedFilters)
  }

  const handleEventClick = (event: PersonEvent) => {
    setSelectedEvent(event)
    setIsModalOpen(true)
  }

  const handleCloseModal = () => {
    setIsModalOpen(false)
    setSelectedEvent(null)
  }

  return (
    <>
      <div className="p-4 border-b">
        <div className="w-full">
          <div className="flex items-center justify-between">
            <div className="flex items-center">
              <Activity size={16} className="mr-2 text-gray-500" />
              <span className="font-medium">Website Activity</span>
              {totalEvents > 0 && <span className="ml-2 text-xs text-gray-500">({totalEvents})</span>}
            </div>
            <div className="flex items-center">
              <button
                className="h-8 w-8 p-0 flex items-center justify-center hover:bg-gray-100"
                onClick={onToggleExpanded}
              >
                <ChevronRight
                  size={16}
                  className={`text-gray-500 transition-transform ${isExpanded ? "rotate-90" : ""}`}
                />
              </button>
            </div>
          </div>

          {isExpanded && (
            <div className="pt-2 pb-1">

              {isLoading ? (
                <div className="flex items-center justify-center py-4">
                  <div className="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
                </div>
              ) : events.length > 0 ? (
                <div className="space-y-2 max-h-80 overflow-y-auto">
                  {events.map((event) => (
                    <div
                      key={event.id}
                      className="p-3 bg-gray-50 rounded-md hover:bg-gray-100 cursor-pointer transition-colors group"
                      onClick={() => handleEventClick(event)}
                    >
                      <div className="flex items-start space-x-2">
                        <div className="flex-shrink-0 mt-0.5">{getEventIcon(event.type)}</div>
                        <div className="flex-1 min-w-0">
                          <div className="flex items-center justify-between mb-1">
                            <div className="flex items-center space-x-2">
                              <span
                                className={`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${getEventTypeColor(event.type)}`}
                              >
                                {event.type}
                              </span>
                            </div>
                            <div className="flex items-center space-x-2">
                              <span className="text-xs text-gray-500 flex-shrink-0">
                                {formatDate(event.occurred_at)}
                              </span>
                              <ExternalLink className="w-3 h-3 text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity" />
                            </div>
                          </div>

                          <p className="text-sm text-gray-900 mb-1 font-medium">{event.message}</p>

                          {event.description && event.description !== event.message && (
                            <p className="text-xs text-gray-600 mb-2 line-clamp-2">{event.description}</p>
                          )}

                          <div className="flex flex-wrap items-center gap-2 text-xs text-gray-500">
                            <span className="flex items-center">
                              <span className="font-medium">Source:</span>
                              <span className="ml-1">{event.source}</span>
                            </span>

                            {event.page_title && (
                              <span className="flex items-center">
                                <span className="font-medium">Page:</span>
                                <span className="ml-1 truncate max-w-32">{event.page_title}</span>
                              </span>
                            )}

                            {event.property_address && (
                              <span className="flex items-center">
                                <Home className="w-3 h-3 mr-1" />
                                <span className="truncate max-w-32">{event.property_address}</span>
                              </span>
                            )}

                            {event.campaign && (
                              <span className="flex items-center">
                                <span className="font-medium">Campaign:</span>
                                <span className="ml-1">{event.campaign.campaign}</span>
                              </span>
                            )}
                          </div>

                          {event.person && (event.person.email || event.person.phone) && (
                            <div className="mt-1 flex flex-wrap items-center gap-2 text-xs text-gray-500">
                              {event.person.email && (
                                <span className="flex items-center">
                                  <Mail className="w-3 h-3 mr-1" />
                                  {event.person.email}
                                </span>
                              )}
                              {event.person.phone && (
                                <span className="flex items-center">
                                  <Phone className="w-3 h-3 mr-1" />
                                  {event.person.phone}
                                </span>
                              )}
                            </div>
                          )}
                        </div>
                      </div>
                    </div>
                  ))}

                  {hasMore && (
                    <button
                      onClick={onLoadMore}
                      disabled={isLoadingMore}
                      className="w-full text-sm text-blue-600 hover:text-blue-800 py-2"
                    >
                      {isLoadingMore ? "Loading..." : "Load more events"}
                    </button>
                  )}
                </div>
              ) : (
                <div className="text-center py-4">
                  <Activity className="w-8 h-8 text-gray-400 mx-auto mb-2" />
                  <p className="text-gray-500 text-xs mb-2">No events found</p>
                  <p className="text-gray-400 text-xs">
                    {filters.search || filters.type || filters.source
                      ? "Try adjusting your filters"
                      : "Events will appear here as they occur"}
                  </p>
                </div>
              )}
            </div>
          )}
        </div>
      </div>

      {/* Event Details Modal */}
      <EventDetailsModal isOpen={isModalOpen} onClose={handleCloseModal} event={selectedEvent} />
    </>
  )
}
