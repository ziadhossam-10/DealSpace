"use client"
import type React from "react"
import { useState, useEffect } from "react"
import { X, Calendar, Clock, MapPin, Users, Globe, Eye, Link, Type, ChevronDown } from "lucide-react"
import { Modal } from "../../components/modal" 

interface EventData {
  id?: number
  calendar_account_id: number
  title: string
  description?: string
  location?: string
  start_time: string
  end_time: string
  timezone?: string
  is_all_day: boolean
  status: "tentative" | "confirmed" | "cancelled"
  visibility: "default" | "public" | "private"
  organizer_email?: string
  meeting_link?: string
  event_type: "event"
}

interface EventModalProps {
  isOpen: boolean
  onClose: () => void
  onSubmit: (data: Omit<EventData, "id">) => Promise<void>
  event?: EventData | null
  calendarAccountId: number
  isLoading?: boolean
}

export const EventModal: React.FC<EventModalProps> = ({
  isOpen,
  onClose,
  onSubmit,
  event,
  calendarAccountId,
  isLoading = false,
}) => {
  const [formData, setFormData] = useState<Omit<EventData, "id">>({
    calendar_account_id: calendarAccountId,
    title: "",
    description: "",
    location: "",
    start_time: "",
    end_time: "",
    timezone: "UTC",
    is_all_day: false,
    status: "confirmed",
    visibility: "default",
    organizer_email: "",
    meeting_link: "",
    event_type: "event",
  })

  const [startDate, setStartDate] = useState("")
  const [startTime, setStartTime] = useState("")
  const [endDate, setEndDate] = useState("")
  const [endTime, setEndTime] = useState("")
  const [showStatusDropdown, setShowStatusDropdown] = useState(false)
  const [showVisibilityDropdown, setShowVisibilityDropdown] = useState(false)
  const [showTimezoneDropdown, setShowTimezoneDropdown] = useState(false)

  const statusOptions = [
    { value: "confirmed", label: "Confirmed", color: "text-green-600" },
    { value: "tentative", label: "Tentative", color: "text-yellow-600" },
    { value: "cancelled", label: "Cancelled", color: "text-red-600" },
  ]

  const visibilityOptions = [
    { value: "default", label: "Default", icon: Globe },
    { value: "public", label: "Public", icon: Globe },
    { value: "private", label: "Private", icon: Eye },
  ]

  const timezoneOptions = [
    { value: "UTC", label: "UTC (GMT+00:00)" },
    { value: "America/New_York", label: "Eastern Time (GMT-05:00)" },
    { value: "America/Chicago", label: "Central Time (GMT-06:00)" },
    { value: "America/Denver", label: "Mountain Time (GMT-07:00)" },
    { value: "America/Los_Angeles", label: "Pacific Time (GMT-08:00)" },
    { value: "Europe/London", label: "London Time (GMT+00:00)" },
    { value: "Europe/Paris", label: "Paris Time (GMT+01:00)" },
    { value: "Asia/Tokyo", label: "Tokyo Time (GMT+09:00)" },
    { value: "Africa/Cairo", label: "Cairo Time (GMT+02:00)" },
  ]

  // Initialize form data
  useEffect(() => {
    if (event) {
      const startDateTime = new Date(event.start_time)
      const endDateTime = new Date(event.end_time)

      setFormData({
        calendar_account_id: event.calendar_account_id,
        title: event.title,
        description: event.description || "",
        location: event.location || "",
        start_time: event.start_time,
        end_time: event.end_time,
        timezone: event.timezone || "UTC",
        is_all_day: event.is_all_day,
        status: event.status,
        visibility: event.visibility,
        organizer_email: event.organizer_email || "",
        meeting_link: event.meeting_link || "",
        event_type: "event",
      })

      setStartDate(startDateTime.toISOString().split("T")[0])
      setStartTime(startDateTime.toTimeString().slice(0, 5))
      setEndDate(endDateTime.toISOString().split("T")[0])
      setEndTime(endDateTime.toTimeString().slice(0, 5))
    } else {
      // Reset form for new event
      const now = new Date()
      const oneHourLater = new Date(now.getTime() + 60 * 60 * 1000)
      const todayDate = now.toISOString().split("T")[0]
      const defaultStartTime = now.toTimeString().slice(0, 5)
      const defaultEndTime = oneHourLater.toTimeString().slice(0, 5)

      setStartDate(todayDate)
      setStartTime(defaultStartTime)
      setEndDate(todayDate)
      setEndTime(defaultEndTime)

      setFormData({
        calendar_account_id: calendarAccountId,
        title: "",
        description: "",
        location: "",
        start_time: `${todayDate}T${defaultStartTime}:00`,
        end_time: `${todayDate}T${defaultEndTime}:00`,
        timezone: "UTC",
        is_all_day: false,
        status: "confirmed",
        visibility: "default",
        organizer_email: "",
        meeting_link: "",
        event_type: "event",
      })
    }
  }, [event, calendarAccountId])

  // Update datetime strings when date/time inputs change
  useEffect(() => {
    if (startDate && startTime && !formData.is_all_day) {
      const startDateTime = `${startDate}T${startTime}:00`
      setFormData((prev) => ({ ...prev, start_time: startDateTime }))
    }
    if (endDate && endTime && !formData.is_all_day) {
      const endDateTime = `${endDate}T${endTime}:00`
      setFormData((prev) => ({ ...prev, end_time: endDateTime }))
    }
  }, [startDate, startTime, endDate, endTime, formData.is_all_day])

  const handleAllDayChange = (checked: boolean) => {
    setFormData((prev) => ({ ...prev, is_all_day: checked }))
    if (checked) {
      const startDateTime = `${startDate}T00:00:00`
      const endDateTime = `${endDate}T23:59:59`
      setFormData((prev) => ({ ...prev, start_time: startDateTime, end_time: endDateTime }))
    }
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    try {
      await onSubmit(formData)
      onClose()
    } catch (error) {
      console.error("Failed to save event:", error)
    }
  }

  const selectedStatus = statusOptions.find((option) => option.value === formData.status)
  const selectedVisibility = visibilityOptions.find((option) => option.value === formData.visibility)
  const selectedTimezone = timezoneOptions.find((option) => option.value === formData.timezone)

  if (!isOpen) return null

  return (
    <>
      {/* Backdrop */}
      <div className="fixed inset-0 bg-black bg-opacity-50 z-50" onClick={onClose} />

      {/* Modal */}
      <Modal isOpen={isOpen} onClose={onClose} className="max-w-2xl w-full">
        <div>
          {/* Header */}
          <div className="flex justify-between items-center p-6 border-b border-gray-200">
            <h2 className="text-xl font-semibold text-gray-800">{event ? "Edit Event" : "Create Event"}</h2>
            <button
              onClick={onClose}
              className="p-2 text-gray-400 hover:text-gray-600 transition-colors rounded-full hover:bg-gray-100"
            >
              <X className="w-5 h-5" />
            </button>
          </div>

          {/* Content */}
          <div className="p-6 overflow-y-auto max-h-[80vh]">
            <form onSubmit={handleSubmit} className="space-y-6">
              {/* Title */}
              <div className="flex items-center space-x-3">
                <div className="text-gray-400">
                  <Calendar className="w-5 h-5" />
                </div>
                <input
                  type="text"
                  placeholder="Add title"
                  value={formData.title}
                  onChange={(e) => setFormData((prev) => ({ ...prev, title: e.target.value }))}
                  className="flex-1 text-lg border-0 outline-none focus:ring-0 placeholder-gray-400"
                  required
                />
              </div>

              {/* Date and Time */}
              <div className="flex items-center space-x-3">
                <div className="text-gray-400">
                  <Clock className="w-5 h-5" />
                </div>
                <div className="flex items-center space-x-2 flex-1">
                  <input
                    type="date"
                    value={startDate}
                    onChange={(e) => setStartDate(e.target.value)}
                    className="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 w-32"
                    required
                  />
                  {!formData.is_all_day && (
                    <>
                      <input
                        type="time"
                        value={startTime}
                        onChange={(e) => setStartTime(e.target.value)}
                        className="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 w-24"
                        required
                      />
                      <span className="text-gray-500">to</span>
                      <input
                        type="time"
                        value={endTime}
                        onChange={(e) => setEndTime(e.target.value)}
                        className="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 w-24"
                        required
                      />
                    </>
                  )}
                  <input
                    type="date"
                    value={endDate}
                    onChange={(e) => setEndDate(e.target.value)}
                    className="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 w-32"
                    required
                  />
                </div>
              </div>

              {/* All Day Event and Timezone */}
              <div className="flex items-center space-x-3">
                <div className="w-5"></div>
                <div className="flex items-center space-x-4 flex-1">
                  <div className="flex items-center space-x-2">
                    <input
                      type="checkbox"
                      id="all-day"
                      checked={formData.is_all_day}
                      onChange={(e) => handleAllDayChange(e.target.checked)}
                      className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                    />
                    <label htmlFor="all-day" className="text-sm text-gray-700">
                      All day event
                    </label>
                  </div>

                  <div className="relative">
                    <button
                      type="button"
                      onClick={() => setShowTimezoneDropdown(!showTimezoneDropdown)}
                      className="px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 focus:outline-none flex items-center space-x-1 border border-gray-300 rounded-md"
                    >
                      <span>{selectedTimezone?.label || "UTC"}</span>
                      <ChevronDown className="w-4 h-4" />
                    </button>
                    {showTimezoneDropdown && (
                      <div className="absolute right-0 mt-1 w-64 bg-white border border-gray-200 rounded-md shadow-lg z-10 max-h-48 overflow-y-auto">
                        <div className="py-1">
                          {timezoneOptions.map((timezone) => (
                            <button
                              key={timezone.value}
                              type="button"
                              onClick={() => {
                                setFormData((prev) => ({ ...prev, timezone: timezone.value }))
                                setShowTimezoneDropdown(false)
                              }}
                              className="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                            >
                              {timezone.label}
                            </button>
                          ))}
                        </div>
                      </div>
                    )}
                  </div>
                </div>
              </div>

              {/* Location */}
              <div className="flex items-center space-x-3">
                <div className="text-gray-400">
                  <MapPin className="w-5 h-5" />
                </div>
                <input
                  type="text"
                  placeholder="Add location"
                  value={formData.location}
                  onChange={(e) => setFormData((prev) => ({ ...prev, location: e.target.value }))}
                  className="flex-1 border-0 outline-none focus:ring-0 placeholder-gray-400"
                />
              </div>

              {/* Status and Visibility */}
              <div className="flex items-center space-x-3">
                <div className="text-gray-400">
                  <Users className="w-5 h-5" />
                </div>
                <div className="flex space-x-4 flex-1">
                  {/* Status Dropdown */}
                  <div className="relative flex-1">
                    <button
                      type="button"
                      onClick={() => setShowStatusDropdown(!showStatusDropdown)}
                      className="w-full px-3 py-2 text-left border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 flex items-center justify-between"
                    >
                      <span className={selectedStatus?.color || "text-gray-900"}>
                        {selectedStatus?.label || "Set status"}
                      </span>
                      <ChevronDown className="w-4 h-4 text-gray-400" />
                    </button>
                    {showStatusDropdown && (
                      <div className="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-md shadow-lg z-10">
                        <div className="py-1">
                          {statusOptions.map((status) => (
                            <button
                              key={status.value}
                              type="button"
                              onClick={() => {
                                setFormData((prev) => ({ ...prev, status: status.value as any }))
                                setShowStatusDropdown(false)
                              }}
                              className={`block w-full text-left px-4 py-2 text-sm hover:bg-gray-100 ${status.color}`}
                            >
                              {status.label}
                            </button>
                          ))}
                        </div>
                      </div>
                    )}
                  </div>

                  {/* Visibility Dropdown */}
                  <div className="relative flex-1">
                    <button
                      type="button"
                      onClick={() => setShowVisibilityDropdown(!showVisibilityDropdown)}
                      className="w-full px-3 py-2 text-left border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 flex items-center justify-between"
                    >
                      <div className="flex items-center space-x-2">
                        {selectedVisibility?.icon && <selectedVisibility.icon className="w-4 h-4" />}
                        <span className="text-gray-900">{selectedVisibility?.label || "Set visibility"}</span>
                      </div>
                      <ChevronDown className="w-4 h-4 text-gray-400" />
                    </button>
                    {showVisibilityDropdown && (
                      <div className="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-md shadow-lg z-10">
                        <div className="py-1">
                          {visibilityOptions.map((visibility) => (
                            <button
                              key={visibility.value}
                              type="button"
                              onClick={() => {
                                setFormData((prev) => ({ ...prev, visibility: visibility.value as any }))
                                setShowVisibilityDropdown(false)
                              }}
                              className="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center space-x-2"
                            >
                              <visibility.icon className="w-4 h-4" />
                              <span>{visibility.label}</span>
                            </button>
                          ))}
                        </div>
                      </div>
                    )}
                  </div>
                </div>
              </div>

              {/* Organizer Email */}
              <div className="flex items-center space-x-3">
                <div className="text-gray-400">
                  <Users className="w-5 h-5" />
                </div>
                <input
                  type="email"
                  placeholder="Organizer email"
                  value={formData.organizer_email}
                  onChange={(e) => setFormData((prev) => ({ ...prev, organizer_email: e.target.value }))}
                  className="flex-1 border-0 outline-none focus:ring-0 placeholder-gray-400"
                />
              </div>

              {/* Meeting Link */}
              <div className="flex items-center space-x-3">
                <div className="text-gray-400">
                  <Link className="w-5 h-5" />
                </div>
                <input
                  type="url"
                  placeholder="Meeting link"
                  value={formData.meeting_link}
                  onChange={(e) => setFormData((prev) => ({ ...prev, meeting_link: e.target.value }))}
                  className="flex-1 border-0 outline-none focus:ring-0 placeholder-gray-400"
                />
              </div>

              {/* Description */}
              <div className="flex items-start space-x-3">
                <div className="text-gray-400 mt-2">
                  <Type className="w-5 h-5" />
                </div>
                <div className="flex-1">
                  <textarea
                    placeholder="Add description..."
                    value={formData.description}
                    onChange={(e) => setFormData((prev) => ({ ...prev, description: e.target.value }))}
                    className="w-full min-h-[120px] border-0 outline-none resize-none focus:ring-0 placeholder-gray-400"
                  />
                </div>
              </div>

              {/* Submit Button */}
              <button
                type="submit"
                className="w-full bg-blue-500 hover:bg-blue-600 text-white py-3 rounded-lg font-medium disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                disabled={isLoading}
              >
                {isLoading ? "Saving..." : event ? "Update Event" : "Create Event"}
              </button>
            </form>
          </div>
        </div>
      </Modal>
    </>
  )
}
