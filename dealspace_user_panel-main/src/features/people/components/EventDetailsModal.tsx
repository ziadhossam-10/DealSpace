"use client"
import {
  X,
  Calendar,
  Clock,
  Globe,
  User,
  Mail,
  Phone,
  Home,
  MapPin,
  Tag,
  ExternalLink,
  Copy,
  Activity,
  Eye,
  FileText,
  MousePointer,
  Search,
  UserPlus,
  Heart,
  Edit3,
  Send,
} from "lucide-react"
import { PersonEvent } from "../../../types/people"

interface EventDetailsModalProps {
  isOpen: boolean
  onClose: () => void
  event: PersonEvent | null
}

export const EventDetailsModal = ({ isOpen, onClose, event }: EventDetailsModalProps) => {
  if (!isOpen || !event) return null

  const getEventIcon = (type: string) => {
    switch (type.toLowerCase()) {
      case "viewed page":
        return <Eye className="w-5 h-5 text-blue-500" />
      case "viewed property":
        return <Home className="w-5 h-5 text-green-500" />
      case "form started":
        return <Edit3 className="w-5 h-5 text-yellow-500" />
      case "form filled":
        return <FileText className="w-5 h-5 text-orange-500" />
      case "form submitted":
        return <Send className="w-5 h-5 text-purple-500" />
      case "visitor identified":
      case "registration":
        return <User className="w-5 h-5 text-orange-500" />
      case "scroll milestone":
        return <MousePointer className="w-5 h-5 text-gray-500" />
      case "email opened":
        return <Mail className="w-5 h-5 text-red-500" />
      case "property search":
      case "saved property search":
        return <Search className="w-5 h-5 text-indigo-500" />
      case "incoming call":
        return <Phone className="w-5 h-5 text-green-600" />
      case "visited website":
        return <Globe className="w-5 h-5 text-blue-600" />
      case "saved property":
        return <Heart className="w-5 h-5 text-red-600" />
      case "visited open house":
        return <Calendar className="w-5 h-5 text-purple-600" />
      case "unsubscribed":
        return <UserPlus className="w-5 h-5 text-gray-600" />
      default:
        return <Activity className="w-5 h-5 text-gray-400" />
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
      weekday: "long",
      year: "numeric",
      month: "long",
      day: "numeric",
      hour: "2-digit",
      minute: "2-digit",
      second: "2-digit",
    })
  }

  const formatPrice = (price: number) => {
    return new Intl.NumberFormat("en-US", {
      style: "currency",
      currency: "USD",
      minimumFractionDigits: 0,
    }).format(price)
  }

  const copyToClipboard = (text: string) => {
    navigator.clipboard.writeText(text)
  }

  const openUrl = (url: string) => {
    window.open(url, "_blank", "noopener,noreferrer")
  }

  return (
    <div className="fixed inset-0 z-50 overflow-y-auto">
      <div className="flex min-h-screen items-center justify-center p-4">
        <div className="fixed inset-0 bg-black bg-opacity-25" onClick={onClose} />

        <div className="relative bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
          {/* Header */}
          <div className="flex items-center justify-between p-6 border-b border-gray-200">
            <div className="flex items-center space-x-3">
              {getEventIcon(event.type)}
              <div>
                <h2 className="text-xl font-semibold text-gray-900">{event.type}</h2>
                <p className="text-sm text-gray-500">Event ID: {event.id}</p>
              </div>
            </div>
            <button onClick={onClose} className="text-gray-400 hover:text-gray-600 transition-colors">
              <X className="w-6 h-6" />
            </button>
          </div>

          {/* Content */}
          <div className="p-6 space-y-6">
            {/* Event Overview */}
            <div className="bg-gray-50 rounded-lg p-4">
              <div className="flex items-center justify-between mb-3">
                <h3 className="text-lg font-medium text-gray-900">Event Overview</h3>
                <div className="flex items-center space-x-2">
                  <span
                    className={`inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${getEventTypeColor(event.type)}`}
                  >
                    {event.type}
                  </span>
                  {event.is_historical && (
                    <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                      Historical
                    </span>
                  )}
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="flex items-center space-x-2 text-sm text-gray-600">
                  <Calendar className="w-4 h-4" />
                  <span className="font-medium">Occurred:</span>
                  <span>{formatDate(event.occurred_at)}</span>
                </div>
                <div className="flex items-center space-x-2 text-sm text-gray-600">
                  <Tag className="w-4 h-4" />
                  <span className="font-medium">Source:</span>
                  <span>{event.source}</span>
                </div>
                <div className="flex items-center space-x-2 text-sm text-gray-600">
                  <Activity className="w-4 h-4" />
                  <span className="font-medium">System:</span>
                  <span>{event.system}</span>
                </div>
                {event.page_duration !== null && (
                  <div className="flex items-center space-x-2 text-sm text-gray-600">
                    <Clock className="w-4 h-4" />
                    <span className="font-medium">Duration:</span>
                    <span>{event.page_duration}s</span>
                  </div>
                )}
              </div>

              <div className="mt-4">
                <p className="text-sm font-medium text-gray-900 mb-2">Message:</p>
                <p className="text-sm text-gray-700">{event.message}</p>
              </div>

              {event.description && event.description !== event.message && (
                <div className="mt-4">
                  <p className="text-sm font-medium text-gray-900 mb-2">Description:</p>
                  <p className="text-sm text-gray-700">{event.description}</p>
                </div>
              )}
            </div>

            {/* Person Information */}
            {event.person && (
              <div className="bg-blue-50 rounded-lg p-4">
                <h3 className="text-lg font-medium text-gray-900 mb-3 flex items-center">
                  <User className="w-5 h-5 mr-2" />
                  Person Information
                </h3>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div className="flex items-center space-x-2 text-sm text-gray-600">
                    <User className="w-4 h-4" />
                    <span className="font-medium">Name:</span>
                    <span>{event.person.name}</span>
                  </div>
                  {event.person.email && (
                    <div className="flex items-center space-x-2 text-sm text-gray-600">
                      <Mail className="w-4 h-4" />
                      <span className="font-medium">Email:</span>
                      <span>{event.person.email}</span>
                      <button
                        onClick={() => copyToClipboard(event.person!.email)}
                        className="text-blue-600 hover:text-blue-800"
                      >
                        <Copy className="w-3 h-3" />
                      </button>
                    </div>
                  )}
                  {event.person.phone && (
                    <div className="flex items-center space-x-2 text-sm text-gray-600">
                      <Phone className="w-4 h-4" />
                      <span className="font-medium">Phone:</span>
                      <span>{event.person.phone}</span>
                      <button
                        onClick={() => copyToClipboard(event.person!.phone!)}
                        className="text-blue-600 hover:text-blue-800"
                      >
                        <Copy className="w-3 h-3" />
                      </button>
                    </div>
                  )}
                  {event.person.message && (
                    <div className="md:col-span-2">
                      <p className="text-sm font-medium text-gray-900 mb-1">Message:</p>
                      <p className="text-sm text-gray-700 bg-white p-2 rounded border">{event.person.message}</p>
                    </div>
                  )}
                </div>
              </div>
            )}

            {/* Property Information */}
            {event.property && (
              <div className="bg-green-50 rounded-lg p-4">
                <h3 className="text-lg font-medium text-gray-900 mb-3 flex items-center">
                  <Home className="w-5 h-5 mr-2" />
                  Property Information
                </h3>
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                  <div className="flex items-center space-x-2 text-sm text-gray-600">
                    <MapPin className="w-4 h-4" />
                    <span className="font-medium">Address:</span>
                    <span>
                      {event.property.street}, {event.property.city}, {event.property.state} {event.property.code}
                    </span>
                  </div>
                  <div className="flex items-center space-x-2 text-sm text-gray-600">
                    <Tag className="w-4 h-4" />
                    <span className="font-medium">MLS:</span>
                    <span>{event.property.mlsNumber}</span>
                  </div>
                  <div className="flex items-center space-x-2 text-sm text-gray-600">
                    <span className="font-medium">Price:</span>
                    <span className="text-green-600 font-semibold">{formatPrice(event.property.price)}</span>
                  </div>
                  <div className="flex items-center space-x-2 text-sm text-gray-600">
                    <span className="font-medium">Type:</span>
                    <span>{event.property.type}</span>
                  </div>
                  <div className="flex items-center space-x-2 text-sm text-gray-600">
                    <span className="font-medium">Bedrooms:</span>
                    <span>{event.property.bedrooms}</span>
                  </div>
                  <div className="flex items-center space-x-2 text-sm text-gray-600">
                    <span className="font-medium">Bathrooms:</span>
                    <span>{event.property.bathrooms}</span>
                  </div>
                  <div className="flex items-center space-x-2 text-sm text-gray-600">
                    <span className="font-medium">Area:</span>
                    <span>{event.property.area}</span>
                  </div>
                  <div className="flex items-center space-x-2 text-sm text-gray-600">
                    <span className="font-medium">Lot:</span>
                    <span>{event.property.lot}</span>
                  </div>
                  <div className="flex items-center space-x-2 text-sm text-gray-600">
                    <span className="font-medium">For Rent:</span>
                    <span>{event.property.forRent ? "Yes" : "No"}</span>
                  </div>
                </div>
                {event.property.url && (
                  <div className="mt-4">
                    <button
                      onClick={() => openUrl(event.property!.url)}
                      className="inline-flex items-center space-x-2 text-sm text-blue-600 hover:text-blue-800"
                    >
                      <ExternalLink className="w-4 h-4" />
                      <span>View Property</span>
                    </button>
                  </div>
                )}
              </div>
            )}

            {/* Campaign Information */}
            {event.campaign && (
              <div className="bg-purple-50 rounded-lg p-4">
                <h3 className="text-lg font-medium text-gray-900 mb-3 flex items-center">
                  <Tag className="w-5 h-5 mr-2" />
                  Campaign Information
                </h3>
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div className="flex items-center space-x-2 text-sm text-gray-600">
                    <span className="font-medium">Source:</span>
                    <span>{event.campaign.source}</span>
                  </div>
                  <div className="flex items-center space-x-2 text-sm text-gray-600">
                    <span className="font-medium">Medium:</span>
                    <span>{event.campaign.medium}</span>
                  </div>
                  <div className="flex items-center space-x-2 text-sm text-gray-600">
                    <span className="font-medium">Campaign:</span>
                    <span>{event.campaign.campaign}</span>
                  </div>
                </div>
              </div>
            )}

            {/* Page Information */}
            <div className="bg-gray-50 rounded-lg p-4">
              <h3 className="text-lg font-medium text-gray-900 mb-3 flex items-center">
                <Globe className="w-5 h-5 mr-2" />
                Page Information
              </h3>
              <div className="space-y-3">
                <div className="flex items-center space-x-2 text-sm text-gray-600">
                  <span className="font-medium">Title:</span>
                  <span>{event.page_title}</span>
                </div>
                <div className="flex items-start space-x-2 text-sm text-gray-600">
                  <span className="font-medium mt-0.5">URL:</span>
                  <div className="flex-1">
                    <span className="break-all">{event.page_url}</span>
                    <div className="flex items-center space-x-2 mt-1">
                      <button
                        onClick={() => copyToClipboard(event.page_url)}
                        className="text-blue-600 hover:text-blue-800"
                      >
                        <Copy className="w-3 h-3" />
                      </button>
                      <button onClick={() => openUrl(event.page_url)} className="text-blue-600 hover:text-blue-800">
                        <ExternalLink className="w-3 h-3" />
                      </button>
                    </div>
                  </div>
                </div>
                {event.page_referrer && (
                  <div className="flex items-start space-x-2 text-sm text-gray-600">
                    <span className="font-medium mt-0.5">Referrer:</span>
                    <div className="flex-1">
                      <span className="break-all">{event.page_referrer}</span>
                      <button
                        onClick={() => copyToClipboard(event.page_referrer!)}
                        className="text-blue-600 hover:text-blue-800 ml-2"
                      >
                        <Copy className="w-3 h-3" />
                      </button>
                    </div>
                  </div>
                )}
              </div>
            </div>

            {/* Technical Details */}
            <div className="bg-gray-50 rounded-lg p-4">
              <h3 className="text-lg font-medium text-gray-900 mb-3">Technical Details</h3>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-600">
                <div className="flex items-center space-x-2">
                  <span className="font-medium">Event ID:</span>
                  <span>{event.id}</span>
                </div>
                <div className="flex items-center space-x-2">
                  <span className="font-medium">Person ID:</span>
                  <span>{event.person_id || "N/A"}</span>
                </div>
                <div className="flex items-center space-x-2">
                  <span className="font-medium">Tenant ID:</span>
                  <span>{event.tenant_id}</span>
                </div>
                <div className="flex items-center space-x-2">
                  <span className="font-medium">Created:</span>
                  <span>{formatDate(event.created_at)}</span>
                </div>
                <div className="flex items-center space-x-2">
                  <span className="font-medium">Updated:</span>
                  <span>{formatDate(event.updated_at)}</span>
                </div>
              </div>
            </div>
          </div>

          {/* Footer */}
          <div className="flex justify-end p-6 border-t border-gray-200">
            <button
              onClick={onClose}
              className="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-colors"
            >
              Close
            </button>
          </div>
        </div>
      </div>
    </div>
  )
}
