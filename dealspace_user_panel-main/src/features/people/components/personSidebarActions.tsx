"use client"

import { useState } from "react"
import {
  CheckSquare,
  Calendar,
  Clock,
  Users,
  File,
  Activity,
  Plus,
  ChevronRight,
  X,
  FileText,
  ImageIcon,
  Download,
  Trash2,
  Upload,
  Edit,
} from "lucide-react"
import { ASSETS_URL, getInitials } from "../../../utils/helpers"
import { PersonDealsSection } from "./PersonDealsSection"
import type { Deal } from "../../../types/deals"
import { Appointment } from "../appointmentsApi"
import { Task } from "../tasksApi"
import { PersonEvent } from "../../../types/people"
import { PersonEventsSection } from "./PersonEventsSection"

interface FileData {
  id: number
  name: string
  description: string | null
  size: number
  type: string
  path: string
}

interface PersonSidebarActionsProps {
  contact: any
  onAddCollaborator: () => void
  onDeleteCollaborator: (id: number) => void
  files: FileData[]
  onAddFile: () => void
  onDeleteFile: (fileId: number) => void
  onDownloadFile: (file: FileData) => void
  isLoadingFiles?: boolean
  // Deal-related props
  deals: Deal[]
  totalDeals: number
  currentDealPage: number
  hasMoreDeals: boolean
  isLoadingDeals: boolean
  isLoadingMoreDeals: boolean
  onAddDeal: () => void
  onEditDeal: (deal: Deal) => void
  onDeleteDeal: (deal: Deal) => void
  onLoadMoreDeals: () => void
  // Appointment-related props
  appointments: Appointment[]
  totalAppointments: number
  currentAppointmentPage: number
  hasMoreAppointments: boolean
  isLoadingAppointments: boolean
  isLoadingMoreAppointments: boolean
  onAddAppointment: () => void
  onEditAppointment: (appointment: Appointment) => void
  onDeleteAppointment: (appointment: Appointment) => Promise<void>
  onLoadMoreAppointments: () => void
  // Task-related props
  tasks: Task[]
  totalTasks: number
  currentTaskPage: number
  hasMoreTasks: boolean
  isLoadingTasks: boolean
  isLoadingMoreTasks: boolean
  onAddTask: () => void
  onEditTask: (task: Task) => void
  onDeleteTask: (task: Task) => Promise<void>
  onCompleteTask: (task: Task) => Promise<void>
  onLoadMoreTasks: () => void
  events: PersonEvent[]
  totalEvents: number
  currentEventPage: number
  hasMoreEvents: boolean
  isLoadingEvents: boolean
  isLoadingMoreEvents: boolean
  onLoadMoreEvents: () => void
  onEventFilterChange: (filters: any) => void
}

