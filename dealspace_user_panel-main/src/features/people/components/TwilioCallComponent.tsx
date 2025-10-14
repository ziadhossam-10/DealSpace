"use client"

import type React from "react"
import { useState, useEffect, useRef } from "react"
import { Device, type Call as TwilioCall } from "@twilio/voice-sdk"
import { Phone, PhoneCall, PhoneOff, Mic, MicOff, Volume2, VolumeX } from "lucide-react"
import { BASE_URL } from "../../../utils/helpers"
import { useAppSelector } from "../../../app/hooks"
import { Modal } from "../../../components/modal"

interface Call {
  id: number
  person_id?: number
  phone: string
  is_incoming: boolean
  note?: string
  outcome?: string
  duration: number
  to_number: string
  from_number: string
  user_id: number
  recording_url?: string
  status?: string
}

interface TwilioCallComponentProps {
  agentId: number
  onCallLogged?: (call: Call) => void
}

const TwilioCallComponent: React.FC<TwilioCallComponentProps> = ({ agentId, onCallLogged }) => {
  // State management
  const [device, setDevice] = useState<Device | null>(null)
  const [activeCall, setActiveCall] = useState<TwilioCall | null>(null)
  const [isConnected, setIsConnected] = useState(false)
  const [isMuted, setIsMuted] = useState(false)
  const [callVolume, setCallVolume] = useState(1)
  const [callStatus, setCallStatus] = useState<string>("")
  const [phoneNumber, setPhoneNumber] = useState("")
  const [showCallLog, setShowCallLog] = useState(false)
  const [callToLog, setCallToLog] = useState<Call | null>(null)
  const [callNote, setCallNote] = useState("")
  const [callOutcome, setCallOutcome] = useState("")
  const [isDialing, setIsDialing] = useState(false)
  const [incomingCall, setIncomingCall] = useState<TwilioCall | null>(null)
  const {token} = useAppSelector(state => state.auth)

  const deviceRef = useRef<Device | null>(null)

  // Initialize Twilio Device
  useEffect(() => {
    initializeTwilioDevice()
    return () => {
      if (deviceRef.current) {
        deviceRef.current.destroy()
      }
    }
  }, [agentId])

  const initializeTwilioDevice = async () => {
    try {
      // Get access token from backend
      const tokenResponse = await fetch(BASE_URL + "/calls/twilio/token", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
        },
        body: JSON.stringify({ agent_id: agentId }),
      })

      const tokenData = await tokenResponse.json()
      console.log(tokenData);
      

      if (!tokenData.data.token) {
        throw new Error("Failed to get access token")
      }

      // Create and setup Twilio Device
      const twilioDevice = new Device(tokenData.data.token, {
        logLevel: 1,
        // Remove codecPreferences or use proper Codec enum values
        // codecPreferences: [Codec.Opus, Codec.PCMU], // If you have access to Codec enum
        // enableRingingState: true,
      })

      // Device event listeners
      twilioDevice.on("registered", () => {
        console.log("Twilio Device registered")
        setIsConnected(true)
        setCallStatus("Ready")
      })

      twilioDevice.on("error", (error) => {
        console.error("Twilio Device error:", error)
        setCallStatus("Error: " + error.message)
      })

      twilioDevice.on("incoming", (call) => {
        console.log("Incoming call:", call)
        setIncomingCall(call)
        setCallStatus("Incoming call...")

        // Setup call event listeners
        setupCallEventListeners(call)
      })

      twilioDevice.on("unregistered", () => {
        setIsConnected(false)
        setCallStatus("Disconnected")
      })

      // Register device
      await twilioDevice.register()

      setDevice(twilioDevice)
      deviceRef.current = twilioDevice
    } catch (error) {
      console.error("Failed to initialize Twilio Device:", error)
      setCallStatus("Failed to connect")
    }
  }

  const setupCallEventListeners = (call: TwilioCall) => {
    call.on("accept", () => {
      console.log("Call accepted")
      setActiveCall(call)
      setIncomingCall(null)
      setCallStatus("Connected")
    })

    call.on("disconnect", () => {
      console.log("Call disconnected")
      setActiveCall(null)
      setIncomingCall(null)
      setCallStatus("Call ended")
      setIsDialing(false)

      // Show call logging interface
      showCallLoggingInterface(call)
    })

    call.on("reject", () => {
      console.log("Call rejected")
      setIncomingCall(null)
      setCallStatus("Call rejected")
    })

    call.on("cancel", () => {
      console.log("Call cancelled")
      setIncomingCall(null)
      setActiveCall(null)
      setCallStatus("Call cancelled")
      setIsDialing(false)
    })

    call.on("error", (error: any) => {
      console.error("Call error:", error)
      setCallStatus("Call error: " + error.message)
      setActiveCall(null)
      setIncomingCall(null)
      setIsDialing(false)
    })
  }

  const makeCall = async (number: string, personId?: number) => {
    if (!device || !number) return

    try {
      setIsDialing(true)
      setCallStatus("Dialing...")

      // Make call through Twilio Device
      const call = await device.connect({
        params: {
          To: number,
          PersonId: personId?.toString() || "",
          AgentId: agentId.toString(),
        },
      })

      setupCallEventListeners(call)
      setActiveCall(call)
    } catch (error) {
      console.error("Failed to make call:", error)
      setCallStatus("Failed to make call")
      setIsDialing(false)
    }
  }

  const acceptCall = () => {
    if (incomingCall) {
      incomingCall.accept()
    }
  }

  const rejectCall = () => {
    if (incomingCall) {
      incomingCall.reject()
      setIncomingCall(null)
      setCallStatus("Ready")
    }
  }

  const hangupCall = () => {
    if (activeCall) {
      activeCall.disconnect()
    }
  }

  const toggleMute = () => {
    if (activeCall) {
      if (isMuted) {
        activeCall.mute(false)
      } else {
        activeCall.mute(true)
      }
      setIsMuted(!isMuted)
    }
  }

  const adjustVolume = (volume: number) => {
    // Note: Volume control on Twilio calls is typically handled through the audio element
    // or browser's audio context, not directly on the call object
    setCallVolume(volume)

    // If you need to control the actual audio volume, you might need to:
    // 1. Get the audio element from the call
    // 2. Use Web Audio API
    // 3. Or handle it through the device's audio settings

    // For now, we'll just update the state for UI purposes
    console.log("Volume adjusted to:", volume)
  }

  const showCallLoggingInterface = (call: TwilioCall) => {
    // Create call object for logging
    const callData: Call = {
      id: Date.now(), // Use timestamp as fallback ID
      phone: call.parameters?.To || call.parameters?.From || "",
      is_incoming: !!incomingCall,
      duration: 0, // Will be updated by backend
      to_number: call.parameters?.To || "",
      from_number: call.parameters?.From || "",
      user_id: agentId,
      person_id: call.parameters?.PersonId ? Number.parseInt(call.parameters.PersonId) : undefined,
    }

    setCallToLog(callData)
    setShowCallLog(true)
  }

  const logCall = async () => {
    if (!callToLog) return

    try {
      const response = await fetch(BASE_URL + "/calls/twilio/log-call", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
        },
        body: JSON.stringify({
          call_id: callToLog.id,
          outcome: callOutcome,
          note: callNote,
        }),
      })

      if (response.ok) {
        setShowCallLog(false)
        setCallNote("")
        setCallOutcome("")
        setCallToLog(null)

        if (onCallLogged) {
          onCallLogged({ ...callToLog, outcome: callOutcome, note: callNote })
        }
      }
    } catch (error) {
      console.error("Failed to log call:", error)
    }
  }

  const formatPhoneNumber = (value: string) => {
    const cleaned = value.replace(/\D/g, "")
    const match = cleaned.match(/^(\d{3})(\d{3})(\d{4})$/)
    if (match) {
      return `(${match[1]}) ${match[2]}-${match[3]}`
    }
    return value
  }

  return (
    <Modal isOpen={true} onClose={() => {}} className="p-6 max-w-md mx-auto">
      {/* Connection Status */}
      <div className="mb-4">
        <div
          className={`flex items-center gap-2 px-3 py-2 rounded-lg ${
            isConnected ? "bg-green-100 text-green-800" : "bg-red-100 text-red-800"
          }`}
        >
          <div className={`w-2 h-2 rounded-full ${isConnected ? "bg-green-500" : "bg-red-500"}`} />
          <span className="text-sm font-medium">{callStatus}</span>
        </div>
      </div>

      {/* Incoming Call Interface */}
      {incomingCall && (
        <div className="mb-4 p-4 bg-blue-50 rounded-lg border-2 border-blue-200">
          <div className="text-center">
            <PhoneCall className="w-8 h-8 mx-auto mb-2 text-blue-600 animate-bounce" />
            <h3 className="font-semibold text-lg mb-2">Incoming Call</h3>
            <p className="text-gray-600 mb-4">From: {formatPhoneNumber(incomingCall.parameters?.From || "")}</p>
            <div className="flex gap-3 justify-center">
              <button
                onClick={acceptCall}
                className="flex items-center gap-2 bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition-colors"
              >
                <Phone className="w-4 h-4" />
                Accept
              </button>
              <button
                onClick={rejectCall}
                className="flex items-center gap-2 bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition-colors"
              >
                <PhoneOff className="w-4 h-4" />
                Decline
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Dialer Interface */}
      {!activeCall && !incomingCall && (
        <div className="mb-4">
          <h3 className="font-semibold text-lg mb-4">Make a Call</h3>
          <div className="mb-4">
            <input
              type="tel"
              value={phoneNumber}
              onChange={(e) => setPhoneNumber(e.target.value)}
              placeholder="Enter phone number"
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>
          <button
            onClick={() => makeCall(phoneNumber)}
            disabled={!phoneNumber || !isConnected || isDialing}
            className="w-full flex items-center justify-center gap-2 bg-blue-500 hover:bg-blue-600 disabled:bg-gray-300 text-white px-4 py-2 rounded-lg transition-colors"
          >
            <Phone className="w-4 h-4" />
            {isDialing ? "Dialing..." : "Call"}
          </button>
        </div>
      )}

      {/* Active Call Controls */}
      {activeCall && (
        <div className="mb-4 p-4 bg-green-50 rounded-lg">
          <div className="text-center mb-4">
            <h3 className="font-semibold text-lg">Active Call</h3>
            <p className="text-gray-600">
              {formatPhoneNumber(activeCall.parameters?.To || activeCall.parameters?.From || "")}
            </p>
          </div>

          <div className="flex gap-3 justify-center mb-4">
            <button
              onClick={toggleMute}
              className={`flex items-center gap-2 px-4 py-2 rounded-lg transition-colors ${
                isMuted ? "bg-red-100 text-red-700 hover:bg-red-200" : "bg-gray-100 text-gray-700 hover:bg-gray-200"
              }`}
            >
              {isMuted ? <MicOff className="w-4 h-4" /> : <Mic className="w-4 h-4" />}
              {isMuted ? "Unmute" : "Mute"}
            </button>

            <button
              onClick={hangupCall}
              className="flex items-center gap-2 bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition-colors"
            >
              <PhoneOff className="w-4 h-4" />
              Hang Up
            </button>
          </div>

          {/* Volume Control - Note: This controls UI state, actual volume may need different implementation */}
          <div className="flex items-center gap-2">
            <VolumeX className="w-4 h-4 text-gray-500" />
            <input
              type="range"
              min="0"
              max="1"
              step="0.1"
              value={callVolume}
              onChange={(e) => adjustVolume(Number.parseFloat(e.target.value))}
              className="flex-1"
              title="Volume control (UI only - actual volume control may require additional implementation)"
            />
            <Volume2 className="w-4 h-4 text-gray-500" />
          </div>
        </div>
      )}

      {/* Call Logging Modal */}
      {showCallLog && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div className="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <h3 className="font-semibold text-lg mb-4">Log Call</h3>

            <div className="mb-4">
              <label className="block text-sm font-medium text-gray-700 mb-2">Outcome</label>
              <select
                value={callOutcome}
                onChange={(e) => setCallOutcome(e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              >
                <option value="">Select outcome...</option>
                <option value="answered">Answered</option>
                <option value="no_answer">No Answer</option>
                <option value="busy">Busy</option>
                <option value="voicemail">Voicemail</option>
                <option value="interested">Interested</option>
                <option value="not_interested">Not Interested</option>
                <option value="callback">Callback Requested</option>
                <option value="wrong_number">Wrong Number</option>
              </select>
            </div>

            <div className="mb-4">
              <label className="block text-sm font-medium text-gray-700 mb-2">Notes</label>
              <textarea
                value={callNote}
                onChange={(e) => setCallNote(e.target.value)}
                placeholder="Add call notes..."
                rows={4}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <div className="flex gap-3">
              <button
                onClick={logCall}
                disabled={!callOutcome}
                className="flex-1 bg-blue-500 hover:bg-blue-600 disabled:bg-gray-300 text-white px-4 py-2 rounded-lg transition-colors"
              >
                Save Call Log
              </button>
              <button
                onClick={() => {
                  setShowCallLog(false)
                  setCallNote("")
                  setCallOutcome("")
                  setCallToLog(null)
                }}
                className="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg transition-colors"
              >
                Cancel
              </button>
            </div>
          </div>
        </div>
      )}
    </Modal>
  )
}

export default TwilioCallComponent
