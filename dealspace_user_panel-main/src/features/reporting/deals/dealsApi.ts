import { createApi } from "@reduxjs/toolkit/query/react"
import { ApiResponse } from "../../../types/meta"
import { customBaseQuery } from "../../../app/baseQueryHandler"

export interface DealAgent {
  agent_id: number
  agent_name: string
  email: string
  deals_created: number
  deals_closed_won: number
  deals_closed_lost: number
  deals_in_pipeline: number
  total_deal_value: string | number
  closed_deal_value: number
  pipeline_value: string | number
  avg_deal_size: number
  total_commission: number
  agent_commission: number
  team_commission: number
  close_rate: number
  win_rate: number
  avg_time_to_close: number
  avg_time_in_current_stage: number
  avg_deals_per_day: number
  avg_value_per_day: number
  deals_by_stage: Record<string, number>
  deals_by_type: Record<string, number>
}

export interface DealTotals {
  deals_created: number
  deals_closed_won: number
  deals_closed_lost: number
  deals_in_pipeline: number
  total_deal_value: number
  closed_deal_value: number
  pipeline_value: number
  total_commission: number
  agent_commission: number
  team_commission: number
  close_rate: number
  win_rate: number
}

export interface DealTimeSeriesData {
  date: string
  deals_created: number
  deals_closed_won: number
  total_value: string | number
  closed_value: number
}

export interface DealStageAverage {
  stage_id: number
  stage_name: string
  deal_count: number
  avg_time_in_stage: number
  avg_deal_value: number
  total_value: number
}

export interface DealSourceBreakdown {
  [source: string]: {
    deals_count: number
    total_value: number
    closed_deals: number
    closed_value: number
    conversion_rate: number
  }
}

export interface Deal {
  id: number
  name: string
  stage: string
  type: string
  price: number
  commission_value: number
  agent_commission: number
  team_commission: number
  projected_close_date: string
  created_at: string
  updated_at: string
  time_in_stage: number
  time_to_close: number
  agents: Array<{
    id: number
    name: string
    email: string
  }>
  people: Array<{
    id: number
    name: string
    email: string | null
    phone: string | null
  }>
}

export interface DealsResponse {
  period: {
    start: string
    end: string
  }
  team_info: any
  filters: {
    stage_id: number | null
    type_id: number | null
    status: string
  }
  agents: DealAgent[]
  totals: DealTotals
  time_series: DealTimeSeriesData[]
  summary_stats: {
    top_performer_by_deals: any
    top_performer_by_value: any
    team_averages: {
      avg_deals_per_agent: number
      avg_closed_deals_per_agent: number
      avg_deal_value_per_agent: number
      avg_closed_value_per_agent: number
      avg_commission_per_agent: number
      team_close_rate: number
      team_win_rate: number
    }
  }
  stage_averages: DealStageAverage[]
  source_breakdown: DealSourceBreakdown
  deals_list: Deal[]
}

export interface DealsRequest {
  start_date?: string
  end_date?: string
  agent_ids?: number[]
  team_id?: number
  stage_id?: number
  type_id?: number
  status?: "all" | "current" | "archived"
}

export const dealsReportApi = createApi({
  reducerPath: "dealsReportApi",
  baseQuery: customBaseQuery,
  tagTypes: ["Deals"],
  endpoints: (builder) => ({
    getDeals: builder.query<DealsResponse, DealsRequest>({
      query: (params) => {
        const searchParams = new URLSearchParams()

        if (params.start_date) {
          searchParams.append("start_date", params.start_date)
        }
        if (params.end_date) {
          searchParams.append("end_date", params.end_date)
        }
        if (params.agent_ids?.length) {
          params.agent_ids.forEach((id) => searchParams.append("agent_ids[]", id.toString()))
        }
        if (params.team_id) {
          searchParams.append("team_id", params.team_id.toString())
        }
        if (params.stage_id) {
          searchParams.append("stage_id", params.stage_id.toString())
        }
        if (params.type_id) {
          searchParams.append("type_id", params.type_id.toString())
        }
        if (params.status) {
          searchParams.append("status", params.status)
        }

        return {
          url: `/reports/deals?${searchParams.toString()}`,
          method: "GET",
        }
      },
      providesTags: ["Deals"],
    }),
    downloadDealsExcel: builder.mutation<void, DealsRequest>({
      query: (params) => {
        const searchParams = new URLSearchParams()

        if (params.start_date) {
          searchParams.append("start_date", params.start_date)
        }
        if (params.end_date) {
          searchParams.append("end_date", params.end_date)
        }
        if (params.agent_ids?.length) {
          params.agent_ids.forEach((id) => searchParams.append("agent_ids[]", id.toString()))
        }
        if (params.team_id) {
          searchParams.append("team_id", params.team_id.toString())
        }
        if (params.stage_id) {
          searchParams.append("stage_id", params.stage_id.toString())
        }
        if (params.type_id) {
          searchParams.append("type_id", params.type_id.toString())
        }
        if (params.status) {
          searchParams.append("status", params.status)
        }

        return {
          url: `/reports/deals/export?${searchParams.toString()}`,
          method: "GET",
          responseHandler: async (response: Response) => {
            const blob = await response.blob()
            const url = window.URL.createObjectURL(blob)
            const a = document.createElement("a")
            a.href = url
            a.download = `deals-report-${new Date().toISOString().split("T")[0]}.xlsx`
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

export const { useGetDealsQuery, useDownloadDealsExcelMutation } = dealsReportApi
