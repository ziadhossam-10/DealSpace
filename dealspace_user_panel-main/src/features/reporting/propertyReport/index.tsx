"use client"

import { useState, useEffect } from "react"
import { Calendar, Download, MapPin, Filter, ChevronDown, Search, X } from "lucide-react"
import ReportsLayout from "../../../layout/ReportLayout"
import {
  useGetPropertyReportsQuery,
  useGetPropertyReportsByZipQuery,
  useDownloadPropertyReportsExcelMutation,
} from "./propertyReportsApi"
import { useGetEventTypesQuery } from "./eventTypesApi"
import PropertyReportMetrics from "./PropertyReportMetrics"
import PropertyReportTable from "./PropertyReportTable"
import PropertyMapView from "./PropertyMapView"

// Helper function to format date to YYYY-MM-DD
const formatDateForAPI = (dateString: string) => {
  return dateString // API expects YYYY-MM-DD format
}

export default function PropertyReport() {
  const [dateRange, setDateRange] = useState({
    start: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split("T")[0],
    end: new Date().toISOString().split("T")[0],
  })

  // View toggle - property or zip code
  const [viewMode, setViewMode] = useState<"property" | "zipcode">("property")

  // Event type selection
  const [selectedEventTypes, setSelectedEventTypes] = useState<string[]>([])
  const [eventTypeDropdownOpen, setEventTypeDropdownOpen] = useState(false)
  const [eventTypeSearchTerm, setEventTypeSearchTerm] = useState("")

  // Selected property for highlighting
  const [selectedProperty, setSelectedProperty] = useState<string | null>(null)

  const [downloadPropertyReportsExcel] = useDownloadPropertyReportsExcelMutation()

  // Get event types
  const { data: eventTypesData } = useGetEventTypesQuery()
  const eventTypes = eventTypesData?.data || []

  // Get property reports data
  const {
    data: propertyData,
    isLoading: isLoadingProperty,
    error: propertyError,
    refetch: refetchProperty,
  } = useGetPropertyReportsQuery(
    {
      date_from: formatDateForAPI(dateRange.start),
      date_to: formatDateForAPI(dateRange.end),
      event_types: selectedEventTypes.length > 0 ? selectedEventTypes : undefined,
    },
    {
      skip: viewMode !== "property",
    },
  )

  // Get zip code reports data
  const {
    data: zipData,
    isLoading: isLoadingZip,
    error: zipError,
    refetch: refetchZip,
  } = useGetPropertyReportsByZipQuery(
    {
      date_from: formatDateForAPI(dateRange.start),
      date_to: formatDateForAPI(dateRange.end),
      event_types: selectedEventTypes.length > 0 ? selectedEventTypes : undefined,
    },
    {
      skip: viewMode !== "zipcode",
    },
  )

  const currentData = viewMode === "property" ? propertyData : zipData
  const isLoading = viewMode === "property" ? isLoadingProperty : isLoadingZip
  const error = viewMode === "property" ? propertyError : zipError

  const handleDateRangeChange = (field: "start" | "end", value: string) => {
    setDateRange((prev) => ({ ...prev, [field]: value }))
  }

  const handleEventTypeToggle = (eventType: string) => {
    setSelectedEventTypes((prev) =>
      prev.includes(eventType) ? prev.filter((type) => type !== eventType) : [...prev, eventType],
    )
  }

  const handleExport = async () => {
    try {
      await downloadPropertyReportsExcel({
        date_from: formatDateForAPI(dateRange.start),
        date_to: formatDateForAPI(dateRange.end),
        event_types: selectedEventTypes.length > 0 ? selectedEventTypes : undefined,
        view_mode: viewMode,
      })
    } catch (err) {
      console.error("Failed to download Excel:", err)
    }
  }

  const getSelectedEventTypesText = () => {
    if (selectedEventTypes.length === 0) return "All event types"
    if (selectedEventTypes.length === 1) {
      return selectedEventTypes[0]
    }
    return `${selectedEventTypes.length} Event Types`
  }

  const filteredEventTypes = eventTypes.filter((type) => type.toLowerCase().includes(eventTypeSearchTerm.toLowerCase()))

  // Close dropdowns when clicking outside
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      const target = event.target as Element
      if (!target.closest(".eventtype-dropdown")) {
        setEventTypeDropdownOpen(false)
      }
    }
    document.addEventListener("mousedown", handleClickOutside)
    return () => document.removeEventListener("mousedown", handleClickOutside)
  }, [])

  const refetch = () => {
    if (viewMode === "property") {
      refetchProperty()
    } else {
      refetchZip()
    }
  }

  if (error) {
    return (
      <ReportsLayout>
        <div className="text-center py-12">
          <p className="text-red-600">Error loading property report data. Please try again.</p>
          <button onClick={refetch} className="mt-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
            Retry
          </button>
        </div>
      </ReportsLayout>
    )
  }

  return (
    <ReportsLayout>
      <div className="space-y-6">
        {/* Header */}
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-bold text-gray-900">Property Reports</h1>
            <p className="text-gray-600">
              {viewMode === "property"
                ? "See property inquiries and engagement metrics on the map"
                : "View inquiry patterns by zip code areas"}
            </p>
          </div>
        </div>

        {/* Filters */}
        <div className="bg-white rounded-lg shadow p-6">
          <div className="flex flex-wrap items-center gap-4">
            {/* Date Range */}
            <div className="flex items-center gap-2">
              <Calendar className="w-4 h-4 text-gray-500" />
              <input
                type="date"
                value={dateRange.start}
                onChange={(e) => handleDateRangeChange("start", e.target.value)}
                className="border border-gray-300 rounded px-3 py-2 text-sm"
              />
              <span className="text-gray-500">to</span>
              <input
                type="date"
                value={dateRange.end}
                onChange={(e) => handleDateRangeChange("end", e.target.value)}
                className="border border-gray-300 rounded px-3 py-2 text-sm"
              />
            </div>

            {/* View Mode Toggle */}
            <div className="flex items-center gap-2 border border-gray-300 rounded px-3 py-2">
              <MapPin className="w-4 h-4 text-gray-500" />
              <button
                onClick={() => setViewMode("property")}
                className={`px-3 py-1 rounded text-sm ${
                  viewMode === "property" ? "bg-blue-100 text-blue-800" : "text-gray-600 hover:bg-gray-100"
                }`}
              >
                By Property
              </button>
              <button
                onClick={() => setViewMode("zipcode")}
                className={`px-3 py-1 rounded text-sm ${
                  viewMode === "zipcode" ? "bg-blue-100 text-blue-800" : "text-gray-600 hover:bg-gray-100"
                }`}
              >
                By Zip Code
              </button>
            </div>

            {/* Event Type Selector */}
            <div className="relative eventtype-dropdown">
              <button
                onClick={() => setEventTypeDropdownOpen(!eventTypeDropdownOpen)}
                className="flex items-center gap-2 border border-gray-300 rounded px-3 py-2 text-sm hover:bg-gray-50"
              >
                <Filter className="w-4 h-4 text-gray-500" />
                {getSelectedEventTypesText()}
                <ChevronDown className="w-4 h-4 text-gray-500" />
              </button>

              {eventTypeDropdownOpen && (
                <div className="absolute top-full left-0 mt-1 w-80 bg-white border border-gray-300 rounded-md shadow-lg z-10 max-h-96 overflow-hidden">
                  <div className="p-3 border-b">
                    <div className="relative">
                      <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
                      <input
                        type="text"
                        placeholder="Search event types..."
                        value={eventTypeSearchTerm}
                        onChange={(e) => setEventTypeSearchTerm(e.target.value)}
                        className="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                      />
                    </div>
                  </div>
                  <div className="max-h-60 overflow-y-auto">
                    <div className="p-2">
                      <label className="flex items-center p-2 hover:bg-gray-50 rounded">
                        <input
                          type="checkbox"
                          checked={selectedEventTypes.length === 0}
                          onChange={() => setSelectedEventTypes([])}
                          className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        />
                        <span className="ml-2 text-sm">All event types</span>
                      </label>
                      {filteredEventTypes.map((eventType) => (
                        <label key={eventType} className="flex items-center p-2 hover:bg-gray-50 rounded">
                          <input
                            type="checkbox"
                            checked={selectedEventTypes.includes(eventType)}
                            onChange={() => handleEventTypeToggle(eventType)}
                            className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                          />
                          <span className="ml-2 text-sm">{eventType}</span>
                        </label>
                      ))}
                    </div>
                  </div>
                </div>
              )}
            </div>
          </div>

          {/* Selected Event Types Display */}
          {selectedEventTypes.length > 0 && (
            <div className="flex items-center gap-2 mt-4">
              {selectedEventTypes.map((eventType) => (
                <div
                  key={eventType}
                  className="flex items-center bg-gray-100 text-gray-800 px-2 py-1 rounded-full text-sm"
                >
                  {eventType}
                  <button
                    onClick={() => handleEventTypeToggle(eventType)}
                    className="ml-1 hover:bg-gray-200 rounded-full p-0.5"
                  >
                    <X className="w-3 h-3" />
                  </button>
                </div>
              ))}
            </div>
          )}

          <div className="mt-4 text-sm text-gray-500">
            Reporting results may be cached for up to 10 minutes.
            <button className="text-blue-600 hover:underline ml-1" onClick={refetch}>
              Refresh results.
            </button>
          </div>
        </div>

        {/* Map and Data Visualization */}
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {/* Property/Zip Code List */}
          <div className="lg:col-span-1">
            <div className="bg-white rounded-lg shadow">
              <div className="p-4 border-b">
                <h3 className="font-semibold text-gray-900">
                  {viewMode === "property" ? "Properties" : "Zip Codes"} by Inquiries
                </h3>
                <p className="text-sm text-gray-600">Click to highlight on map</p>
              </div>
              <div className="max-h-96 overflow-y-auto">
                {isLoading ? (
                  <div className="p-4 text-center text-gray-500">Loading...</div>
                ) : currentData?.data?.length === 0 ? (
                  <div className="p-4 text-center text-gray-500">No data found</div>
                ) : (
                  <div className="divide-y">
                    {currentData?.data?.map((item: any, index: number) => (
                      <div
                        key={viewMode === "property" ? item.mls_number : item.zip_code}
                        className={`p-4 cursor-pointer hover:bg-gray-50 ${
                          selectedProperty === (viewMode === "property" ? item.mls_number : item.zip_code)
                            ? "bg-blue-50 border-l-4 border-blue-500"
                            : ""
                        }`}
                        onClick={() => setSelectedProperty(viewMode === "property" ? item.mls_number : item.zip_code)}
                      >
                        {viewMode === "property" ? (
                          <div>
                            <div className="font-medium text-gray-900">{item.street}</div>
                            <div className="text-sm text-gray-600">
                              {item.city}, {item.state} {item.zip_code}
                            </div>
                            <div className="text-sm text-green-600 font-medium">
                              ${Number.parseInt(item.price).toLocaleString()}
                            </div>
                            <div className="text-xs text-gray-500 mt-1">
                              {item.total_events} inquiries • {item.unique_leads} leads
                            </div>
                          </div>
                        ) : (
                          <div>
                            <div className="font-medium text-gray-900">{item.zip_code}</div>
                            <div className="text-sm text-gray-600">
                              {item.city}, {item.state}
                            </div>
                            <div className="text-xs text-gray-500 mt-1">
                              {item.total_events} inquiries • {item.unique_properties} properties
                            </div>
                          </div>
                        )}
                      </div>
                    ))}
                  </div>
                )}
              </div>
            </div>
          </div>

          {/* Map View */}
          <div className="lg:col-span-2">
            <PropertyMapView
              data={currentData?.data || []}
              viewMode={viewMode}
              selectedProperty={selectedProperty}
              onPropertySelect={setSelectedProperty}
              isLoading={isLoading}
            />
          </div>
        </div>

        {/* Metrics Cards */}
        {currentData && <PropertyReportMetrics data={currentData.data} viewMode={viewMode} />}

        {/* Data Table */}
        <PropertyReportTable data={currentData?.data || []} viewMode={viewMode} isLoading={isLoading} />
      </div>
    </ReportsLayout>
  )
}
