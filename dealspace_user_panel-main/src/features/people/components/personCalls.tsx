"use client"

import type React from "react"
import { useState, useEffect } from "react"
import { Phone, Edit, Trash2, X, Clock, User, PhoneCall, PhoneIncoming, PhoneOutgoing, Play } from "lucide-react"
import {
  useGetCallsQuery,
  useCreateCallMutation,
  useUpdateCallMutation,
  useDeleteCallMutation,
  getOutcomeLabel,
  getOutcomeOptions,
  OutcomeOptions,
  type Call,
  type CreateCallRequest,
  type UpdateCallRequest,
} from "../callsApi"

interface PersonCallsProps {
  personId: number
  onToast: (message: string, type?: "success" | "error") => void
}

interface CallDialogProps {
  isOpen: boolean
  onClose: () => void
  onSubmit: (data: Omit<CreateCallRequest, "person_id"> | Omit<UpdateCallRequest, "id" | "person_id">) => void
  initialData?: Call | null
  personId: number
}

const CallDialog = ({ isOpen, onClose, onSubmit, initialData, personId }: CallDialogProps) => {
  const [phone, setPhone] = useState(initialData?.phone || "")
  const [isIncoming, setIsIncoming] = useState(initialData?.is_incoming ?? true)
  const [note, setNote] = useState(initialData?.note || "")
  const [outcome, setOutcome] = useState(initialData?.outcome ?? OutcomeOptions.INTERESTED)
  const [duration, setDuration] = useState(initialData?.duration?.toString() || "0")
  const [toNumber, setToNumber] = useState(initialData?.to_number || "")
  const [fromNumber, setFromNumber] = useState(initialData?.from_number || "")
  const [recordingUrl, setRecordingUrl] = useState(initialData?.recording_url || "")

  useEffect(() => {
    if (isOpen) {
      setPhone(initialData?.phone || "")
      setIsIncoming(initialData?.is_incoming ?? true)
      setNote(initialData?.note || "")
      setOutcome(initialData?.outcome ?? OutcomeOptions.INTERESTED)
      setDuration(initialData?.duration?.toString() || "0")
      setToNumber(initialData?.to_number || "")
      setFromNumber(initialData?.from_number || "")
      setRecordingUrl(initialData?.recording_url || "")
    }
  }, [isOpen, initialData])

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    onSubmit({
      phone,
      is_incoming: isIncoming,
      note,
      outcome,
      duration: Number.parseInt(duration) || 0,
      to_number: toNumber,
      from_number: fromNumber,
      recording_url: recordingUrl || undefined,
    })
    onClose()
  }

  const formatDuration = (seconds: number) => {
    const mins = Math.floor(seconds / 60)
    const secs = seconds % 60
    return `${mins}:${secs.toString().padStart(2, "0")}`
  }

  if (!isOpen) return null

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div className="bg-white rounded-lg shadow-lg w-full max-w-2xl p-6 max-h-[90vh] overflow-y-auto">
        <div className="flex justify-between items-center mb-4">
          <h3 className="text-lg font-medium">{initialData ? "Edit Call Log" : "Log Call"}</h3>
          <button onClick={onClose} className="text-gray-400 hover:text-gray-600">
            <X size={20} />
          </button>
        </div>

        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <label htmlFor="phone" className="block text-sm font-medium text-gray-700">
                Phone Number
              </label>
              <input
                id="phone"
                type="tel"
                value={phone}
                onChange={(e) => setPhone(e.target.value)}
                placeholder="+1234567890"
                className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                required
              />
            </div>

            <div className="space-y-2">
              <label className="block text-sm font-medium text-gray-700">Call Direction</label>
              <div className="flex space-x-4">
                <label className="flex items-center">
                  <input type="radio" checked={isIncoming} onChange={() => setIsIncoming(true)} className="mr-2" />
                  <PhoneIncoming size={16} className="mr-1" />
                  Incoming
                </label>
                <label className="flex items-center">
                  <input type="radio" checked={!isIncoming} onChange={() => setIsIncoming(false)} className="mr-2" />
                  <PhoneOutgoing size={16} className="mr-1" />
                  Outgoing
                </label>
              </div>
            </div>
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <label htmlFor="fromNumber" className="block text-sm font-medium text-gray-700">
                From Number
              </label>
              <input
                id="fromNumber"
                type="tel"
                value={fromNumber}
                onChange={(e) => setFromNumber(e.target.value)}
                placeholder="+1234567890"
                className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                required
              />
            </div>

            <div className="space-y-2">
              <label htmlFor="toNumber" className="block text-sm font-medium text-gray-700">
                To Number
              </label>
              <input
                id="toNumber"
                type="tel"
                value={toNumber}
                onChange={(e) => setToNumber(e.target.value)}
                placeholder="+1234567890"
                className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                required
              />
            </div>
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <label htmlFor="duration" className="block text-sm font-medium text-gray-700">
                Duration (seconds)
              </label>
              <input
                id="duration"
                type="number"
                value={duration}
                onChange={(e) => setDuration(e.target.value)}
                placeholder="120"
                min="0"
                className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                required
              />
              {Number.parseInt(duration) > 0 && (
                <p className="text-xs text-gray-500">Duration: {formatDuration(Number.parseInt(duration))}</p>
              )}
            </div>

            <div className="space-y-2">
              <label htmlFor="outcome" className="block text-sm font-medium text-gray-700">
                Call Outcome
              </label>
              <select
                id="outcome"
                value={outcome}
                onChange={(e) => setOutcome(Number.parseInt(e.target.value))}
                className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                required
              >
                {getOutcomeOptions().map((option) => (
                  <option key={option.value} value={option.value}>
                    {option.label}
                  </option>
                ))}
              </select>
            </div>
          </div>

          <div className="space-y-2">
            <label htmlFor="note" className="block text-sm font-medium text-gray-700">
              Call Notes
            </label>
            <textarea
              id="note"
              value={note}
              onChange={(e) => setNote(e.target.value)}
              placeholder="Add notes about this call..."
              rows={3}
              className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>

          <div className="space-y-2">
            <label htmlFor="recordingUrl" className="block text-sm font-medium text-gray-700">
              Recording URL (optional)
            </label>
            <input
              id="recordingUrl"
              type="url"
              value={recordingUrl}
              onChange={(e) => setRecordingUrl(e.target.value)}
              placeholder="https://example.com/recording.mp3"
              className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>

          <div className="flex justify-end space-x-2 pt-4">
            <button
              type="button"
              onClick={onClose}
              className="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              Cancel
            </button>
            <button
              type="submit"
              className="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              {initialData ? "Update Call" : "Log Call"}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}

