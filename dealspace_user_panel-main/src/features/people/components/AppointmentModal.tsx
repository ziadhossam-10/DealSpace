"use client"

import type React from "react"
import { useState, useEffect } from "react"
import {
  X,
  Calendar,
  Clock,
  MapPin,
  Users,
  Search,
  Bold,
  Italic,
  Underline,
  List,
  Link,
  Type,
  Plus,
  ChevronDown,
} from "lucide-react"
import { getInitials } from "../../../utils/helpers"
import { Modal } from "../../../components/modal"
import { AppointmentType } from "../../appointmentTypes/appointmentTypesApi"
import { AppointmentOutcome } from "../../appointmentOutcomes/appointmentOutcomesApi"

interface AppointmentData {
  id?: number
  title: string
  description: string
  start: string
  end: string
  all_day: boolean
  location: string
  created_by_id: number
  type_id: number | null
  outcome_id: number | null
  user_ids: number[]
  person_ids: number[]
  check_conflicts: boolean
  // For editing - to track deletions
  user_ids_to_delete?: number[]
  person_ids_to_delete?: number[]
  // For display purposes
  user_invitees?: Array<{
    id: number
    name: string
    email: string
    response_status: string
    responded_at: string | null
  }>
  person_invitees?: Array<{
    id: number
    name: string
    email: string | null
    response_status: string
    responded_at: string | null
  }>
}

interface AppointmentModalProps {
  isOpen: boolean
  onClose: () => void
  onSubmit: (data: Omit<AppointmentData, "id" | "created_by_id">) => Promise<void>
  appointment?: AppointmentData | null
  personId?: number
  appointmentTypes: AppointmentType[]
  appointmentOutcomes: AppointmentOutcome[]
  users: any[]
  people: any[]
  isLoading?: boolean
}

