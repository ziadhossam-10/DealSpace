"use client"

import { useState } from "react"
import { PenTool, Clock, User, ArrowUpRight, ArrowDownLeft, PhoneIncoming, PhoneOutgoing, Send } from "lucide-react"
import { useGetActivitiesQuery } from "../activitiesApi" 
import type { Activity } from "../../../types/activities" 

interface PersonActivityFeedProps {
  personId: number
  onToast: (message: string, type?: "success" | "error") => void
}

export const PersonActivityFeed = ({ personId, onToast }: PersonActivityFeedProps) => {
  const [page, setPage] = useState(1)
  const {
    data: activitiesData,
    isLoading,
    error,
  } = useGetActivitiesQuery({
    person_id: personId,
    page,
    per_page: 15,
  })

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString("en-US", {
      year: "numeric",
      month: "short",
      day: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    })
  }

  const getActivityIcon = (activity: Activity) => {
    switch (activity.type) {
      case "Email":
        return activity.metadata.is_incoming ? (
          <ArrowDownLeft className="h-5 w-5 text-green-600" />
        ) : (
          <ArrowUpRight className="h-5 w-5 text-blue-600" />
        )
      case "TestMessage":
        return activity.metadata.is_incoming ? (
          <ArrowDownLeft className="h-5 w-5 text-green-600" />
        ) : (
          <Send className="h-5 w-5 text-blue-600" />
        )
      case "Call":
        return activity.metadata.is_incoming ? (
          <PhoneIncoming className="h-5 w-5 text-green-600" />
        ) : (
          <PhoneOutgoing className="h-5 w-5 text-blue-600" />
        )
      case "Note":
        return <PenTool className="h-5 w-5 text-purple-600" />
      default:
        return <Clock className="h-5 w-5 text-gray-500" />
    }
  }

  const getActivityBgColor = (activity: Activity) => {
    switch (activity.type) {
      case "Email":
        return "bg-blue-50 border-blue-200"
      case "TestMessage":
        return "bg-green-50 border-green-200"
      case "Call":
        return "bg-orange-50 border-orange-200"
      case "Note":
        return "bg-purple-50 border-purple-200"
      default:
        return "bg-gray-50 border-gray-200"
    }
  }

  const renderActivityContent = (activity: Activity) => {
    switch (activity.type) {
      case "Email":
        return (
          <div className="space-y-2">
            <div className="text-sm text-gray-600">
              <span className="font-medium">{activity.metadata.is_incoming ? "From:" : "To:"}</span>{" "}
              {activity.metadata.is_incoming ? activity.metadata.from_email : activity.metadata.to_email}
            </div>
            <div className="text-gray-800">
              {activity.description.length > 150
                ? `${activity.description.substring(0, 150)}...`
                : activity.description}
            </div>
          </div>
        )

      case "TestMessage":
        return (
          <div className="space-y-2">
            <div className="text-sm text-gray-600">
              <span className="font-medium">{activity.metadata.is_incoming ? "From:" : "To:"}</span>{" "}
              {activity.metadata.is_incoming ? activity.metadata.from_number : activity.metadata.to_number}
            </div>
            <div className="bg-white p-3 rounded border">
              <div className="text-gray-800 whitespace-pre-wrap">
                {activity.description.length > 150
                  ? `${activity.description.substring(0, 150)}...`
                  : activity.description}
              </div>
            </div>
          </div>
        )

      case "Call":
        return (
          <div className="space-y-2">
            <div className="text-sm text-gray-600">
              <span className="font-medium">Phone:</span> {activity.metadata.phone}
            </div>
            {activity.metadata.duration && (
              <div className="text-sm text-gray-600">
                <span className="font-medium">Duration:</span> {Math.floor(activity.metadata.duration / 60)}m{" "}
                {activity.metadata.duration % 60}s
              </div>
            )}
            {activity.description && <div className="text-gray-800">{activity.description}</div>}
          </div>
        )

      case "Note":
        return (
          <div className="text-gray-800">
            <div
              className="prose prose-sm max-w-none"
              dangerouslySetInnerHTML={{
                __html:
                  activity.description.length > 200
                    ? `${activity.description.substring(0, 200)}...`
                    : activity.description,
              }}
            />
          </div>
        )

      default:
        return <div className="text-gray-800">{activity.description}</div>
    }
  }

  if (isLoading) {
    return (
      <div className="space-y-4">
        {[...Array(5)].map((_, i) => (
          <div key={i} className="border border-gray-200 rounded-lg p-4 animate-pulse">
            <div className="flex items-start space-x-3">
              <div className="w-10 h-10 bg-gray-200 rounded-full"></div>
              <div className="flex-1 space-y-2">
                <div className="h-4 bg-gray-200 rounded w-1/4"></div>
                <div className="h-3 bg-gray-200 rounded w-3/4"></div>
                <div className="h-3 bg-gray-200 rounded w-1/2"></div>
              </div>
            </div>
          </div>
        ))}
      </div>
    )
  }

  if (error) {
    return (
      <div className="text-center py-8 text-gray-500">
        <p>Failed to load activities</p>
        <button onClick={() => window.location.reload()} className="mt-2 text-blue-600 hover:text-blue-800">
          Try again
        </button>
      </div>
    )
  }

  const activities = activitiesData?.data?.items || []

  if (activities.length === 0) {
    return (
      <div className="text-center py-8 text-gray-500">
        <Clock size={48} className="mx-auto mb-2 text-gray-300" />
        <p>No activities yet</p>
        <p className="text-sm">Activities will appear here as they happen</p>
      </div>
    )
  }

  return (
    <div className="space-y-4">
      {activities.map((activity) => (
        <div key={activity.id} className={`border rounded-lg p-4 shadow-sm bg-[#cccccc0a]`}>
          <div className="flex items-start space-x-3">
            <div className="flex-shrink-0 w-10 h-10 rounded-full bg-white border flex items-center justify-center">
              {getActivityIcon(activity)}
            </div>

            <div className="flex-1 min-w-0">
              <div className="flex items-center justify-between mb-2">
                <h4 className="font-medium text-gray-900 truncate">{activity.title}</h4>
                <span className="text-sm text-gray-500 flex-shrink-0 ml-2">{formatDate(activity.created_at)}</span>
              </div>

              {renderActivityContent(activity)}

              {
                activity.user?.name && (                  
                  <div className="mt-3 flex items-center text-sm text-gray-500">
                    <User size={14} className="mr-1" />
                    <span>By {activity.user?.name}</span>
                  </div>
                )
              }
            </div>
          </div>
        </div>
      ))}

      {/* Load More Button */}
      {activitiesData?.data?.meta && activitiesData.data.meta.current_page < activitiesData.data.meta.last_page && (
        <div className="text-center pt-4">
          <button
            onClick={() => setPage(page + 1)}
            className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            Load More Activities
          </button>
        </div>
      )}
    </div>
  )
}
