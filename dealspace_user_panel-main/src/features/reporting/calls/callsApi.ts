import { createApi } from "@reduxjs/toolkit/query/react"
import { customBaseQuery } from "../../../app/baseQueryHandler"

export interface TimeData {
  seconds: number | string
  formatted: string
}

export interface CallsAgentData {
  agent_id: number
  agent_name: string
  email: string
  calls_made: number
  calls_connected: number
  conversations: number
  calls_received: number
  calls_missed: number
  total_talk_time: TimeData
  avg_call_duration: TimeData
  avg_conversation_duration: TimeData
  avg_answer_time: TimeData
  connection_rate: number
  conversation_rate: number
  answer_rate: number
  unique_contacts_called: number
  contacts_reached: number
  avg_calls_per_day: number
  avg_talk_time_per_day: TimeData
  outcomes: Record<string, number> | []
}

export interface CallsTotals {
  calls_made: number
  calls_connected: number
  conversations: number
  calls_received: number
  calls_missed: number
  total_talk_time: TimeData
  unique_contacts_called: number
  contacts_reached: number
  connection_rate: number
  conversation_rate: number
  answer_rate: number
}

export interface CallsTimeSeriesData {
  date: string
  calls_made: number
  calls_connected: number
  conversations: number
  calls_received: number
  total_talk_time: TimeData
}

export interface TeamAverages {
  avg_calls_per_agent: number
  avg_connection_rate: number
  avg_conversation_rate: number
  avg_talk_time_per_agent: TimeData
}

export interface SummaryStats {
  top_performer: {
    agent_name: string
    calls_made: number
  }
  team_averages: TeamAverages
}

export interface CallsResponse {
  period: {
    start: string
    end: string
  }
  team_info: any | null
  agents: CallsAgentData[]
  totals: CallsTotals
  time_series: CallsTimeSeriesData[]
  summary_stats: SummaryStats
}

export interface CallsRequest {
  start_date?: string
  end_date?: string
  agent_ids?: number[]
  team_id?: number
}

export const callsReportApi = createApi({
  reducerPath: "callsReportApi",
  baseQuery: customBaseQuery,
  tagTypes: ["Calls"],
  endpoints: (builder) => ({
    getCalls: builder.query<CallsResponse, CallsRequest>({
      query: (params) => ({
        url: "/reports/calls",
        method: "POST",
        body: {
          start_date: params.start_date,
          end_date: params.end_date,
          agent_ids: params.agent_ids,
          team_id: params.team_id,
        },
      }),
      providesTags: ["Calls"],
    }),
    downloadCallsExcel: builder.mutation<void, CallsRequest>({
      query: (params) => ({
        url: "/reports/calls/download-excel",
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
          a.download = `calls-report-${params.start_date}-to-${params.end_date}.xlsx`
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

export const { useGetCallsQuery, useDownloadCallsExcelMutation } = callsReportApi