export const AppointmentModal: React.FC<AppointmentModalProps> = ({
  isOpen,
  onClose,
  onSubmit,
  appointment,
  personId,
  appointmentTypes,
  appointmentOutcomes,
  users,
  people,
  isLoading = false,
}) => {
  const [formData, setFormData] = useState<Omit<AppointmentData, "id" | "created_by_id">>({
    title: "",
    description: "",
    start: "",
    end: "",
    all_day: false,
    location: "",
    type_id: null,
    outcome_id: null,
    user_ids: [],
    person_ids: [],
    check_conflicts: true,
    user_ids_to_delete: [],
    person_ids_to_delete: [],
  })

  const [startDate, setStartDate] = useState("")
  const [startTime, setStartTime] = useState("")
  const [endDate, setEndDate] = useState("")
  const [endTime, setEndTime] = useState("")
  const [sendInvitation, setSendInvitation] = useState(true)

  const [showUserSearch, setShowUserSearch] = useState(false)
  const [showPeopleSearch, setShowPeopleSearch] = useState(false)
  const [userSearchTerm, setUserSearchTerm] = useState("")
  const [peopleSearchTerm, setPeopleSearchTerm] = useState("")
  const [showTypeDropdown, setShowTypeDropdown] = useState(false)
  const [showOutcomeDropdown, setShowOutcomeDropdown] = useState(false)
  const [showTimezoneDropdown, setShowTimezoneDropdown] = useState(false)

  // Track original invitees for edit mode
  const [originalUserIds, setOriginalUserIds] = useState<number[]>([])
  const [originalPersonIds, setOriginalPersonIds] = useState<number[]>([])

  const handleUserSelect = (user: any, checked: boolean) => {
    if (checked) {
      if (!formData.user_ids.includes(user.id)) {
        setFormData((prev) => ({
          ...prev,
          user_ids: [...prev.user_ids, user.id],
        }))
      }
    } else {
      setFormData((prev) => ({
        ...prev,
        user_ids: prev.user_ids.filter((id) => id !== user.id),
      }))
    }
  }

  const handlePersonSelect = (person: any, checked: boolean) => {
    if (checked) {
      if (!formData.person_ids.includes(person.id)) {
        setFormData((prev) => ({
          ...prev,
          person_ids: [...prev.person_ids, person.id],
        }))
      }
    } else {
      setFormData((prev) => ({
        ...prev,
        person_ids: prev.person_ids.filter((id) => id !== person.id),
      }))
    }
  }

  const filteredUsers = users.filter(
    (user) =>
      user.name.toLowerCase().includes(userSearchTerm.toLowerCase()) ||
      user.email.toLowerCase().includes(userSearchTerm.toLowerCase()),
  )

  const filteredPeople = people.filter(
    (person) =>
      person.name.toLowerCase().includes(peopleSearchTerm.toLowerCase()) ||
      person.emails?.[0]?.value?.toLowerCase().includes(peopleSearchTerm.toLowerCase()),
  )

  // Get selected users and people for display
  const selectedUsers = users.filter((user) => formData.user_ids.includes(user.id))
  const selectedPeople = people.filter((person) => formData.person_ids.includes(person.id))

  // Initialize form data
  useEffect(() => {
    if (appointment) {
      const startDateTime = new Date(appointment.start)
      const endDateTime = new Date(appointment.end)

      // Extract user and person IDs from the appointment data
      const userIds = appointment.user_invitees?.map((user) => user.id) || []
      const personIds = appointment.person_invitees?.map((person) => person.id) || []

      setOriginalUserIds(userIds)
      setOriginalPersonIds(personIds)

      setFormData({
        title: appointment.title,
        description: appointment.description,
        start: appointment.start,
        end: appointment.end,
        all_day: appointment.all_day,
        location: appointment.location,
        type_id: appointment.type_id,
        outcome_id: appointment.outcome_id,
        user_ids: userIds,
        person_ids: personIds,
        check_conflicts: appointment.check_conflicts,
        user_ids_to_delete: [],
        person_ids_to_delete: [],
      })

      setStartDate(startDateTime.toISOString().split("T")[0])
      setStartTime(startDateTime.toTimeString().slice(0, 5))
      setEndDate(endDateTime.toISOString().split("T")[0])
      setEndTime(endDateTime.toTimeString().slice(0, 5))
    } else {
      // Reset form for new appointment
      const now = new Date()
      const oneHourLater = new Date(now.getTime() + 60 * 60 * 1000)

      setStartDate(now.toISOString().split("T")[0])
      setStartTime(now.toTimeString().slice(0, 5))
      setEndDate(oneHourLater.toISOString().split("T")[0])
      setEndTime(oneHourLater.toTimeString().slice(0, 5))

      setOriginalUserIds([])
      setOriginalPersonIds([])

      setFormData({
        title: "",
        description: "",
        start: "",
        end: "",
        all_day: false,
        location: "",
        type_id: null,
        outcome_id: null,
        user_ids: [],
        person_ids: personId ? [personId] : [],
        check_conflicts: true,
        user_ids_to_delete: [],
        person_ids_to_delete: [],
      })
    }
  }, [appointment, personId])

  // Update datetime strings when date/time inputs change
  useEffect(() => {
    if (startDate && startTime && !formData.all_day) {
      const startDateTime = `${startDate}T${startTime}:00`
      setFormData((prev) => ({ ...prev, start: startDateTime }))
    }
    if (endDate && endTime && !formData.all_day) {
      const endDateTime = `${endDate}T${endTime}:00`
      setFormData((prev) => ({ ...prev, end: endDateTime }))
    }
  }, [startDate, startTime, endDate, endTime, formData.all_day])

  const handleAllDayChange = (checked: boolean) => {
    setFormData((prev) => ({ ...prev, all_day: checked }))
    if (checked) {
      const startDateTime = `${startDate}T00:00:00`
      const endDateTime = `${endDate}T23:59:59`
      setFormData((prev) => ({ ...prev, start: startDateTime, end: endDateTime }))
    }
  }

  const handleRemoveUser = (userId: number) => {
    setFormData((prev) => ({
      ...prev,
      user_ids: prev.user_ids.filter((id) => id !== userId),
    }))
  }

  const handleRemovePerson = (personId: number) => {
    setFormData((prev) => ({
      ...prev,
      person_ids: prev.person_ids.filter((id) => id !== personId),
    }))
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()

    // Calculate what needs to be deleted for updates
    let submitData = { ...formData }

    if (appointment) {
      // For updates, calculate what to delete
      const userIdsToDelete = originalUserIds.filter((id) => !formData.user_ids.includes(id))
      const personIdsToDelete = originalPersonIds.filter((id) => !formData.person_ids.includes(id))

      submitData = {
        ...formData,
        user_ids_to_delete: userIdsToDelete,
        person_ids_to_delete: personIdsToDelete,
      }
    }

    try {
      await onSubmit(submitData)
      onClose()
    } catch (error) {
      console.error("Failed to save appointment:", error)
    }
  }

  const selectedType = appointmentTypes.find((type) => type.id === formData.type_id)
  const selectedOutcome = appointmentOutcomes.find((outcome) => outcome.id === formData.outcome_id)

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
            <h2 className="text-xl font-semibold text-gray-800">
              {appointment ? "Edit Appointment" : "Create Appointment"}
            </h2>
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
                  {!formData.all_day && (
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
                <div className="flex items-center space-x-2">
                  <input
                    type="checkbox"
                    id="all-day"
                    checked={formData.all_day}
                    onChange={(e) => handleAllDayChange(e.target.checked)}
                    className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                  />
                  <label htmlFor="all-day" className="text-sm text-gray-700">
                    All day event
                  </label>
                </div>
                <div className="flex-1 text-right">
                  <div className="relative">
                    <button
                      type="button"
                      onClick={() => setShowTimezoneDropdown(!showTimezoneDropdown)}
                      className="px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 focus:outline-none flex items-center space-x-1"
                    >
                      <span>Eastern Time (GMT-04:00)</span>
                      <ChevronDown className="w-4 h-4" />
                    </button>
                    {showTimezoneDropdown && (
                      <div className="absolute right-0 mt-1 w-48 bg-white border border-gray-200 rounded-md shadow-lg z-10">
                        <div className="py-1">
                          <button
                            type="button"
                            className="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                          >
                            Eastern Time (GMT-04:00)
                          </button>
                          <button
                            type="button"
                            className="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                          >
                            Central Time (GMT-05:00)
                          </button>
                          <button
                            type="button"
                            className="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                          >
                            Mountain Time (GMT-06:00)
                          </button>
                          <button
                            type="button"
                            className="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                          >
                            Pacific Time (GMT-07:00)
                          </button>
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

              {/* Guests/Invitees Section */}
              <div className="flex items-start space-x-3">
                <div className="text-gray-400 mt-2">
                  <Users className="w-5 h-5" />
                </div>
                <div className="flex-1 space-y-4">
                  {/* Users Selection */}
                  <div className="user-search-container">
                    <label className="block text-sm font-medium text-gray-700 mb-2">Users</label>
                    {!showUserSearch ? (
                      <button
                        type="button"
                        onClick={() => setShowUserSearch(true)}
                        className="w-full px-4 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 flex items-center justify-center"
                      >
                        <Plus className="mr-2 h-4 w-4" />
                        Add Users
                      </button>
                    ) : (
                      <div className="space-y-2">
                        <div className="relative">
                          <input
                            type="text"
                            placeholder="Search users..."
                            value={userSearchTerm}
                            onChange={(e) => setUserSearchTerm(e.target.value)}
                            className="w-full px-3 py-2 pr-8 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                          />
                          <Search className="w-4 h-4 text-gray-400 absolute right-2 top-3" />
                        </div>
                        <div className="max-h-40 overflow-y-auto border border-gray-200 rounded-md p-2 bg-white">
                          {filteredUsers.map((user) => (
                            <div key={user.id} className="flex items-center space-x-2 py-2 hover:bg-gray-50 rounded">
                              <input
                                type="checkbox"
                                checked={formData.user_ids.includes(user.id)}
                                onChange={(e) => handleUserSelect(user, e.target.checked)}
                                className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                              />
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
                              <div className="flex-1">
                                <div className="text-sm font-medium text-gray-900">{user.name}</div>
                                <div className="text-xs text-gray-500">{user.email}</div>
                              </div>
                            </div>
                          ))}
                          {filteredUsers.length === 0 && (
                            <div className="text-center py-4 text-gray-500 text-sm">No users found</div>
                          )}
                        </div>
                        <button
                          type="button"
                          onClick={() => setShowUserSearch(false)}
                          className="text-sm text-blue-600 hover:text-blue-800"
                        >
                          Done
                        </button>
                      </div>
                    )}
                  </div>

                  {/* People Selection */}
                  <div className="people-search-container">
                    <label className="block text-sm font-medium text-gray-700 mb-2">People</label>
                    {!showPeopleSearch ? (
                      <button
                        type="button"
                        onClick={() => setShowPeopleSearch(true)}
                        className="w-full px-4 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 flex items-center justify-center"
                      >
                        <Plus className="mr-2 h-4 w-4" />
                        Add People
                      </button>
                    ) : (
                      <div className="space-y-2">
                        <div className="relative">
                          <input
                            type="text"
                            placeholder="Search people..."
                            value={peopleSearchTerm}
                            onChange={(e) => setPeopleSearchTerm(e.target.value)}
                            className="w-full px-3 py-2 pr-8 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                          />
                          <Search className="w-4 h-4 text-gray-400 absolute right-2 top-3" />
                        </div>
                        <div className="max-h-40 overflow-y-auto border border-gray-200 rounded-md p-2 bg-white">
                          {filteredPeople.map((person) => (
                            <div key={person.id} className="flex items-center space-x-2 py-2 hover:bg-gray-50 rounded">
                              <input
                                type="checkbox"
                                checked={formData.person_ids.includes(person.id)}
                                onChange={(e) => handlePersonSelect(person, e.target.checked)}
                                className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                              />
                              <div className="w-6 h-6 bg-blue-100 text-blue-600 text-xs rounded-full flex items-center justify-center">
                                <span>{getInitials(person.name)}</span>
                              </div>
                              <div className="flex-1">
                                <div className="text-sm font-medium text-gray-900">{person.name}</div>
                                <div className="text-xs text-gray-500">{person.emails?.[0]?.value || "No email"}</div>
                              </div>
                            </div>
                          ))}
                          {filteredPeople.length === 0 && (
                            <div className="text-center py-4 text-gray-500 text-sm">No people found</div>
                          )}
                        </div>
                        <button
                          type="button"
                          onClick={() => setShowPeopleSearch(false)}
                          className="text-sm text-blue-600 hover:text-blue-800"
                        >
                          Done
                        </button>
                      </div>
                    )}
                  </div>

                  {/* Selected Invitees */}
                  {(selectedUsers.length > 0 || selectedPeople.length > 0) && (
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        Selected Invitees ({selectedUsers.length + selectedPeople.length})
                      </label>
                      <div className="space-y-2 max-h-32 overflow-y-auto">
                        {/* Selected Users */}
                        {selectedUsers.map((user) => (
                          <div
                            key={`user-${user.id}`}
                            className="flex items-center justify-between p-2 rounded-md bg-gray-50"
                          >
                            <div className="flex items-center space-x-2">
                              <div className="w-6 h-6 bg-gray-200 text-gray-600 text-xs rounded-full flex items-center justify-center">
                                <span>{getInitials(user.name)}</span>
                              </div>
                              <div>
                                <div className="text-sm font-medium text-gray-900">{user.name}</div>
                                <div className="text-xs text-gray-500">{user.email} • User</div>
                              </div>
                            </div>
                            <button
                              type="button"
                              onClick={() => handleRemoveUser(user.id)}
                              className="p-1 rounded-md hover:bg-gray-200 transition-colors"
                            >
                              <X className="h-4 w-4 text-gray-500" />
                            </button>
                          </div>
                        ))}
                        {/* Selected People */}
                        {selectedPeople.map((person) => (
                          <div
                            key={`person-${person.id}`}
                            className="flex items-center justify-between p-2 rounded-md bg-blue-50"
                          >
                            <div className="flex items-center space-x-2">
                              <div className="w-6 h-6 bg-blue-100 text-blue-600 text-xs rounded-full flex items-center justify-center">
                                <span>{getInitials(person.name)}</span>
                              </div>
                              <div>
                                <div className="text-sm font-medium text-gray-900">{person.name}</div>
                                <div className="text-xs text-gray-500">
                                  {person.emails?.[0]?.value || "No email"} • Person
                                </div>
                              </div>
                            </div>
                            <button
                              type="button"
                              onClick={() => handleRemovePerson(person.id)}
                              className="p-1 rounded-md hover:bg-gray-200 transition-colors"
                            >
                              <X className="h-4 w-4 text-gray-500" />
                            </button>
                          </div>
                        ))}
                      </div>
                    </div>
                  )}
                </div>
              </div>

              {/* Type and Outcome */}
              <div className="flex items-center space-x-3">
                <div className="text-gray-400">
                  <Calendar className="w-5 h-5" />
                </div>
                <div className="flex space-x-4 flex-1">
                  {/* Type Dropdown */}
                  <div className="relative flex-1">
                    <button
                      type="button"
                      onClick={() => setShowTypeDropdown(!showTypeDropdown)}
                      className="w-full px-3 py-2 text-left border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 flex items-center justify-between"
                    >
                      <span className={selectedType ? "text-gray-900" : "text-gray-500"}>
                        {selectedType ? selectedType.name : "Set type"}
                      </span>
                      <ChevronDown className="w-4 h-4 text-gray-400" />
                    </button>
                    {showTypeDropdown && (
                      <div className="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-md shadow-lg z-10 max-h-48 overflow-y-auto">
                        <div className="py-1">
                          {appointmentTypes.map((type) => (
                            <button
                              key={type.id}
                              type="button"
                              onClick={() => {
                                setFormData((prev) => ({ ...prev, type_id: type.id }))
                                setShowTypeDropdown(false)
                              }}
                              className="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                            >
                              {type.name}
                            </button>
                          ))}
                        </div>
                      </div>
                    )}
                  </div>

                  {/* Outcome Dropdown */}
                  <div className="relative flex-1">
                    <button
                      type="button"
                      onClick={() => setShowOutcomeDropdown(!showOutcomeDropdown)}
                      className="w-full px-3 py-2 text-left border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 flex items-center justify-between"
                    >
                      <span className={selectedOutcome ? "text-gray-900" : "text-gray-500"}>
                        {selectedOutcome ? selectedOutcome.name : "Set outcome"}
                      </span>
                      <ChevronDown className="w-4 h-4 text-gray-400" />
                    </button>
                    {showOutcomeDropdown && (
                      <div className="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-md shadow-lg z-10 max-h-48 overflow-y-auto">
                        <div className="py-1">
                          {appointmentOutcomes.map((outcome) => (
                            <button
                              key={outcome.id}
                              type="button"
                              onClick={() => {
                                setFormData((prev) => ({ ...prev, outcome_id: outcome.id }))
                                setShowOutcomeDropdown(false)
                              }}
                              className="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                            >
                              {outcome.name}
                            </button>
                          ))}
                        </div>
                      </div>
                    )}
                  </div>
                </div>
              </div>

              {/* Description */}
              <div className="flex items-start space-x-3">
                <div className="text-gray-400 mt-2">
                  <Type className="w-5 h-5" />
                </div>
                <div className="flex-1">
                  {/* Toolbar */}
                  <div className="flex items-center space-x-2 mb-2 p-2 border-b border-gray-200">
                    <button type="button" className="p-1 hover:bg-gray-100 rounded">
                      <Bold className="w-4 h-4 text-gray-600" />
                    </button>
                    <button type="button" className="p-1 hover:bg-gray-100 rounded">
                      <Italic className="w-4 h-4 text-gray-600" />
                    </button>
                    <button type="button" className="p-1 hover:bg-gray-100 rounded">
                      <Underline className="w-4 h-4 text-gray-600" />
                    </button>
                    <button type="button" className="p-1 hover:bg-gray-100 rounded">
                      <List className="w-4 h-4 text-gray-600" />
                    </button>
                    <button type="button" className="p-1 hover:bg-gray-100 rounded">
                      <Link className="w-4 h-4 text-gray-600" />
                    </button>
                  </div>
                  <textarea
                    placeholder="Add description..."
                    value={formData.description}
                    onChange={(e) => setFormData((prev) => ({ ...prev, description: e.target.value }))}
                    className="w-full min-h-[120px] border-0 outline-none resize-none focus:ring-0 placeholder-gray-400"
                  />
                </div>
              </div>

              {/* Send Invitation */}
              <div className="flex items-center space-x-2">
                <input
                  type="checkbox"
                  id="send-invitation"
                  checked={sendInvitation}
                  onChange={(e) => setSendInvitation(e.target.checked)}
                  className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                />
                <label htmlFor="send-invitation" className="text-sm text-gray-700">
                  Send invitation email & text reminder
                </label>
              </div>

              {/* Submit Button */}
              <button
                type="submit"
                className="w-full bg-blue-500 hover:bg-blue-600 text-white py-3 rounded-lg font-medium disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                disabled={isLoading}
              >
                {isLoading ? "Saving..." : appointment ? "Update Appointment" : "Create Appointment"}
              </button>
            </form>
          </div>
        </div>
      </Modal>
    </>
  )
}
