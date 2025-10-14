import { createApi } from "@reduxjs/toolkit/query/react"
import { customBaseQuery } from "../../../app/baseQueryHandler"
import { ApiResponse } from "../../../types/meta"

export interface MarketingCampaignData {
  platform: string
  source: string
  medium: string
  campaign: string
  source_count: number
  leads: number
  appointments: number
  people_with_appointments: number
  closed_deals: number
  deal_value: number
  total_events: number
}

export interface MarketingTotals {
  total_leads: number
  total_appointments: number
  total_people_with_appointments: number
  total_closed_deals: number
  total_deal_value: number
}

export interface MarketingTimeSeriesData {
  date: string
  leads: number
  appointments: number
  closed_deals: number
  deal_value: number
  total_events: number
}

export interface MarketingResponse {
  period: {
    start: string
    end: string
  }
  campaigns: MarketingCampaignData[]
  totals: MarketingTotals
  time_series?: MarketingTimeSeriesData[]
}

export interface MarketingRequest {
  date_filter?: string
  start_date?: string
  end_date?: string
  platforms?: string[]
  sources?: string[]
  campaigns?: string[]
}

export const marketingApi = createApi({
  reducerPath: "marketingApi",
  baseQuery: customBaseQuery,
  tagTypes: ["Marketing"],
  endpoints: (builder) => ({
    getMarketing: builder.query<ApiResponse<MarketingResponse>, MarketingRequest>({
      query: (params) => {
        const searchParams = new URLSearchParams()

        if (params.date_filter) {
          searchParams.append("date_filter", params.date_filter)
        }
        if (params.start_date) {
          searchParams.append("start_date", params.start_date)
        }
        if (params.end_date) {
          searchParams.append("end_date", params.end_date)
        }
        if (params.platforms?.length) {
          params.platforms.forEach((platform) => searchParams.append("platforms[]", platform))
        }
        if (params.sources?.length) {
          params.sources.forEach((source) => searchParams.append("sources[]", source))
        }
        if (params.campaigns?.length) {
          params.campaigns.forEach((campaign) => searchParams.append("campaigns[]", campaign))
        }

        return {
          url: `/reports/marketing?${searchParams.toString()}`,
          method: "GET",
        }
      },
      providesTags: ["Marketing"],
    }),
    downloadMarketingExcel: builder.mutation<void, MarketingRequest>({
      query: (params) => {
        const searchParams = new URLSearchParams()

        if (params.date_filter) {
          searchParams.append("date_filter", params.date_filter)
        }
        if (params.start_date) {
          searchParams.append("start_date", params.start_date)
        }
        if (params.end_date) {
          searchParams.append("end_date", params.end_date)
        }
        if (params.platforms?.length) {
          params.platforms.forEach((platform) => searchParams.append("platforms[]", platform))
        }
        if (params.sources?.length) {
          params.sources.forEach((source) => searchParams.append("sources[]", source))
        }
        if (params.campaigns?.length) {
          params.campaigns.forEach((campaign) => searchParams.append("campaigns[]", campaign))
        }

        return {
          url: `/reports/marketing/export?${searchParams.toString()}`,
          method: "GET",
          responseHandler: async (response: Response) => {
            const blob = await response.blob()
            const url = window.URL.createObjectURL(blob)
            const a = document.createElement("a")
            a.href = url
            a.download = `marketing-report-${new Date().toISOString().split("T")[0]}.xlsx`
            document.body.appendChild(a)
            a.click()
            window.URL.revokeObjectURL(url)
            a.remove()
          },
          cache: "no-cache",
        }
      },
    }),
  }),
})

export const { useGetMarketingQuery, useDownloadMarketingExcelMutation } = marketingApi
