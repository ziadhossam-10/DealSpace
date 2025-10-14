"use client"
import React, { useState, useRef } from "react"
import FullCalendar from "@fullcalendar/react"
import dayGridPlugin from "@fullcalendar/daygrid"
import timeGridPlugin from "@fullcalendar/timegrid"
import interactionPlugin from "@fullcalendar/interaction"
import type { EventInput, EventClickArg } from "@fullcalendar/core"
import { CalendarEvent, DealSyncable, useGetCalendarEventsQuery } from "./calendarEventsApi"
import { Modal } from "../../components/modal"
import { useModal } from "../../hooks/useModal"
import { TaskModal } from "../people/components/TaskModal" 
import { AppointmentModal } from "../people/components/AppointmentModal" 
import { useGetDealsWithClosingDateQuery } from "./calendarEventsApi"
import {
  Calendar,
  Clock,
  MapPin,
  User,
  ExternalLink,
  RefreshCw,
  AlertCircle,
  Edit,
  Trash2,
  CheckSquare,
  List,
  DollarSign,
  Tag,
} from "lucide-react"
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

interface DynamicCalendarProps {
  connectedAccount: CalendarAccount | null
}

interface ExtendedEventInput extends EventInput {
  extendedProps: {
    description?: string | null
    location?: string | null
    organizer_email?: string | null
    meeting_link?: string | null
    event_type: string
    status: string
    attendees: any[]
    reminders: any[]
    is_all_day: boolean
    original_data: any
    syncable?: any
    syncable_type?: string
    syncable_id?: number
  }
}

