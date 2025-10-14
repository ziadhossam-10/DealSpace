"use client"

import { useState, useEffect } from "react"
import { Calendar, Download, Filter, ChevronDown, Search, X } from "lucide-react"
import { useGetMarketingQuery, useDownloadMarketingExcelMutation } from "./marketingApi"
import MarketingMetrics from "./MarketingMetrics"
import MarketingTable from "./MarketingTable"
import MarketingChart from "./MarketingChart"
import MarketingPredefinedReportsSelector, {
  PREDEFINED_REPORTS,
  type PredefinedReport,
} from "./MarketingPredefinedReportsSelector"
import ReportsLayout from "../../../layout/ReportLayout"

const DATE_FILTER_OPTIONS = [
  { value: "today", label: "Today" },
  { value: "yesterday", label: "Yesterday" },
  { value: "last_7_days", label: "Last 7 Days" },
  { value: "last_14_days", label: "Last 14 Days" },
  { value: "last_30_days", label: "Last 30 Days" },
  { value: "this_month", label: "This Month" },
  { value: "last_month", label: "Last Month" },
  { value: "this_year", label: "This Year" },
  { value: "custom", label: "Custom Range" },
]

export default function MarketingReport() {
  const [dateFilter, setDateFilter] = useState("last_7_days")
  const [customDateRange, setCustomDateRange] = useState({
    start: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split("T")[0],
    end: new Date().toISOString().split("T")[0],
  })

  // Filters
  const [selectedPlatforms, setSelectedPlatforms] = useState<string[]>([])
  const [selectedSources, setSelectedSources] = useState<string[]>([])
  const [selectedCampaigns, setSelectedCampaigns] = useState<string[]>([])

  // Dropdown states
  const [platformDropdownOpen, setPlatformDropdownOpen] = useState(false)
  const [sourceDropdownOpen, setSourceDropdownOpen] = useState(false)
  const [campaignDropdownOpen, setCampaignDropdownOpen] = useState(false)

  // Search states
  const [platformSearchTerm, setPlatformSearchTerm] = useState("")
  const [sourceSearchTerm, setSourceSearchTerm] = useState("")
  const [campaignSearchTerm, setCampaignSearchTerm] = useState("")

  const [selectedReport, setSelectedReport] = useState<PredefinedReport>(PREDEFINED_REPORTS[0])
  const [downloadMarketingExcel] = useDownloadMarketingExcelMutation()

  const queryParams = {
    date_filter: dateFilter !== "custom" ? dateFilter : undefined,
    start_date: dateFilter === "custom" ? customDateRange.start : undefined,
    end_date: dateFilter === "custom" ? customDateRange.end : undefined,
    platforms: selectedPlatforms.length > 0 ? selectedPlatforms : undefined,
    sources: selectedSources.length > 0 ? selectedSources : undefined,
    campaigns: selectedCampaigns.length > 0 ? selectedCampaigns : undefined,
  }

  const { data, isLoading, error, refetch } = useGetMarketingQuery(queryParams)

  const reportData = data

  // Get unique values from the data for filtering
    const availablePlatforms = Array.from(new Set(reportData?.data?.campaigns?.map((campaign) => campaign.platform) || []))
    const availableSources = Array.from(new Set(reportData?.data?.campaigns?.map((campaign) => campaign.source) || []))
    const availableCampaigns = Array.from(new Set(reportData?.data?.campaigns?.map((campaign) => campaign.campaign) || []))

    const filteredPlatforms = availablePlatforms.filter((platform) =>
    platform.toLowerCase().includes(platformSearchTerm.toLowerCase()),
  )
  const filteredSources = availableSources.filter((source) =>
    source.toLowerCase().includes(sourceSearchTerm.toLowerCase()),
  )
  const filteredCampaigns = availableCampaigns.filter((campaign) =>
    campaign.toLowerCase().includes(campaignSearchTerm.toLowerCase()),
  )

  const handleDateRangeChange = (field: "start" | "end", value: string) => {
    setCustomDateRange((prev) => ({ ...prev, [field]: value }))
  }

  const handlePlatformToggle = (platform: string) => {
    setSelectedPlatforms((prev) => (prev.includes(platform) ? prev.filter((p) => p !== platform) : [...prev, platform]))
  }

  const handleSourceToggle = (source: string) => {
    setSelectedSources((prev) => (prev.includes(source) ? prev.filter((s) => s !== source) : [...prev, source]))
  }

  const handleCampaignToggle = (campaign: string) => {
    setSelectedCampaigns((prev) => (prev.includes(campaign) ? prev.filter((c) => c !== campaign) : [...prev, campaign]))
  }

  const handleExport = async () => {
    try {
      await downloadMarketingExcel(queryParams)
    } catch (err) {
      console.error("Failed to download Excel:", err)
    }
  }

  const getSelectedPlatformsText = () => {
    if (selectedPlatforms.length === 0) return "All platforms"
    if (selectedPlatforms.length === 1) return selectedPlatforms[0]
    return `${selectedPlatforms.length} Platforms`
  }

  const getSelectedSourcesText = () => {
    if (selectedSources.length === 0) return "All sources"
    if (selectedSources.length === 1) return selectedSources[0]
    return `${selectedSources.length} Sources`
  }

  const getSelectedCampaignsText = () => {
    if (selectedCampaigns.length === 0) return "All campaigns"
    if (selectedCampaigns.length === 1) return selectedCampaigns[0]
    return `${selectedCampaigns.length} Campaigns`
  }

  // Close dropdowns when clicking outside
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      const target = event.target as Element
      if (!target.closest(".platform-dropdown")) {
        setPlatformDropdownOpen(false)
      }
      if (!target.closest(".source-dropdown")) {
        setSourceDropdownOpen(false)
      }
      if (!target.closest(".campaign-dropdown")) {
        setCampaignDropdownOpen(false)
      }
    }
    document.addEventListener("mousedown", handleClickOutside)
    return () => document.removeEventListener("mousedown", handleClickOutside)
  }, [])

  if (error) {
    return (
      <div className="container mx-auto p-6">
        <div className="text-center py-12">
          <p className="text-red-600">Error loading marketing data. Please try again.</p>
          <button onClick={() => refetch()} className="mt-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
            Retry
          </button>
        </div>
      </div>
    )
  }

  return (
    <ReportsLayout>
      <div className="space-y-6">
        {/* Header */}
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-bold text-gray-900">Marketing Report</h1>
            <p className="text-gray-600">Analyze marketing campaign performance and ROI across all channels.</p>
          </div>
          <div className="flex items-center gap-3">
            <button
              onClick={handleExport}
              className="flex items-center px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
            >
              <Download className="w-4 h-4 mr-2" />
              Export
            </button>
          </div>
        </div>

        {/* Filters */}
        <div className="bg-white rounded-lg shadow p-6">
          <div className="flex flex-wrap items-center gap-4">
            {/* Date Filter */}
            <div className="flex items-center gap-2">
              <Calendar className="w-4 h-4 text-gray-500" />
              <select
                value={dateFilter}
                onChange={(e) => setDateFilter(e.target.value)}
                className="border border-gray-300 rounded px-3 py-2 text-sm"
              >
                {DATE_FILTER_OPTIONS.map((option) => (
                  <option key={option.value} value={option.value}>
                    {option.label}
                  </option>
                ))}
              </select>
            </div>

            {/* Custom Date Range */}
            {dateFilter === "custom" && (
              <>
                <input
                  type="date"
                  value={customDateRange.start}
                  onChange={(e) => handleDateRangeChange("start", e.target.value)}
                  className="border border-gray-300 rounded px-3 py-2 text-sm"
                />
                <span className="text-gray-500">to</span>
                <input
                  type="date"
                  value={customDateRange.end}
                  onChange={(e) => handleDateRangeChange("end", e.target.value)}
                  className="border border-gray-300 rounded px-3 py-2 text-sm"
                />
              </>
            )}

            {/* Platform Selector */}
            <div className="relative platform-dropdown">
              <button
                onClick={() => setPlatformDropdownOpen(!platformDropdownOpen)}
                className="flex items-center gap-2 border border-gray-300 rounded px-3 py-2 text-sm hover:bg-gray-50"
              >
                <Filter className="w-4 h-4 text-gray-500" />
                {getSelectedPlatformsText()}
                <ChevronDown className="w-4 h-4 text-gray-500" />
              </button>
              {platformDropdownOpen && (
                <div className="absolute top-full left-0 mt-1 w-64 bg-white border border-gray-300 rounded-md shadow-lg z-10 max-h-60 overflow-hidden">
                  <div className="p-3 border-b">
                    <div className="relative">
                      <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
                      <input
                        type="text"
                        placeholder="Search platforms..."
                        value={platformSearchTerm}
                        onChange={(e) => setPlatformSearchTerm(e.target.value)}
                        className="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                      />
                    </div>
                  </div>
                  <div className="max-h-40 overflow-y-auto p-2">
                    <label className="flex items-center p-2 hover:bg-gray-50 rounded">
                      <input
                        type="checkbox"
                        checked={selectedPlatforms.length === 0}
                        onChange={() => setSelectedPlatforms([])}
                        className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                      />
                      <span className="ml-2 text-sm">All platforms</span>
                    </label>
                    {filteredPlatforms.map((platform) => (
                      <label key={platform} className="flex items-center p-2 hover:bg-gray-50 rounded">
                        <input
                          type="checkbox"
                          checked={selectedPlatforms.includes(platform)}
                          onChange={() => handlePlatformToggle(platform)}
                          className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        />
                        <span className="ml-2 text-sm">{platform}</span>
                      </label>
                    ))}
                  </div>
                </div>
              )}
            </div>

            {/* Source Selector */}
            <div className="relative source-dropdown">
              <button
                onClick={() => setSourceDropdownOpen(!sourceDropdownOpen)}
                className="flex items-center gap-2 border border-gray-300 rounded px-3 py-2 text-sm hover:bg-gray-50"
              >
                <Filter className="w-4 h-4 text-gray-500" />
                {getSelectedSourcesText()}
                <ChevronDown className="w-4 h-4 text-gray-500" />
              </button>
              {sourceDropdownOpen && (
                <div className="absolute top-full left-0 mt-1 w-64 bg-white border border-gray-300 rounded-md shadow-lg z-10 max-h-60 overflow-hidden">
                  <div className="p-3 border-b">
                    <div className="relative">
                      <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
                      <input
                        type="text"
                        placeholder="Search sources..."
                        value={sourceSearchTerm}
                        onChange={(e) => setSourceSearchTerm(e.target.value)}
                        className="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                      />
                    </div>
                  </div>
                  <div className="max-h-40 overflow-y-auto p-2">
                    <label className="flex items-center p-2 hover:bg-gray-50 rounded">
                      <input
                        type="checkbox"
                        checked={selectedSources.length === 0}
                        onChange={() => setSelectedSources([])}
                        className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                      />
                      <span className="ml-2 text-sm">All sources</span>
                    </label>
                    {filteredSources.map((source) => (
                      <label key={source} className="flex items-center p-2 hover:bg-gray-50 rounded">
                        <input
                          type="checkbox"
                          checked={selectedSources.includes(source)}
                          onChange={() => handleSourceToggle(source)}
                          className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        />
                        <span className="ml-2 text-sm">{source}</span>
                      </label>
                    ))}
                  </div>
                </div>
              )}
            </div>

            {/* Campaign Selector */}
            <div className="relative campaign-dropdown">
              <button
                onClick={() => setCampaignDropdownOpen(!campaignDropdownOpen)}
                className="flex items-center gap-2 border border-gray-300 rounded px-3 py-2 text-sm hover:bg-gray-50"
              >
                <Filter className="w-4 h-4 text-gray-500" />
                {getSelectedCampaignsText()}
                <ChevronDown className="w-4 h-4 text-gray-500" />
              </button>
              {campaignDropdownOpen && (
                <div className="absolute top-full left-0 mt-1 w-64 bg-white border border-gray-300 rounded-md shadow-lg z-10 max-h-60 overflow-hidden">
                  <div className="p-3 border-b">
                    <div className="relative">
                      <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
                      <input
                        type="text"
                        placeholder="Search campaigns?..."
                        value={campaignSearchTerm}
                        onChange={(e) => setCampaignSearchTerm(e.target.value)}
                        className="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                      />
                    </div>
                  </div>
                  <div className="max-h-40 overflow-y-auto p-2">
                    <label className="flex items-center p-2 hover:bg-gray-50 rounded">
                      <input
                        type="checkbox"
                        checked={selectedCampaigns.length === 0}
                        onChange={() => setSelectedCampaigns([])}
                        className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                      />
                      <span className="ml-2 text-sm">All campaigns</span>
                    </label>
                    {filteredCampaigns.map((campaign) => (
                      <label key={campaign} className="flex items-center p-2 hover:bg-gray-50 rounded">
                        <input
                          type="checkbox"
                          checked={selectedCampaigns.includes(campaign)}
                          onChange={() => handleCampaignToggle(campaign)}
                          className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        />
                        <span className="ml-2 text-sm">{campaign}</span>
                      </label>
                    ))}
                  </div>
                </div>
              )}
            </div>
          </div>

          {/* Selected Filters Display */}
          {(selectedPlatforms.length > 0 || selectedSources.length > 0 || selectedCampaigns.length > 0) && (
            <div className="flex items-center gap-2 mt-4">
              {selectedPlatforms.map((platform) => (
                <div
                  key={platform}
                  className="flex items-center bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-sm"
                >
                  Platform: {platform}
                  <button
                    onClick={() => handlePlatformToggle(platform)}
                    className="ml-1 hover:bg-blue-200 rounded-full p-0.5"
                  >
                    <X className="w-3 h-3" />
                  </button>
                </div>
              ))}
              {selectedSources.map((source) => (
                <div
                  key={source}
                  className="flex items-center bg-green-100 text-green-800 px-2 py-1 rounded-full text-sm"
                >
                  Source: {source}
                  <button
                    onClick={() => handleSourceToggle(source)}
                    className="ml-1 hover:bg-green-200 rounded-full p-0.5"
                  >
                    <X className="w-3 h-3" />
                  </button>
                </div>
              ))}
              {selectedCampaigns.map((campaign) => (
                <div
                  key={campaign}
                  className="flex items-center bg-purple-100 text-purple-800 px-2 py-1 rounded-full text-sm"
                >
                  Campaign: {campaign}
                  <button
                    onClick={() => handleCampaignToggle(campaign)}
                    className="ml-1 hover:bg-purple-200 rounded-full p-0.5"
                  >
                    <X className="w-3 h-3" />
                  </button>
                </div>
              ))}
            </div>
          )}

          <div className="mt-4 text-sm text-gray-500">
            Reporting results may be cached for up to 10 minutes.
            <button className="text-blue-600 hover:underline ml-1" onClick={() => refetch()}>
              Refresh results.
            </button>
          </div>
        </div>

        {/* Predefined Reports Selector */}
        <MarketingPredefinedReportsSelector selectedReport={selectedReport} onReportChange={setSelectedReport} />

        {/* Chart Visualization */}
        <MarketingChart campaigns={reportData?.data?.campaigns || []} selectedReport={selectedReport} isLoading={isLoading} />

        {/* Metrics Cards */}
        {reportData && <MarketingMetrics totals={reportData?.data?.totals} />}

        {/* Data Table */}
        <MarketingTable campaigns={reportData?.data?.campaigns || []} isLoading={isLoading} />

        {/* Additional Info */}
        {reportData?.data?.period && (
          <div className="text-sm text-gray-500 text-center">
            Period: {reportData?.data?.period.start} to {reportData?.data?.period.end}
          </div>
        )}
      </div>
    </ReportsLayout>
  )
}