export const PersonSidebarActions = ({
  contact,
  onAddCollaborator,
  onDeleteCollaborator,
  files,
  onAddFile,
  onDeleteFile,
  onDownloadFile,
  isLoadingFiles = false,
  deals,
  totalDeals,
  currentDealPage,
  hasMoreDeals,
  isLoadingDeals,
  isLoadingMoreDeals,
  onAddDeal,
  onEditDeal,
  onDeleteDeal,
  onLoadMoreDeals,
  appointments,
  totalAppointments,
  currentAppointmentPage,
  hasMoreAppointments,
  isLoadingAppointments,
  isLoadingMoreAppointments,
  onAddAppointment,
  onEditAppointment,
  onDeleteAppointment,
  onLoadMoreAppointments,
  tasks,
  totalTasks,
  currentTaskPage,
  hasMoreTasks,
  isLoadingTasks,
  isLoadingMoreTasks,
  onAddTask,
  onEditTask,
  onDeleteTask,
  onCompleteTask,
  onLoadMoreTasks,
  events,
  totalEvents,
  currentEventPage,
  hasMoreEvents,
  isLoadingEvents,
  isLoadingMoreEvents,
  onLoadMoreEvents,
  onEventFilterChange,
}: PersonSidebarActionsProps) => {
  const [collapsibleStates, setCollapsibleStates] = useState({
    deals: false,
    tasks: false,
    appointments: false,
    actionPlans: false,
    collaborators: false,
    files: false,
    websiteActivity: false,
  })

  const toggleCollapsible = (section: keyof typeof collapsibleStates) => {
    setCollapsibleStates((prevState) => ({
      ...prevState,
      [section]: !prevState[section],
    }))
  }
  

  const formatFileSize = (bytes: number): string => {
    if (bytes === 0) return "0 Bytes"
    const k = 1024
    const sizes = ["Bytes", "KB", "MB", "GB"]
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    return Number.parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i]
  }

  const getFileIcon = (type: string) => {
    if (type.startsWith("image/")) {
      return <ImageIcon className="w-3 h-3 text-blue-500" />
    }
    return <FileText className="w-3 h-3 text-gray-500" />
  }

  const getFileTypeColor = (type: string) => {
    if (type.startsWith("image/")) return "bg-blue-100 text-blue-800"
    if (type.includes("pdf")) return "bg-red-100 text-red-800"
    if (type.includes("document") || type.includes("word")) return "bg-blue-100 text-blue-800"
    if (type.includes("spreadsheet") || type.includes("excel")) return "bg-green-100 text-green-800"
    return "bg-gray-100 text-gray-800"
  }

  const getAppointmentStatusColor = (status: string) => {
    switch (status.toLowerCase()) {
      case "today":
        return "bg-green-100 text-green-800"
      case "tomorrow":
        return "bg-blue-100 text-blue-800"
      case "upcoming":
        return "bg-yellow-100 text-yellow-800"
      case "past":
        return "bg-gray-100 text-gray-800"
      default:
        return "bg-gray-100 text-gray-800"
    }
  }

  const getTaskPriorityColor = (priority: string) => {
    switch (priority.toLowerCase()) {
      case "urgent":
        return "bg-red-100 text-red-800"
      case "high":
        return "bg-orange-100 text-orange-800"
      case "medium":
        return "bg-yellow-100 text-yellow-800"
      case "low":
        return "bg-green-100 text-green-800"
      default:
        return "bg-gray-100 text-gray-800"
    }
  }

  const getTaskStatusColor = (status: string, isCompleted: boolean) => {
    if (isCompleted) return "bg-green-100 text-green-800"
    switch (status.toLowerCase()) {
      case "in progress":
        return "bg-blue-100 text-blue-800"
      case "pending":
        return "bg-yellow-100 text-yellow-800"
      case "cancelled":
        return "bg-red-100 text-red-800"
      default:
        return "bg-gray-100 text-gray-800"
    }
  }

  return (
    <div className="w-80 min-w-[300px] bg-white overflow-y-auto">
      {/* Deals Section */}
      <PersonDealsSection
        personId={contact?.id}
        deals={deals}
        totalDeals={totalDeals}
        currentPage={currentDealPage}
        hasMore={hasMoreDeals}
        isLoading={isLoadingDeals}
        isLoadingMore={isLoadingMoreDeals}
        isExpanded={collapsibleStates.deals}
        onToggleExpanded={() => toggleCollapsible("deals")}
        onAddDeal={onAddDeal}
        onEditDeal={onEditDeal}
        onDeleteDeal={onDeleteDeal}
        onLoadMore={onLoadMoreDeals}
      />

      {/* Tasks Section */}
      <div className="p-4 border-b">
        <div className="w-full">
          <div className="flex items-center justify-between">
            <div className="flex items-center">
              <CheckSquare size={16} className="mr-2 text-gray-500" />
              <span className="font-medium">Tasks </span>
            </div>
            <div className="flex items-center">
              <button
                className="h-8 w-8 p-0 rounded-full flex items-center justify-center hover:bg-gray-100"
                onClick={onAddTask}
              >
                <Plus size={16} className="text-blue-500" />
              </button>
              <button
                className="h-8 w-8 p-0 flex items-center justify-center hover:bg-gray-100"
                onClick={() => toggleCollapsible("tasks")}
              >
                <ChevronRight
                  size={16}
                  className={`text-gray-500 transition-transform ${collapsibleStates.tasks ? "rotate-90" : ""}`}
                />
              </button>
            </div>
          </div>
          {collapsibleStates.tasks && (
            <div className="pt-2 pb-1">
              {isLoadingTasks ? (
                <div className="flex items-center justify-center py-4">
                  <div className="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
                </div>
              ) : tasks.length > 0 ? (
                <div className="space-y-3 max-h-60 overflow-y-auto">
                  {tasks.map((task) => (
                    <div key={task.id} className="p-3 bg-gray-50 rounded-md group">
                      <div className="flex items-start justify-between">
                        <div className="flex-1 min-w-0">
                          <div className="flex items-center gap-2 mb-1">
                            <button
                              onClick={() => onCompleteTask(task)}
                              className={`w-4 h-4 rounded border-2 flex items-center justify-center transition-colors ${
                                task.is_completed
                                  ? "bg-green-500 border-green-500 text-white"
                                  : "border-gray-300 hover:border-green-500"
                              }`}
                            >
                              {task.is_completed && <CheckSquare className="w-3 h-3" />}
                            </button>
                            <h4
                              className={`text-sm font-medium truncate ${
                                task.is_completed ? "line-through text-gray-500" : "text-gray-900"
                              }`}
                            >
                              {task.name}
                            </h4>
                            <span className={`text-xs px-2 py-0.5 rounded-full ${getTaskPriorityColor(task.priority)}`}>
                              {task.priority}
                            </span>
                          </div>
                          <div className="flex items-center gap-2 mb-1">
                            <span
                              className={`text-xs px-2 py-0.5 rounded-full ${getTaskStatusColor(task.status, task.is_completed)}`}
                            >
                              {task.is_completed ? "Completed" : task.status}
                            </span>
                            <span className="text-xs text-gray-500">📋 {task.type}</span>
                          </div>
                          <p className="text-xs text-gray-600 mb-1">📅 {task.formatted_due_date}</p>
                          {task.is_overdue && !task.is_completed && (
                            <p className="text-xs text-red-600 font-medium">⚠️ Overdue</p>
                          )}
                          {task.is_due_today && !task.is_completed && (
                            <p className="text-xs text-orange-600 font-medium">🔔 Due today</p>
                          )}
                          {task.assigned_user && (
                            <p className="text-xs text-gray-500 mt-1">👤 Assigned to {task.assigned_user.name}</p>
                          )}
                          {task.notes && <p className="text-xs text-gray-400 mt-1 truncate">💬 {task.notes}</p>}
                        </div>
                        <div className="flex items-center space-x-1 ml-2 opacity-0 group-hover:opacity-100 transition-opacity">
                          <button
                            onClick={() => onEditTask(task)}
                            className="p-1 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded transition-colors"
                            title="Edit task"
                          >
                            <Edit className="w-3 h-3" />
                          </button>
                          <button
                            onClick={() => onDeleteTask(task)}
                            className="p-1 text-red-600 hover:text-red-800 hover:bg-red-50 rounded transition-colors"
                            title="Delete task"
                          >
                            <Trash2 className="w-3 h-3" />
                          </button>
                        </div>
                      </div>
                    </div>
                  ))}
                  {hasMoreTasks && (
                    <button
                      onClick={onLoadMoreTasks}
                      disabled={isLoadingMoreTasks}
                      className="w-full text-sm text-blue-600 hover:text-blue-800 py-2"
                    >
                      {isLoadingMoreTasks ? "Loading..." : "Load more tasks"}
                    </button>
                  )}
                </div>
              ) : (
                <div className="text-center py-4">
                  <CheckSquare className="w-8 h-8 text-gray-400 mx-auto mb-2" />
                  <p className="text-gray-500 text-xs mb-2">No tasks created</p>
                  <button onClick={onAddTask} className="text-xs text-blue-600 hover:text-blue-800 underline">
                    Create first task
                  </button>
                </div>
              )}
            </div>
          )}
        </div>
      </div>

      {/* Appointments Section */}
      <div className="p-4 border-b">
        <div className="w-full">
          <div className="flex items-center justify-between">
            <div className="flex items-center">
              <Calendar size={16} className="mr-2 text-gray-500" />
              <span className="font-medium">Appointments</span>
            </div>
            <div className="flex items-center">
              <button
                className="h-8 w-8 p-0 rounded-full flex items-center justify-center hover:bg-gray-100"
                onClick={onAddAppointment}
              >
                <Plus size={16} className="text-blue-500" />
              </button>
              <button
                className="h-8 w-8 p-0 flex items-center justify-center hover:bg-gray-100"
                onClick={() => toggleCollapsible("appointments")}
              >
                <ChevronRight
                  size={16}
                  className={`text-gray-500 transition-transform ${collapsibleStates.appointments ? "rotate-90" : ""}`}
                />
              </button>
            </div>
          </div>
          {collapsibleStates.appointments && (
            <div className="pt-2 pb-1">
              {isLoadingAppointments ? (
                <div className="flex items-center justify-center py-4">
                  <div className="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
                </div>
              ) : appointments.length > 0 ? (
                <div className="space-y-3 max-h-60 overflow-y-auto">
                  {appointments.map((appointment) => (
                    <div key={appointment.id} className="p-3 bg-gray-50 rounded-md group">
                      <div className="flex items-start justify-between">
                        <div className="flex-1 min-w-0">
                          <div className="flex items-center gap-2 mb-1">
                            <h4 className="text-sm font-medium text-gray-900 truncate">{appointment.title}</h4>
                            <span
                              className={`text-xs px-2 py-0.5 rounded-full ${getAppointmentStatusColor(appointment.status)}`}
                            >
                              {appointment.status}
                            </span>
                          </div>
                          <p className="text-xs text-gray-600 mb-1">📅 {appointment.formatted_date_range}</p>
                          {appointment.location && (
                            <p className="text-xs text-gray-500 mb-1">📍 {appointment.location}</p>
                          )}
                          {appointment.invitee_names.length > 0 && (
                            <p className="text-xs text-gray-500 mb-1">
                              👥 {appointment.invitee_names.slice(0, 2).join(", ")}
                              {appointment.invitee_names.length > 2 && ` +${appointment.invitee_names.length - 2} more`}
                            </p>
                          )}
                          {appointment.type && <p className="text-xs text-blue-600 mt-1">🏷️ {appointment.type.name}</p>}
                          {appointment.outcome && (
                            <p className="text-xs text-green-600">✅ {appointment.outcome.name}</p>
                          )}
                        </div>
                        <div className="flex items-center space-x-1 ml-2 opacity-0 group-hover:opacity-100 transition-opacity">
                          <button
                            onClick={() => onEditAppointment(appointment)}
                            className="p-1 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded transition-colors"
                            title="Edit appointment"
                          >
                            <Edit className="w-3 h-3" />
                          </button>
                          <button
                            onClick={() => onDeleteAppointment(appointment)}
                            className="p-1 text-red-600 hover:text-red-800 hover:bg-red-50 rounded transition-colors"
                            title="Delete appointment"
                          >
                            <Trash2 className="w-3 h-3" />
                          </button>
                        </div>
                      </div>
                    </div>
                  ))}
                  {hasMoreAppointments && (
                    <button
                      onClick={onLoadMoreAppointments}
                      disabled={isLoadingMoreAppointments}
                      className="w-full text-sm text-blue-600 hover:text-blue-800 py-2"
                    >
                      {isLoadingMoreAppointments ? "Loading..." : "Load more appointments"}
                    </button>
                  )}
                </div>
              ) : (
                <div className="text-center py-4">
                  <Calendar className="w-8 h-8 text-gray-400 mx-auto mb-2" />
                  <p className="text-gray-500 text-xs mb-2">No appointments scheduled</p>
                  <button onClick={onAddAppointment} className="text-xs text-blue-600 hover:text-blue-800 underline">
                    Schedule first appointment
                  </button>
                </div>
              )}
            </div>
          )}
        </div>
      </div>

      {/* Action Plans Section */}
      <div className="p-4 border-b">
        <div className="w-full">
          <div className="flex items-center justify-between">
            <div className="flex items-center">
              <Clock size={16} className="mr-2 text-gray-500" />
              <span className="font-medium">Action Plans</span>
            </div>
            <div className="flex items-center">
              <button className="h-8 w-8 p-0 rounded-full flex items-center justify-center hover:bg-gray-100">
                <Plus size={16} className="text-blue-500" />
              </button>
              <button
                className="h-8 w-8 p-0 flex items-center justify-center hover:bg-gray-100"
                onClick={() => toggleCollapsible("actionPlans")}
              >
                <ChevronRight
                  size={16}
                  className={`text-gray-500 transition-transform ${collapsibleStates.actionPlans ? "rotate-90" : ""}`}
                />
              </button>
            </div>
          </div>
          {collapsibleStates.actionPlans && <div className="mt-2 text-gray-500 text-sm">No action plans found</div>}
        </div>
      </div>

      {/* Collaborators Section */}
      <div className="p-4 border-b">
        <div className="w-full">
          <div className="flex items-center justify-between">
            <div className="flex items-center">
              <Users size={16} className="mr-2 text-gray-500" />
              <span className="font-medium">Collaborators</span>
            </div>
            <div className="flex items-center">
              <button
                className="h-8 w-8 p-0 rounded-full flex items-center justify-center hover:bg-gray-100"
                onClick={onAddCollaborator}
              >
                <Plus size={16} className="text-blue-500" />
              </button>
              <button
                className="h-8 w-8 p-0 flex items-center justify-center hover:bg-gray-100"
                onClick={() => toggleCollapsible("collaborators")}
              >
                <ChevronRight
                  size={16}
                  className={`text-gray-500 transition-transform ${collapsibleStates.collaborators ? "rotate-90" : ""}`}
                />
              </button>
            </div>
          </div>
          {collapsibleStates.collaborators && (
            <div className="pt-2 pb-1">
              {(contact?.collaborators ?? []).length > 0 ? (
                <div className="space-y-2">
                  {contact?.collaborators?.map((collaborator: any) => (
                    <div
                      key={collaborator.id}
                      className="flex items-center justify-between p-2 bg-gray-50 rounded-md group"
                    >
                      <div className="flex items-center space-x-2">
                        <div className="h-8 w-8 bg-gray-200 text-gray-600 text-sm rounded-full flex items-center justify-center">
                          {collaborator.avatar ? (
                            <img
                              src={ASSETS_URL + "/storage/" + collaborator.avatar || "/placeholder.svg"}
                              alt={collaborator.name}
                              className="h-full w-full object-cover rounded-full"
                            />
                          ) : (
                            <span>{getInitials(collaborator.name)}</span>
                          )}
                        </div>
                        <div>
                          <div className="font-medium text-sm text-gray-900">{collaborator.name}</div>
                          <div className="text-xs text-gray-500">{collaborator.email}</div>
                        </div>
                      </div>
                      <button
                        onClick={() => onDeleteCollaborator(collaborator.id)}
                        className="opacity-0 group-hover:opacity-100 p-1 text-gray-400 hover:text-red-500 rounded-full hover:bg-gray-200 transition-all"
                      >
                        <X size={14} />
                      </button>
                    </div>
                  ))}
                </div>
              ) : (
                <p className="text-gray-500 text-sm">No collaborators</p>
              )}
            </div>
          )}
        </div>
      </div>

      {/* Files Section */}
      <div className="p-4 border-b">
        <div className="w-full">
          <div className="flex items-center justify-between">
            <div className="flex items-center">
              <File size={16} className="mr-2 text-gray-500" />
              <span className="font-medium">Files</span>
            </div>
            <div className="flex items-center">
              <button
                className="h-8 w-8 p-0 rounded-full flex items-center justify-center hover:bg-gray-100"
                onClick={onAddFile}
              >
                <Plus size={16} className="text-blue-500" />
              </button>
              <button
                className="h-8 w-8 p-0 flex items-center justify-center hover:bg-gray-100"
                onClick={() => toggleCollapsible("files")}
              >
                <ChevronRight
                  size={16}
                  className={`text-gray-500 transition-transform ${collapsibleStates.files ? "rotate-90" : ""}`}
                />
              </button>
            </div>
          </div>
          {collapsibleStates.files && (
            <div className="pt-2 pb-1">
              {isLoadingFiles ? (
                <div className="flex items-center justify-center py-4">
                  <div className="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
                </div>
              ) : files.length > 0 ? (
                <div className="space-y-2 max-h-60 overflow-y-auto">
                  {files.map((file) => (
                    <div key={file.id} className="flex items-center justify-between p-2 bg-gray-50 rounded-md group">
                      <div className="flex items-center space-x-2 flex-1 min-w-0">
                        {getFileIcon(file.type)}
                        <div className="flex-1 min-w-0">
                          <div className="flex items-center gap-1 mb-1">
                            <p className="text-xs font-medium text-gray-900 truncate">{file.name}</p>
                            <span className={`text-xs px-1 py-0.5 rounded ${getFileTypeColor(file.type)}`}>
                              {file.type.split("/")[1]?.toUpperCase().slice(0, 3) || "FILE"}
                            </span>
                          </div>
                          <div className="flex items-center gap-2 text-xs text-gray-500">
                            <span>{formatFileSize(file.size)}</span>
                          </div>
                          {file.description && (
                            <p className="text-xs text-gray-400 truncate mt-1">{file.description}</p>
                          )}
                        </div>
                      </div>
                      <div className="flex items-center space-x-1 ml-2 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button
                          onClick={() => onDownloadFile(file)}
                          className="p-1 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded transition-colors"
                          title="Download file"
                        >
                          <Download className="w-3 h-3" />
                        </button>
                        <button
                          onClick={() => onDeleteFile(file.id)}
                          className="p-1 text-red-600 hover:text-red-800 hover:bg-red-50 rounded transition-colors"
                          title="Delete file"
                        >
                          <Trash2 className="w-3 h-3" />
                        </button>
                      </div>
                    </div>
                  ))}
                </div>
              ) : (
                <div className="text-center py-4">
                  <Upload className="w-8 h-8 text-gray-400 mx-auto mb-2" />
                  <p className="text-gray-500 text-xs mb-2">No files uploaded</p>
                  <button onClick={onAddFile} className="text-xs text-blue-600 hover:text-blue-800 underline">
                    Upload first file
                  </button>
                </div>
              )}
            </div>
          )}
        </div>
      </div>

      {/* Website Activity Section */}
      <PersonEventsSection
        personId={contact?.id}
        events={events}
        totalEvents={totalEvents}
        currentPage={currentEventPage}
        hasMore={hasMoreEvents}
        isLoading={isLoadingEvents}
        isLoadingMore={isLoadingMoreEvents}
        isExpanded={collapsibleStates.websiteActivity}
        onToggleExpanded={() => toggleCollapsible("websiteActivity")}
        onLoadMore={onLoadMoreEvents}
        onFilterChange={onEventFilterChange}
      />

    </div>
  )
}
