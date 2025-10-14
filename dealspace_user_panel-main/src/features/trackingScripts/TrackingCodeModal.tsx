"use client"

import { useState, useEffect } from "react"
import { X, Copy, Check, Code, Eye, EyeOff } from "lucide-react"
import { useGetTrackingCodeQuery, useGetTrackingScriptByIdQuery } from "./trackingScriptsApi"

interface TrackingCodeModalProps {
  isOpen: boolean
  onClose: () => void
  scriptId: number | null
}

export default function TrackingCodeModal({ isOpen, onClose, scriptId }: TrackingCodeModalProps) {
  const [copied, setCopied] = useState("")
  const [activeTab, setActiveTab] = useState("code")
  const [showInstructions, setShowInstructions] = useState(true)

  // Fetch script data by ID
  const { data: scriptData, isLoading: isLoadingScript } = useGetTrackingScriptByIdQuery(scriptId || 0, {
    skip: !scriptId || !isOpen,
  })

  const { data: trackingCodeData, isLoading } = useGetTrackingCodeQuery(scriptId || 0, {
    skip: !scriptId || !isOpen,
  })

  const script = scriptData?.data

  useEffect(() => {
    if (!isOpen) {
      setCopied("")
      setActiveTab("code")
    }
  }, [isOpen])

  const handleCopy = async (text: string, type: string) => {
    try {
      await navigator.clipboard.writeText(text)
      setCopied(type)
      setTimeout(() => setCopied(""), 2000)
    } catch (err) {
      console.error("Failed to copy text:", err)
    }
  }

  if (!isOpen) return null

  if (isLoadingScript) {
    return (
      <div className="fixed inset-0 bg-black bg-opacity-50 z-50 flex justify-center items-center p-4">
        <div className="bg-white rounded-lg shadow-xl w-full max-w-6xl p-6">
          <div className="flex items-center justify-center h-32">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <span className="ml-2 text-gray-600">Loading script data...</span>
          </div>
        </div>
      </div>
    )
  }

  if (!script) {
    return (
      <div className="fixed inset-0 bg-black bg-opacity-50 z-50 flex justify-center items-center p-4">
        <div className="bg-white rounded-lg shadow-xl w-full max-w-6xl p-6">
          <div className="text-center text-red-600">Script not found</div>
        </div>
      </div>
    )
  }

  const trackingCode = trackingCodeData?.data?.tracking_code || ""
  const instructions = trackingCodeData?.data?.instructions || {}

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 z-50 flex justify-center items-center p-4">
      <div className="bg-white rounded-lg shadow-xl w-full max-w-6xl max-h-[90vh] overflow-hidden">
        <div className="flex justify-between items-center p-6 border-b border-gray-200">
          <div>
            <h2 className="text-xl font-semibold text-gray-900">Tracking Code</h2>
            <p className="text-sm text-gray-500 mt-1">{script.name}</p>
          </div>
          <div className="flex items-center gap-2">
            <button
              onClick={() => setShowInstructions(!showInstructions)}
              className="p-2 rounded-md hover:bg-gray-100 transition-colors"
              title={showInstructions ? "Hide Instructions" : "Show Instructions"}
            >
              {showInstructions ? (
                <EyeOff className="h-4 w-4 text-gray-500" />
              ) : (
                <Eye className="h-4 w-4 text-gray-500" />
              )}
            </button>
            <button onClick={onClose} className="p-2 rounded-md hover:bg-gray-100 transition-colors">
              <X className="h-4 w-4 text-gray-500" />
            </button>
          </div>
        </div>

        <div className="flex h-[calc(90vh-120px)]">
          {/* Main Content */}
          <div className={`${showInstructions ? "w-2/3" : "w-full"} flex flex-col`}>
            {/* Tabs */}
            <div className="flex border-b border-gray-200">
              <button
                onClick={() => setActiveTab("code")}
                className={`px-4 py-2 text-sm font-medium border-b-2 ${
                  activeTab === "code"
                    ? "border-blue-500 text-blue-600"
                    : "border-transparent text-gray-500 hover:text-gray-700"
                }`}
              >
                <Code className="h-4 w-4 inline mr-2" />
                Tracking Code
              </button>
              <button
                onClick={() => setActiveTab("details")}
                className={`px-4 py-2 text-sm font-medium border-b-2 ${
                  activeTab === "details"
                    ? "border-blue-500 text-blue-600"
                    : "border-transparent text-gray-500 hover:text-gray-700"
                }`}
              >
                Script Details
              </button>
            </div>

            {/* Tab Content */}
            <div className="flex-1 overflow-y-auto p-6">
              {activeTab === "code" && (
                <div className="space-y-4">
                  <div className="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                    <div className="flex">
                      <div className="flex-shrink-0">
                        <svg className="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                          <path
                            fillRule="evenodd"
                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                            clipRule="evenodd"
                          />
                        </svg>
                      </div>
                      <div className="ml-3">
                        <h3 className="text-sm font-medium text-yellow-800">Installation Instructions</h3>
                        <div className="mt-2 text-sm text-yellow-700">
                          <p>
                            Copy the tracking code below and paste it into the {"<head>"} section of your website. The
                            script will automatically start tracking based on your configuration.
                          </p>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div>
                    <div className="flex items-center justify-between mb-2">
                      <label className="block text-sm font-medium text-gray-700">Tracking Code</label>
                      <button
                        onClick={() => handleCopy(trackingCode, "code")}
                        className="flex items-center px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors"
                      >
                        {copied === "code" ? (
                          <>
                            <Check className="h-4 w-4 mr-1" />
                            Copied
                          </>
                        ) : (
                          <>
                            <Copy className="h-4 w-4 mr-1" />
                            Copy Code
                          </>
                        )}
                      </button>
                    </div>
                    <div className="relative">
                      <pre className="bg-gray-900 text-gray-100 p-4 rounded-md overflow-x-auto text-sm">
                        <code>{trackingCode}</code>
                      </pre>
                    </div>
                  </div>

                  <div>
                    <div className="flex items-center justify-between mb-2">
                      <label className="block text-sm font-medium text-gray-700">Script Key</label>
                      <button
                        onClick={() => handleCopy(script.script_key, "key")}
                        className="flex items-center px-3 py-1 bg-gray-600 text-white rounded text-sm hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-colors"
                      >
                        {copied === "key" ? (
                          <>
                            <Check className="h-4 w-4 mr-1" />
                            Copied
                          </>
                        ) : (
                          <>
                            <Copy className="h-4 w-4 mr-1" />
                            Copy Key
                          </>
                        )}
                      </button>
                    </div>
                    <div className="p-3 bg-gray-50 border border-gray-300 rounded-md font-mono text-sm break-all">
                      {script.script_key}
                    </div>
                  </div>
                </div>
              )}

              {activeTab === "details" && (
                <div className="space-y-6">
                  <div>
                    <h3 className="text-lg font-medium text-gray-900 mb-4">Script Configuration</h3>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div className="bg-gray-50 p-4 rounded-md">
                        <h4 className="font-medium text-gray-900 mb-2">Allowed Domains</h4>
                        <div className="space-y-1">
                          {script.domain.map((domain, index) => (
                            <span
                              key={index}
                              className="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded mr-1"
                            >
                              {domain}
                            </span>
                          ))}
                        </div>
                      </div>

                      <div className="bg-gray-50 p-4 rounded-md">
                        <h4 className="font-medium text-gray-900 mb-2">Tracking Features</h4>
                        <div className="space-y-2">
                          <div className="flex items-center">
                            <div
                              className={`w-2 h-2 rounded-full mr-2 ${
                                script.track_page_views ? "bg-green-500" : "bg-gray-300"
                              }`}
                            />
                            <span className="text-sm">Page Views</span>
                          </div>
                          <div className="flex items-center">
                            <div
                              className={`w-2 h-2 rounded-full mr-2 ${
                                script.auto_lead_capture ? "bg-green-500" : "bg-gray-300"
                              }`}
                            />
                            <span className="text-sm">Auto Lead Capture</span>
                          </div>
                          <div className="flex items-center">
                            <div
                              className={`w-2 h-2 rounded-full mr-2 ${
                                script.track_utm_parameters ? "bg-green-500" : "bg-gray-300"
                              }`}
                            />
                            <span className="text-sm">UTM Parameters</span>
                          </div>
                          <div className="flex items-center">
                            <div
                              className={`w-2 h-2 rounded-full mr-2 ${
                                script.track_all_forms ? "bg-green-500" : "bg-gray-300"
                              }`}
                            />
                            <span className="text-sm">All Forms</span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  {script.custom_events.length > 0 && (
                    <div>
                      <h4 className="font-medium text-gray-900 mb-2">Custom Events</h4>
                      <div className="bg-gray-50 p-4 rounded-md">
                        <div className="flex flex-wrap gap-2">
                          {script.custom_events.map((event, index) => (
                            <span key={index} className="bg-purple-100 text-purple-800 text-xs px-2 py-1 rounded">
                              {event}
                            </span>
                          ))}
                        </div>
                      </div>
                    </div>
                  )}

                  {!script.track_all_forms && script.form_selectors.length > 0 && (
                    <div>
                      <h4 className="font-medium text-gray-900 mb-2">Form Selectors</h4>
                      <div className="bg-gray-50 p-4 rounded-md">
                        <div className="space-y-1">
                          {script.form_selectors.map((selector, index) => (
                            <code key={index} className="block bg-gray-800 text-gray-100 px-2 py-1 rounded text-sm">
                              {selector}
                            </code>
                          ))}
                        </div>
                      </div>
                    </div>
                  )}

                  <div>
                    <h4 className="font-medium text-gray-900 mb-2">Field Mappings</h4>
                    <div className="bg-gray-50 p-4 rounded-md">
                      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        {Object.entries(script.field_mappings).map(([key, values]) => (
                          <div key={key}>
                            <h5 className="text-sm font-medium text-gray-700 mb-1 capitalize">{key} Fields</h5>
                            <div className="space-y-1">
                              {values.map((value, index) => (
                                <code key={index} className="block bg-gray-200 text-gray-800 px-2 py-1 rounded text-xs">
                                  {value}
                                </code>
                              ))}
                            </div>
                          </div>
                        ))}
                      </div>
                    </div>
                  </div>
                </div>
              )}
            </div>
          </div>

          {/* Instructions Sidebar */}
          {showInstructions && (
            <div className="w-1/3 border-l border-gray-200 bg-gray-50 overflow-y-auto">
              <div className="p-6">
                <h3 className="text-lg font-medium text-gray-900 mb-4">Implementation Guide</h3>

                {isLoading ? (
                  <div className="animate-pulse space-y-4">
                    <div className="h-4 bg-gray-300 rounded w-3/4"></div>
                    <div className="h-4 bg-gray-300 rounded w-1/2"></div>
                    <div className="h-20 bg-gray-300 rounded"></div>
                  </div>
                ) : (
                  <div className="space-y-6">
                    {Object.entries(instructions).map(([key, instruction]: [string, any]) => (
                      <div key={key} className="border-b border-gray-200 pb-4 last:border-b-0">
                        <h4 className="font-medium text-gray-900 mb-2">{instruction.title}</h4>
                        <p className="text-sm text-gray-600 mb-3">{instruction.description}</p>
                        {instruction.code_example && (
                          <div className="relative">
                            <button
                              onClick={() => handleCopy(instruction.code_example, key)}
                              className="absolute top-2 right-2 p-1 bg-gray-700 text-white rounded hover:bg-gray-600 transition-colors"
                              title="Copy example"
                            >
                              {copied === key ? <Check className="h-3 w-3" /> : <Copy className="h-3 w-3" />}
                            </button>
                            <pre className="bg-gray-800 text-gray-100 p-3 rounded text-xs overflow-x-auto">
                              <code>{instruction.code_example}</code>
                            </pre>
                          </div>
                        )}
                      </div>
                    ))}
                  </div>
                )}
              </div>
            </div>
          )}
        </div>

        {/* Footer */}
        <div className="flex justify-end p-6 border-t border-gray-200 bg-gray-50">
          <button
            onClick={onClose}
            className="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  )
}