const DynamicCalendar: React.FC<DynamicCalendarProps> = ({ connectedAccount }) => {
  const [selectedEvent, setSelectedEvent] = useState<ExtendedEventInput | null>(null)
  const [calendarView, setCalendarView] = useState("dayGridMonth")
  const [dateRange, setDateRange] = useState({
    start: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split("T")[0],
    end: new Date(new Date().getFullYear(), new Date().getMonth() + 1, 0).toISOString().split("T")[0],
  })

  // Modal states
  const { isOpen, openModal, closeModal } = useModal()
  const [taskModalOpen, setTaskModalOpen] = useState(false)
  const [appointmentModalOpen, setAppointmentModalOpen] = useState(false)
  const [editingTask, setEditingTask] = useState<any>(null)
  const [editingAppointment, setEditingAppointment] = useState<any>(null)

  const calendarRef = useRef<FullCalendar>(null)
  const token = useAppSelector((state) => state.auth.token)

  // Fetch calendar events
  const {
    data: eventsData,
    isLoading,
    error,
    refetch,
  } = useGetCalendarEventsQuery(
    {
      start_date: dateRange.start,
      end_date: dateRange.end,
      calendar_account_id: connectedAccount ? Number.parseInt(connectedAccount.id) : undefined,
    },
    {
      skip: !connectedAccount,
      refetchOnMountOrArgChange: true,
    },
  )

  const {
    data: dealsData,
    isLoading: isLoadingDeals,
    error: dealsError,
  } = useGetDealsWithClosingDateQuery(
    {
      start_date: dateRange.start,
      end_date: dateRange.end,
    },
    {
      refetchOnMountOrArgChange: true,
    }
  )

  // Transform API events to FullCalendar format
const transformedEvents: ExtendedEventInput[] = React.useMemo(() => {
  const events: ExtendedEventInput[] = []

  // Add calendar events
  if (eventsData?.data) {
    eventsData.data.forEach((event: CalendarEvent) => {
      let title = event.title
      let description = event.description
      let location = event.location

      if (event.syncable && (event.event_type === "task" || event.event_type === "appointment")) {
        if (event.event_type === "task" && event.syncable && "name" in event.syncable) {
          title = event.syncable?.name 
          description = event.syncable.notes || event.description
        } else if (event.event_type === "appointment" && event.syncable && "title" in event.syncable) {
          title = event.syncable.title
          description = event.syncable.description || event.description
          location = event.syncable.location || event.location
        }
      }

      events.push({
        id: event.id.toString(),
        title: title,
        start: event.start_time,
        end: event.end_time,
        allDay: event.is_all_day,
        backgroundColor: event.color,
        borderColor: event.color,
        textColor: "#ffffff",
        extendedProps: {
          description: description,
          location: location,
          organizer_email: event.organizer_email || undefined,
          meeting_link: event.meeting_link || undefined,
          event_type: event.event_type,
          status: event.status,
          attendees: event.attendees || [],
          reminders: event.reminders || [],
          is_all_day: event.is_all_day,
          original_data: event,
          syncable: event.syncable,
          syncable_type: event.syncable_type || undefined,
          syncable_id: event.syncable_id ?? undefined,
        },
      })
    })
  }

  // Add deals with closing dates
  if (dealsData?.data?.items) {
    dealsData.data.items.forEach((deal: DealSyncable) => {
      if (deal.projected_close_date) {
        events.push({
          id: `deal-${deal.id}`,
          title: `Deal: ${deal.name}`,
          start: deal.projected_close_date,
          allDay: true,
          backgroundColor: deal.stage?.color || "#6b7280",
          borderColor: deal.stage?.color || "#6b7280",
          textColor: "#ffffff",
          extendedProps: {
            description: deal.description,
            event_type: "deal",
            status: "",
            is_all_day: true,
            original_data: deal,
            syncable: deal,
            syncable_type: "deal",
            syncable_id: deal.id,
            attendees: [], // Add default empty array for attendees
            reminders: [], // Add default empty array for reminders
          },
        })
      }
    })
  }

  return events
}, [eventsData, dealsData])

  // Handle date range changes
  const handleDatesSet = (dateInfo: any) => {
    setDateRange({
      start: dateInfo.start.toISOString().split("T")[0],
      end: dateInfo.end.toISOString().split("T")[0],
    })
  }

  // Handle event click
  const handleEventClick = (clickInfo: EventClickArg) => {
    const event = clickInfo.event as any
    setSelectedEvent({
      id: event.id,
      title: event.title,
      start: event.start?.toISOString(),
      end: event.end?.toISOString(),
      allDay: event.allDay,
      backgroundColor: event.backgroundColor,
      borderColor: event.borderColor,
      textColor: event.textColor,
      extendedProps: event.extendedProps,
    })
    openModal()
  }

  // API calls for CRUD operations
  const createTask = async (taskData: any) => {
    const response = await fetch(`${BASE_URL}/tasks`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Authorization: `Bearer ${token}`,
      },
      body: JSON.stringify(taskData),
    })

    if (!response.ok) {
      throw new Error("Failed to create task")
    }

    refetch()
  }

  const updateTask = async (taskId: number, taskData: any) => {
    const response = await fetch(`${BASE_URL}/tasks/${taskId}`, {
      method: "PUT",
      headers: {
        "Content-Type": "application/json",
        Authorization: `Bearer ${token}`,
      },
      body: JSON.stringify(taskData),
    })

    if (!response.ok) {
      throw new Error("Failed to update task")
    }

    refetch()
  }

  const deleteTask = async (taskId: number) => {
    const response = await fetch(`${BASE_URL}/tasks/${taskId}`, {
      method: "DELETE",
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })

    if (!response.ok) {
      throw new Error("Failed to delete task")
    }

    refetch()
  }

  const createAppointment = async (appointmentData: any) => {
    const response = await fetch(`${BASE_URL}/appointments`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Authorization: `Bearer ${token}`,
      },
      body: JSON.stringify(appointmentData),
    })

    if (!response.ok) {
      throw new Error("Failed to create appointment")
    }

    refetch()
  }

  const updateAppointment = async (appointmentId: number, appointmentData: any) => {
    const response = await fetch(`${BASE_URL}/appointments/${appointmentId}`, {
      method: "PUT",
      headers: {
        "Content-Type": "application/json",
        Authorization: `Bearer ${token}`,
      },
      body: JSON.stringify(appointmentData),
    })

    if (!response.ok) {
      throw new Error("Failed to update appointment")
    }

    refetch()
  }

  const deleteAppointment = async (appointmentId: number) => {
    const response = await fetch(`${BASE_URL}/appointments/${appointmentId}`, {
      method: "DELETE",
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })

    if (!response.ok) {
      throw new Error("Failed to delete appointment")
    }

    refetch()
  }

  // Handle task actions
  const handleCreateTask = () => {
    setEditingTask(null)
    setTaskModalOpen(true)
  }

  const handleEditTask = () => {
    if (selectedEvent?.extendedProps.syncable && selectedEvent.extendedProps.event_type === "task") {
      setEditingTask(selectedEvent.extendedProps.syncable)
      setTaskModalOpen(true)
      closeModal()
    }
  }

  const handleDeleteTask = async () => {
    if (selectedEvent?.extendedProps.syncable_id && selectedEvent.extendedProps.event_type === "task") {
      if (window.confirm("Are you sure you want to delete this task?")) {
        try {
          await deleteTask(selectedEvent.extendedProps.syncable_id)
          closeModal()
        } catch (error) {
          console.error("Failed to delete task:", error)
        }
      }
    }
  }

  // Handle appointment actions
  const handleCreateAppointment = () => {
    setEditingAppointment(null)
    setAppointmentModalOpen(true)
  }

  const handleEditAppointment = () => {
    if (selectedEvent?.extendedProps.syncable && selectedEvent.extendedProps.event_type === "appointment") {
      setEditingAppointment(selectedEvent.extendedProps.syncable)
      setAppointmentModalOpen(true)
      closeModal()
    }
  }

  const handleDeleteAppointment = async () => {
    if (selectedEvent?.extendedProps.syncable_id && selectedEvent.extendedProps.event_type === "appointment") {
      if (window.confirm("Are you sure you want to delete this appointment?")) {
        try {
          await deleteAppointment(selectedEvent.extendedProps.syncable_id)
          closeModal()
        } catch (error) {
          console.error("Failed to delete appointment:", error)
        }
      }
    }
  }

  // Get status badge classes
  const getStatusBadgeClasses = (status: string) => {
    switch (status) {
      case "confirmed":
        return "bg-green-100 text-green-800"
      case "tentative":
        return "bg-yellow-100 text-yellow-800"
      case "cancelled":
        return "bg-red-100 text-red-800"
      default:
        return "bg-gray-100 text-gray-800"
    }
  }

  // Get event type badge classes
  const getEventTypeBadgeClasses = (eventType: string) => {
    switch (eventType) {
      case "task":
        return "bg-red-100 text-red-800"
      case "appointment":
        return "bg-blue-100 text-blue-800"
      case "event":
        return "bg-purple-100 text-purple-800"
      default:
        return "bg-gray-100 text-gray-800"
    }
  }

  if (!connectedAccount) {
    return (
      <div className="flex items-center justify-center h-96 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
        <div className="text-center">
          <Calendar className="w-12 h-12 mx-auto mb-4 text-gray-400" />
          <h3 className="text-lg font-medium text-gray-900 mb-2">No Calendar Connected</h3>
          <p className="text-gray-500">Connect your calendar account to view and manage events</p>
        </div>
      </div>
    )
  }

  const isLoadingAll = isLoading || isLoadingDeals
  const errorAll = error || dealsError

  return (
    <div className="h-screen flex flex-col">
      {/* Action Bar */}
        <div className="flex items-center justify-between">
          <div className="flex items-center space-x-2 absolute right-6 top-[10px]">
            <button
              onClick={handleCreateTask}
              className="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
            >
              <CheckSquare className="w-4 h-4 mr-2" />
              New Task
            </button>
            <button
              onClick={handleCreateAppointment}
              className="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              <Calendar className="w-4 h-4 mr-2" />
              New Appointment
            </button>
          </div>
        </div>

      {/* Loading State */}
      {isLoadingAll && (
        <div className="flex items-center justify-center py-8 bg-white">
          <div className="flex items-center gap-2 text-gray-600">
            <RefreshCw className="w-5 h-5 animate-spin" />
            <span>Loading events...</span>
          </div>
        </div>
      )}

      {/* Error State */}
      {errorAll && (
        <div className="mx-6 mt-4">
          <div className="flex items-center gap-2 p-4 bg-red-50 border border-red-200 rounded-lg">
            <AlertCircle className="h-4 w-4 text-red-600 flex-shrink-0" />
            <span className="text-sm text-red-700">Failed to load events. Please try again.</span>
          </div>
        </div>
      )}

      {/* Calendar - Full Screen */}
      <div className="flex-1 bg-white overflow-hidden">
        <div className="h-full custom-calendar">
          <FullCalendar
            ref={calendarRef}
            plugins={[dayGridPlugin, timeGridPlugin, interactionPlugin]}
            initialView="dayGridMonth"
            headerToolbar={{
              left: "prev,next today",
              center: "title",
              right: "dayGridMonth,timeGridWeek,timeGridDay",
            }}
            events={transformedEvents}
            eventClick={handleEventClick}
            datesSet={handleDatesSet}
            height="100%"
            eventContent={renderEventContent}
            eventDisplay="block"
            dayMaxEvents={3}
            moreLinkClick="popover"
            aspectRatio={1.35}
          />
        </div>
      </div>

      {/* Event Details Modal */}
      <Modal isOpen={isOpen} onClose={closeModal} className="max-w-2xl p-6">
        {selectedEvent && (
          <div className="space-y-6">
            <div className="flex items-start justify-between">
              <div className="flex-1">
                <h3 className="text-xl font-semibold text-gray-900 mb-2">{selectedEvent.title}</h3>
                <div className="flex items-center gap-2 mb-4">
                  <span
                    className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getEventTypeBadgeClasses(
                      selectedEvent.extendedProps.event_type,
                    )}`}
                  >
                    {selectedEvent.extendedProps.event_type}
                  </span>
                  {
                    selectedEvent.extendedProps.status && (
                      <span
                        className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getStatusBadgeClasses(
                          selectedEvent.extendedProps.status,
                        )}`}
                      >
                        {selectedEvent.extendedProps.status}
                      </span>
                    )
                  }
                </div>
              </div>
            </div>
          {selectedEvent.extendedProps.event_type === "deal" && (
            <div className="space-y-4">
              {/* Deal Price */}
              <div className="flex items-center gap-3">
                <DollarSign className="w-5 h-5 text-gray-400 flex-shrink-0" />
                <div>
                  <p className="font-medium text-gray-900">Deal Value</p>
                  <p className="text-sm text-gray-600">
                    ${selectedEvent.extendedProps.syncable?.price?.toLocaleString() || "0"}
                  </p>
                </div>
              </div>

              {/* Deal Stage */}
              {selectedEvent.extendedProps.syncable?.stage && (
                <div className="flex items-center gap-3">
                  <Tag className="w-5 h-5 text-gray-400 flex-shrink-0" />
                  <div>
                    <p className="font-medium text-gray-900">Stage</p>
                    <div className="flex items-center gap-2">
                      <span
                        className="w-3 h-3 rounded-full"
                        style={{ backgroundColor: selectedEvent.extendedProps.syncable.stage.color }}
                      ></span>
                      <p className="text-sm text-gray-600">{selectedEvent.extendedProps.syncable.stage.name}</p>
                    </div>
                  </div>
                </div>
              )}

              {/* Deal Type */}
              {selectedEvent.extendedProps.syncable?.type && (
                <div className="flex items-center gap-3">
                  <List className="w-5 h-5 text-gray-400 flex-shrink-0" />
                  <div>
                    <p className="font-medium text-gray-900">Type</p>
                    <p className="text-sm text-gray-600">{selectedEvent.extendedProps.syncable.type.name}</p>
                  </div>
                </div>
              )}

            </div>
          )}

            <div className="space-y-4">
              {/* Date and Time */}
              {
                (selectedEvent.extendedProps.event_type === "task" || selectedEvent.extendedProps.event_type === "appointment") && (
                  <div className="flex items-center gap-3">
                    <Clock className="w-5 h-5 text-gray-400 flex-shrink-0" />
                    <div>
                      <p className="font-medium text-gray-900">
                        {selectedEvent.extendedProps.is_all_day ? "All Day" : "Timed Event"}
                      </p>
                      <p className="text-sm text-gray-600">
                        {selectedEvent.start && new Date(selectedEvent.start.toString()).toLocaleString()}
                        {selectedEvent.end && ` - ${new Date(selectedEvent.end.toString()).toLocaleString()}`}
                      </p>
                    </div>
                  </div>
                )
              }

              {/* Location */}
              {selectedEvent.extendedProps.location && (
                <div className="flex items-center gap-3">
                  <MapPin className="w-5 h-5 text-gray-400 flex-shrink-0" />
                  <div>
                    <p className="font-medium text-gray-900">Location</p>
                    <p className="text-sm text-gray-600">{selectedEvent.extendedProps.location}</p>
                  </div>
                </div>
              )}

              {/* Organizer */}
              {selectedEvent.extendedProps.organizer_email && (
                <div className="flex items-center gap-3">
                  <User className="w-5 h-5 text-gray-400 flex-shrink-0" />
                  <div>
                    <p className="font-medium text-gray-900">Organizer</p>
                    <p className="text-sm text-gray-600">{selectedEvent.extendedProps.organizer_email}</p>
                  </div>
                </div>
              )}

              {/* Notes */}
              {selectedEvent.extendedProps.syncable?.notes &&  (
                <div>
                  <p className="font-medium text-gray-900 mb-2">Notes</p>
                  <div className="text-sm text-gray-600 bg-gray-50 p-3 rounded-lg">
                    {selectedEvent.extendedProps.syncable?.notes}
                  </div>
                </div>
              )}

              {/* Description */}
              {selectedEvent.extendedProps.syncable?.description &&  (
                <div>
                  <p className="font-medium text-gray-900 mb-2">Description</p>
                  <div className="text-sm text-gray-600 bg-gray-50 p-3 rounded-lg">
                    {selectedEvent.extendedProps.syncable?.description}
                  </div>
                </div>
              )}

              {/* Meeting Link */}
              {selectedEvent.extendedProps.meeting_link && (
                <div>
                  <button
                    onClick={() => {
                      if (selectedEvent.extendedProps.meeting_link) {
                        window.open(selectedEvent.extendedProps.meeting_link, "_blank")
                      }
                    }}
                    className="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                  >
                    <ExternalLink className="w-4 h-4" />
                    Join Meeting
                  </button>
                </div>
              )}

              {/* Attendees */}
              {selectedEvent.extendedProps.attendees.length > 0 && (
                <div>
                  <p className="font-medium text-gray-900 mb-2">
                    Attendees ({selectedEvent.extendedProps.attendees.length})
                  </p>
                  <div className="space-y-1">
                    {selectedEvent.extendedProps.attendees.map((attendee: any, index: number) => (
                      <p key={index} className="text-sm text-gray-600">
                        {attendee.email} {attendee.name && `(${attendee.name})`}
                      </p>
                    ))}
                  </div>
                </div>
              )}

              {/* Task/Appointment specific info */}
              {selectedEvent.extendedProps.syncable && (selectedEvent.extendedProps.event_type === "task" || selectedEvent.extendedProps.event_type === "appointment")  && (
                <div>
                  <p className="font-medium text-gray-900 mb-2">Additional Details</p>
                  <div className="text-sm text-gray-600 bg-gray-50 p-3 rounded-lg">
                    {selectedEvent.extendedProps.event_type === "task" && (
                      <div className="space-y-1">
                        <p>
                          <strong>Type:</strong> {selectedEvent.extendedProps.syncable.type}
                        </p>
                        <p>
                          <strong>Priority:</strong> {selectedEvent.extendedProps.syncable.priority || "Medium"}
                        </p>
                        <p>
                          <strong>Status:</strong>{" "}
                          {selectedEvent.extendedProps.syncable.is_completed ? "Completed" : "Pending"}
                        </p>
                        {selectedEvent.extendedProps.syncable.assigned_user_id && (
                          <p>
                            <strong>Assigned to:</strong> User ID{" "}
                            {selectedEvent.extendedProps.syncable.assigned_user_id}
                          </p>
                        )}
                      </div>
                    )}
                    {selectedEvent.extendedProps.event_type === "appointment" && (
                      <div className="space-y-1">
                        <p>
                          <strong>All Day:</strong> {selectedEvent.extendedProps.syncable.all_day ? "Yes" : "No"}
                        </p>
                        {selectedEvent.extendedProps.syncable.type_id && (
                          <p>
                            <strong>Type ID:</strong> {selectedEvent.extendedProps.syncable.type_id}
                          </p>
                        )}
                        {selectedEvent.extendedProps.syncable.outcome_id && (
                          <p>
                            <strong>Outcome ID:</strong> {selectedEvent.extendedProps.syncable.outcome_id}
                          </p>
                        )}
                      </div>
                    )}
                  </div>
                </div>
              )}
            </div>

            <div className="flex justify-between pt-4 border-t border-gray-200">
              <div className="flex space-x-2">
                {/* Edit/Delete actions for tasks and appointments */}
                {selectedEvent.extendedProps.syncable && (
                  <>
                    {selectedEvent.extendedProps.event_type === "task" && (
                      <>
                        <button
                          onClick={handleEditTask}
                          className="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                          <Edit className="w-4 h-4 mr-2" />
                          Edit Task
                        </button>
                        <button
                          onClick={handleDeleteTask}
                          className="inline-flex items-center px-3 py-2 border border-red-300 text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                        >
                          <Trash2 className="w-4 h-4 mr-2" />
                          Delete Task
                        </button>
                      </>
                    )}
                    {selectedEvent.extendedProps.event_type === "appointment" && (
                      <>
                        <button
                          onClick={handleEditAppointment}
                          className="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                          <Edit className="w-4 h-4 mr-2" />
                          Edit Appointment
                        </button>
                        <button
                          onClick={handleDeleteAppointment}
                          className="inline-flex items-center px-3 py-2 border border-red-300 text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                        >
                          <Trash2 className="w-4 h-4 mr-2" />
                          Delete Appointment
                        </button>
                      </>
                    )}
                  </>
                )}
              </div>
              <button
                onClick={closeModal}
                className="px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
              >
                Close
              </button>
            </div>
          </div>
        )}
      </Modal>

      {/* Task Modal */}
      <TaskModal
        isOpen={taskModalOpen}
        onClose={() => {
          setTaskModalOpen(false)
          setEditingTask(null)
        }}
        onSubmit={async (taskData) => {
          if (editingTask) {
            await updateTask(editingTask.id, taskData)
          } else {
            await createTask(taskData)
          }
          setTaskModalOpen(false)
          setEditingTask(null)
        }}
        task={editingTask}
        users={[]} // You'll need to fetch users
        isLoading={false}
      />

      {/* Appointment Modal */}
      <AppointmentModal
        isOpen={appointmentModalOpen}
        onClose={() => {
          setAppointmentModalOpen(false)
          setEditingAppointment(null)
        }}
        onSubmit={async (appointmentData) => {
          if (editingAppointment) {
            await updateAppointment(editingAppointment.id, appointmentData)
          } else {
            await createAppointment(appointmentData)
          }
          setAppointmentModalOpen(false)
          setEditingAppointment(null)
        }}
        appointment={editingAppointment}
        appointmentTypes={[]} // You'll need to fetch appointment types
        appointmentOutcomes={[]} // You'll need to fetch appointment outcomes
        users={[]} // You'll need to fetch users
        people={[]} // You'll need to fetch people
        isLoading={false}
      />
    </div>
  )
}

// Custom event content renderer
const renderEventContent = (eventInfo: any) => {
  const { event } = eventInfo
  const eventType = event.extendedProps.event_type

  return (
    <div className="fc-event-main-frame p-1">
      <div className="fc-event-time text-xs opacity-75">{eventInfo.timeText}</div>
      <div className="fc-event-title text-xs font-medium truncate">
        {eventType === "deal" ? `💰 ${event.title}` : event.title}
      </div>
      {event.extendedProps.location && (
        <div className="fc-event-location text-xs opacity-75 truncate">📍 {event.extendedProps.location}</div>
      )}
      {eventType === "task" && event.extendedProps.syncable?.is_completed && (
        <div className="text-xs opacity-75">✅ Completed</div>
      )}
      {eventType === "deal" && event.extendedProps.syncable?.price && (
        <div className="text-xs opacity-75">
          ${event.extendedProps.syncable.price.toLocaleString()}
        </div>
      )}
    </div>
  )
}

export default DynamicCalendar