export const PersonCalls = ({ personId, onToast }: PersonCallsProps) => {
  const [callDialog, setCallDialog] = useState<{ isOpen: boolean; data: Call | null }>({
    isOpen: false,
    data: null,
  })

  const { data: callsData, isLoading, refetch } = useGetCallsQuery({ person_id: personId })
  const [createCall] = useCreateCallMutation()
  const [updateCall] = useUpdateCallMutation()
  const [deleteCall] = useDeleteCallMutation()

  const handleCreateCall = async (data: Omit<CreateCallRequest, "person_id">) => {
    try {
      await createCall({ ...data, person_id: personId }).unwrap()
      onToast("Call logged successfully")
      refetch()
    } catch (error) {
      onToast("Failed to log call", "error")
      console.error(error)
    }
  }

  const handleUpdateCall = async (data: Omit<UpdateCallRequest, "id" | "person_id">) => {
    if (!callDialog.data) return

    try {
      await updateCall({
        id: callDialog.data.id,
        person_id: personId,
        ...data,
      }).unwrap()
      onToast("Call updated successfully")
      refetch()
    } catch (error) {
      onToast("Failed to update call", "error")
      console.error(error)
    }
  }

  const handleDeleteCall = async (callId: number) => {
    if (window.confirm("Are you sure you want to delete this call log?")) {
      try {
        await deleteCall(callId).unwrap()
        onToast("Call deleted successfully")
        refetch()
      } catch (error) {
        onToast("Failed to delete call", "error")
        console.error(error)
      }
    }
  }

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString("en-US", {
      year: "numeric",
      month: "short",
      day: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    })
  }

  const formatDuration = (seconds: number) => {
    const mins = Math.floor(seconds / 60)
    const secs = seconds % 60
    return `${mins}:${secs.toString().padStart(2, "0")}`
  }

  const getOutcomeColor = (outcome: number) => {
    switch (outcome) {
      case OutcomeOptions.INTERESTED:
        return "text-green-600 bg-green-100"
      case OutcomeOptions.NOT_INTERESTED:
        return "text-red-600 bg-red-100"
      case OutcomeOptions.LEFT_MESSAGE:
        return "text-blue-600 bg-blue-100"
      case OutcomeOptions.NO_ANSWER:
        return "text-gray-600 bg-gray-100"
      case OutcomeOptions.BUSY:
        return "text-yellow-600 bg-yellow-100"
      case OutcomeOptions.BAD_NUMBER:
        return "text-red-600 bg-red-100"
      default:
        return "text-gray-600 bg-gray-100"
    }
  }

  return (
    <div className="space-y-4">
      {/* Create Call Button */}
      <div className="flex justify-between items-center">
        <h3 className="text-lg font-medium flex items-center">
          <PhoneCall size={20} className="mr-2" />
          Call Logs
        </h3>
        <button
          onClick={() => setCallDialog({ isOpen: true, data: null })}
          className="flex items-center px-3 py-1.5 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700"
        >
          <Phone size={16} className="mr-1" />
          Log Call
        </button>
      </div>

      {/* Calls List */}
      <div className="space-y-3">
        {isLoading ? (
          <div className="text-center py-4 text-gray-500">Loading calls...</div>
        ) : callsData?.data?.items?.length ? (
          callsData.data.items.map((call) => (
            <div key={call.id} className="border border-gray-200 rounded-lg p-4 bg-[#cccccc0a] shadow-sm">
              <div className="flex justify-between items-start mb-2">
                <div className="flex items-center space-x-2">
                  {call.is_incoming ? (
                    <PhoneIncoming size={16} className="text-green-600" />
                  ) : (
                    <PhoneOutgoing size={16} className="text-blue-600" />
                  )}
                  <h4 className="font-medium text-gray-900">{call.phone}</h4>
                  <span className={`px-2 py-1 rounded-full text-xs font-medium ${getOutcomeColor(call.outcome)}`}>
                    {getOutcomeLabel(call.outcome)}
                  </span>
                </div>
                <div className="flex items-center space-x-1">
                  <button
                    onClick={() => setCallDialog({ isOpen: true, data: call })}
                    className="p-1 text-gray-400 hover:text-blue-500 rounded"
                  >
                    <Edit size={14} />
                  </button>
                  <button
                    onClick={() => handleDeleteCall(call.id)}
                    className="p-1 text-gray-400 hover:text-red-500 rounded"
                  >
                    <Trash2 size={14} />
                  </button>
                </div>
              </div>

              {call.note && (
                <div className="text-gray-700 mb-3">
                  <p>{call.note}</p>
                </div>
              )}

              <div className="flex items-center justify-between text-sm text-gray-500">
                <div className="flex items-center space-x-4">
                  <div className="flex items-center space-x-1">
                    <User size={14} />
                    <span>By {call.user ? call.user.name : 'Unknown'}</span>
                  </div>
                  <div className="flex items-center space-x-1">
                    <Clock size={14} />
                    <span>{formatDate(call.created_at)}</span>
                  </div>
                  {call.duration > 0 && (
                    <div className="flex items-center space-x-1">
                      <Phone size={14} />
                      <span>{formatDuration(call.duration)}</span>
                    </div>
                  )}
                </div>

                {call.recording_url && (
                  <button
                    onClick={() => window.open(call.recording_url!, "_blank")}
                    className="flex items-center space-x-1 text-blue-600 hover:text-blue-800"
                  >
                    <Play size={14} />
                    <span>Play Recording</span>
                  </button>
                )}
              </div>

              {(call.from_number || call.to_number) && (
                <div className="mt-2 text-xs text-gray-500">
                  {call.from_number && <span>From: {call.from_number}</span>}
                  {call.from_number && call.to_number && <span className="mx-2">•</span>}
                  {call.to_number && <span>To: {call.to_number}</span>}
                </div>
              )}
            </div>
          ))
        ) : (
          <div className="text-center py-8 text-gray-500">
            <PhoneCall size={48} className="mx-auto mb-2 text-gray-300" />
            <p>No call logs yet</p>
            <p className="text-sm">Log your first call to get started</p>
          </div>
        )}
      </div>

      {/* Call Dialog */}
      <CallDialog
        isOpen={callDialog.isOpen}
        onClose={() => setCallDialog({ isOpen: false, data: null })}
        onSubmit={callDialog.data ? handleUpdateCall : handleCreateCall}
        initialData={callDialog.data}
        personId={personId}
      />
    </div>
  )
}
