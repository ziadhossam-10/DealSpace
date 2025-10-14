"use client"

import type React from "react"
import { useState, useEffect } from "react"
import { X, Calendar, User, CheckSquare, AlertCircle, Bell, FileText, ChevronDown } from "lucide-react"
import { getInitials } from "../../../utils/helpers"
import { Modal } from "../../../components/modal"
import { TASK_TYPES, TASK_PRIORITIES, TASK_STATUSES, Task } from "../tasksApi"

interface TaskData {
  id?: number
  person_id: number
  assigned_user_id: number
  name: string
  type: string
  is_completed: boolean
  due_date: string
  due_date_time: string
  remind_seconds_before: number
  notes?: string
  priority?: string
  status?: string
}

interface TaskModalProps {
  isOpen: boolean
  onClose: () => void
  onSubmit: (data: Omit<TaskData, "id">) => Promise<void>
  task?: Task | null
  personId?: number
  users: any[]
  isLoading?: boolean
}

export const TaskModal: React.FC<TaskModalProps> = ({
  isOpen,
  onClose,
  onSubmit,
  task,
  personId,
  users,
  isLoading = false,
}) => {
  const [formData, setFormData] = useState<Omit<TaskData, "id">>({
    person_id: personId || 0,
    assigned_user_id: 0,
    name: "",
    type: "Follow Up",
    is_completed: false,
    due_date: "",
    due_date_time: "",
    remind_seconds_before: 3600, // 1 hour default
    notes: "",
    priority: "Medium",
    status: "Pending",
  })

  const [dueDate, setDueDate] = useState("")
  const [dueTime, setDueTime] = useState("")
  const [showTypeDropdown, setShowTypeDropdown] = useState(false)
  const [showPriorityDropdown, setShowPriorityDropdown] = useState(false)
  const [showStatusDropdown, setShowStatusDropdown] = useState(false)
  const [showUserDropdown, setShowUserDropdown] = useState(false)
  const [showReminderDropdown, setShowReminderDropdown] = useState(false)

  // Reminder options in seconds
  const reminderOptions = [
    { label: "No reminder", value: 0 },
    { label: "15 minutes before", value: 900 },
    { label: "30 minutes before", value: 1800 },
    { label: "1 hour before", value: 3600 },
    { label: "2 hours before", value: 7200 },
    { label: "1 day before", value: 86400 },
  ]

  // Initialize form data
  useEffect(() => {
    if (task) {
      const taskDateTime = new Date(task.due_date_time)

      setFormData({
        person_id: task.person_id,
        assigned_user_id: task.assigned_user_id,
        name: task.name,
        type: task.type,
        is_completed: task.is_completed,
        due_date: task.due_date,
        due_date_time: task.due_date_time,
        remind_seconds_before: task.remind_seconds_before,
        notes: task.notes || "",
        priority: task.priority || "Medium",
        status: task.status || "Pending",
      })

      setDueDate(task.due_date)
      setDueTime(taskDateTime.toTimeString().slice(0, 5))
    } else {
      // Reset form for new task
      const now = new Date()
      const oneHourLater = new Date(now.getTime() + 60 * 60 * 1000)

      const todayDate = now.toISOString().split("T")[0]
      const defaultTime = oneHourLater.toTimeString().slice(0, 5)

      setDueDate(todayDate)
      setDueTime(defaultTime)

      setFormData({
        person_id: personId || 0,
        assigned_user_id: 0,
        name: "",
        type: "Follow Up",
        is_completed: false,
        due_date: todayDate,
        due_date_time: `${todayDate} ${defaultTime}:00`,
        remind_seconds_before: 3600,
        notes: "",
        priority: "Medium",
        status: "Pending",
      })
    }
  }, [task, personId])

  // Update datetime string when date/time inputs change
  useEffect(() => {
    if (dueDate && dueTime) {
      const dueDateTimeString = `${dueDate} ${dueTime}:00`
      setFormData((prev) => ({
        ...prev,
        due_date: dueDate,
        due_date_time: dueDateTimeString,
      }))
    }
  }, [dueDate, dueTime])

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()

    try {
      await onSubmit(formData)
      onClose()
    } catch (error) {
      console.error("Failed to save task:", error)
    }
  }

  const selectedUser = users.find((user) => user.id === formData.assigned_user_id)
  const selectedReminder = reminderOptions.find((option) => option.value === formData.remind_seconds_before)

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
            <h2 className="text-xl font-semibold text-gray-800">{task ? "Edit Task" : "Create Task"}</h2>
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
              {/* Task Name */}
              <div className="flex items-center space-x-3">
                <div className="text-gray-400">
                  <CheckSquare className="w-5 h-5" />
                </div>
                <input
                  type="text"
                  placeholder="Task name"
                  value={formData.name}
                  onChange={(e) => setFormData((prev) => ({ ...prev, name: e.target.value }))}
                  className="flex-1 text-lg border-0 outline-none focus:ring-0 placeholder-gray-400"
                  required
                />
              </div>

              {/* Task Type and Priority */}
              <div className="flex items-center space-x-3">
                <div className="text-gray-400">
                  <FileText className="w-5 h-5" />
                </div>
                <div className="flex space-x-4 flex-1">
                  {/* Type Dropdown */}
                  <div className="relative flex-1">
                    <button
                      type="button"
                      onClick={() => setShowTypeDropdown(!showTypeDropdown)}
                      className="w-full px-3 py-2 text-left border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 flex items-center justify-between"
                    >
                      <span className="text-gray-900">{formData.type}</span>
                      <ChevronDown className="w-4 h-4 text-gray-400" />
                    </button>
                    {showTypeDropdown && (
                      <div className="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-md shadow-lg z-10 max-h-48 overflow-y-auto">
                        <div className="py-1">
                          {TASK_TYPES.map((type) => (
                            <button
                              key={type}
                              type="button"
                              onClick={() => {
                                setFormData((prev) => ({ ...prev, type }))
                                setShowTypeDropdown(false)
                              }}
                              className="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                            >
                              {type}
                            </button>
                          ))}
                        </div>
                      </div>
                    )}
                  </div>

                  {/* Priority Dropdown */}
                  <div className="relative flex-1">
                    <button
                      type="button"
                      onClick={() => setShowPriorityDropdown(!showPriorityDropdown)}
                      className="w-full px-3 py-2 text-left border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 flex items-center justify-between"
                    >
                      <span
                        className={`flex items-center space-x-2 ${
                          formData.priority === "High"
                            ? "text-red-600"
                            : formData.priority === "Medium"
                              ? "text-yellow-600"
                              : formData.priority === "Urgent"
                                ? "text-red-800"
                                : "text-green-600"
                        }`}
                      >
                        <AlertCircle className="w-4 h-4" />
                        <span>{formData.priority}</span>
                      </span>
                      <ChevronDown className="w-4 h-4 text-gray-400" />
                    </button>
                    {showPriorityDropdown && (
                      <div className="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-md shadow-lg z-10">
                        <div className="py-1">
                          {TASK_PRIORITIES.map((priority) => (
                            <button
                              key={priority}
                              type="button"
                              onClick={() => {
                                setFormData((prev) => ({ ...prev, priority }))
                                setShowPriorityDropdown(false)
                              }}
                              className="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                            >
                              {priority}
                            </button>
                          ))}
                        </div>
                      </div>
                    )}
                  </div>
                </div>
              </div>

              {/* Assigned User */}
              <div className="flex items-center space-x-3">
                <div className="text-gray-400">
                  <User className="w-5 h-5" />
                </div>
                <div className="relative flex-1">
                  <button
                    type="button"
                    onClick={() => setShowUserDropdown(!showUserDropdown)}
                    className="w-full px-3 py-2 text-left border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 flex items-center justify-between"
                  >
                    <div className="flex items-center space-x-2">
                      {selectedUser ? (
                        <>
                          <div className="w-6 h-6 bg-gray-200 text-gray-600 text-xs rounded-full flex items-center justify-center">
                            {selectedUser.avatar ? (
                              <img
                                src={selectedUser.avatar || "/placeholder.svg"}
                                alt={selectedUser.name}
                                className="w-full h-full rounded-full object-cover"
                              />
                            ) : (
                              <span>{getInitials(selectedUser.name)}</span>
                            )}
                          </div>
                          <span className="text-gray-900">{selectedUser.name}</span>
                        </>
                      ) : (
                        <span className="text-gray-500">Select assignee</span>
                      )}
                    </div>
                    <ChevronDown className="w-4 h-4 text-gray-400" />
                  </button>
                  {showUserDropdown && (
                    <div className="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-md shadow-lg z-10 max-h-48 overflow-y-auto">
                      <div className="py-1">
                        {users.map((user) => (
                          <button
                            key={user.id}
                            type="button"
                            onClick={() => {
                              setFormData((prev) => ({ ...prev, assigned_user_id: user.id }))
                              setShowUserDropdown(false)
                            }}
                            className="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center space-x-2"
                          >
                            <div className="w-6 h-6 bg-gray-200 text-gray-600 text-xs rounded-full flex items-center justify-center">
                              {user.avatar ? (
                                <img
                                  src={user.avatar || "/placeholder.svg"}
                                  alt={user.name}
                                  className="w-full h-full rounded-full object-cover"
                                />
                              ) : (
                                <span>{getInitials(user.name)}</span>
                              )}
                            </div>
                            <div>
                              <div className="font-medium">{user.name}</div>
                              <div className="text-xs text-gray-500">{user.email}</div>
                            </div>
                          </button>
                        ))}
                        {users.length === 0 && (
                          <div className="text-center py-4 text-gray-500 text-sm">No users found</div>
                        )}
                      </div>
                    </div>
                  )}
                </div>
              </div>

              {/* Due Date and Time */}
              <div className="flex items-center space-x-3">
                <div className="text-gray-400">
                  <Calendar className="w-5 h-5" />
                </div>
                <div className="flex items-center space-x-2 flex-1">
                  <input
                    type="date"
                    value={dueDate}
                    onChange={(e) => setDueDate(e.target.value)}
                    className="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 w-40"
                    required
                  />
                  <input
                    type="time"
                    value={dueTime}
                    onChange={(e) => setDueTime(e.target.value)}
                    className="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 w-32"
                    required
                  />
                </div>
              </div>

              {/* Reminder */}
              <div className="flex items-center space-x-3">
                <div className="text-gray-400">
                  <Bell className="w-5 h-5" />
                </div>
                <div className="relative flex-1">
                  <button
                    type="button"
                    onClick={() => setShowReminderDropdown(!showReminderDropdown)}
                    className="w-full px-3 py-2 text-left border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 flex items-center justify-between"
                  >
                    <span className="text-gray-900">{selectedReminder?.label || "Custom reminder"}</span>
                    <ChevronDown className="w-4 h-4 text-gray-400" />
                  </button>
                  {showReminderDropdown && (
                    <div className="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-md shadow-lg z-10">
                      <div className="py-1">
                        {reminderOptions.map((option) => (
                          <button
                            key={option.value}
                            type="button"
                            onClick={() => {
                              setFormData((prev) => ({ ...prev, remind_seconds_before: option.value }))
                              setShowReminderDropdown(false)
                            }}
                            className="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                          >
                            {option.label}
                          </button>
                        ))}
                      </div>
                    </div>
                  )}
                </div>
              </div>

              {/* Status (for editing existing tasks) */}
              {task && (
                <div className="flex items-center space-x-3">
                  <div className="text-gray-400">
                    <CheckSquare className="w-5 h-5" />
                  </div>
                  <div className="flex items-center space-x-4 flex-1">
                    <div className="relative flex-1">
                      <button
                        type="button"
                        onClick={() => setShowStatusDropdown(!showStatusDropdown)}
                        className="w-full px-3 py-2 text-left border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 flex items-center justify-between"
                      >
                        <span className="text-gray-900">{formData.status}</span>
                        <ChevronDown className="w-4 h-4 text-gray-400" />
                      </button>
                      {showStatusDropdown && (
                        <div className="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-md shadow-lg z-10">
                          <div className="py-1">
                            {TASK_STATUSES.map((status) => (
                              <button
                                key={status}
                                type="button"
                                onClick={() => {
                                  setFormData((prev) => ({
                                    ...prev,
                                    status,
                                    is_completed: status === "Completed",
                                  }))
                                  setShowStatusDropdown(false)
                                }}
                                className="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                              >
                                {status}
                              </button>
                            ))}
                          </div>
                        </div>
                      )}
                    </div>
                    <div className="flex items-center space-x-2">
                      <input
                        type="checkbox"
                        id="completed"
                        checked={formData.is_completed}
                        onChange={(e) =>
                          setFormData((prev) => ({
                            ...prev,
                            is_completed: e.target.checked,
                            status: e.target.checked ? "Completed" : "Pending",
                          }))
                        }
                        className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                      />
                      <label htmlFor="completed" className="text-sm text-gray-700">
                        Mark as completed
                      </label>
                    </div>
                  </div>
                </div>
              )}

              {/* Notes */}
              <div className="flex items-start space-x-3">
                <div className="text-gray-400 mt-2">
                  <FileText className="w-5 h-5" />
                </div>
                <div className="flex-1">
                  <textarea
                    placeholder="Add notes..."
                    value={formData.notes}
                    onChange={(e) => setFormData((prev) => ({ ...prev, notes: e.target.value }))}
                    className="w-full min-h-[120px] px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none placeholder-gray-400"
                  />
                </div>
              </div>

              {/* Submit Button */}
              <button
                type="submit"
                className="w-full bg-blue-500 hover:bg-blue-600 text-white py-3 rounded-lg font-medium disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                disabled={isLoading}
              >
                {isLoading ? "Saving..." : task ? "Update Task" : "Create Task"}
              </button>
            </form>
          </div>
        </div>
      </Modal>
    </>
  )
}
