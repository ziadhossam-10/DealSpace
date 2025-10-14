import { createApi } from "@reduxjs/toolkit/query/react"
import { customBaseQuery } from "../../../app/baseQueryHandler"

export interface ResponseTimeData {
  minutes: number
  formatted: string
}

export interface MessageLengthDistribution {
  short: number
  medium: number
  long: number
  very_long: number
}

export interface TextsAgentData {
  agent_id: number
  agent_name: string
  email: string
  texts_sent: number
  texts_received: number
  texts_delivered: number
  texts_failed: number
  unique_contacts_texted: number
  contacts_responded: number
  conversations_initiated: number
  conversations_active: number
  delivery_rate: number
  response_rate: number
  engagement_rate: number
  opt_outs: number
  carrier_filtered: number
  other_errors: number
  avg_texts_per_day: number
  avg_responses_per_day: number
  avg_response_time: ResponseTimeData
  texts_by_hour: number[]
  avg_message_length: number
  message_length_distribution: MessageLengthDistribution
}

export interface TextsTotals {
  texts_sent: number
  texts_received: number
  texts_delivered: number
  texts_failed: number
  unique_contacts_texted: number
  contacts_responded: number
  conversations_initiated: number
  conversations_active: number
  opt_outs: number
  carrier_filtered: number
  other_errors: number
  delivery_rate: number
  response_rate: number
  engagement_rate: number
}

export interface TextsTimeSeriesData {
  date: string
  texts_sent: number
  texts_received: number
  texts_delivered: number
  unique_contacts_texted: number
  delivery_rate: number
}

export interface TeamAverages {
  avg_texts_per_agent: number
  avg_delivery_rate: number
  avg_response_rate: number
  avg_engagement_rate: number
}

export interface SummaryStats {
  top_performer: {
    agent_name: string
    texts_sent: number
  } | null
  team_averages: TeamAverages
}

export interface TextsResponse {
  period: {
    start: string
    end: string
  }
  team_info: any | null
  agents: TextsAgentData[]
  totals: TextsTotals
  time_series: TextsTimeSeriesData[]
  summary_stats: SummaryStats
}

export interface TextsRequest {
  start_date?: string
  end_date?: string
  agent_ids?: number[]
  team_id?: number
}

export const textsReportApi = createApi({
  reducerPath: "textsReportApi",
  baseQuery: customBaseQuery,
  tagTypes: ["Texts"],
  endpoints: (builder) => ({
    getTexts: builder.query<TextsResponse, TextsRequest>({
      query: (params) => ({
        url: "/reports/texts",
        method: "POST",
        body: {
          start_date: params.start_date,
          end_date: params.end_date,
          agent_ids: params.agent_ids,
          team_id: params.team_id,
        },
      }),
      providesTags: ["Texts"],
    }),
    downloadTextsExcel: builder.mutation<void, TextsRequest>({
      query: (params) => ({
        url: "/reports/texts/download-excel",
        method: "POST",
        body: {
          start_date: params.start_date,
          end_date: params.end_date,
          agent_ids: params.agent_ids,
          team_id: params.team_id,
        },
        responseHandler: async (response: Response) => {
          const blob = await response.blob()
          const url = window.URL.createObjectURL(blob)
          const a = document.createElement("a")
          a.href = url
          a.download = `texts-report-${params.start_date}-to-${params.end_date}.xlsx`
          document.body.appendChild(a)
          a.click()
          window.URL.revokeObjectURL(url)
          a.remove()
        },
        cache: "no-cache",
      }),
    }),
  }),
})

export const { useGetTextsQuery, useDownloadTextsExcelMutation } = textsReportApi
