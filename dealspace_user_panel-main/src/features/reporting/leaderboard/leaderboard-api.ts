import { createApi } from "@reduxjs/toolkit/query/react"
import { customBaseQuery } from "../../../app/baseQueryHandler"

export interface LeaderboardAgent {
  agent_id: number
  agent_name: string
  agent_email: string
  agent_avatar: string | null
  deals_closed: number
  total_closed_value: number
  average_deal_size: number
  total_commission: number
  agent_commission: number
  deals_in_pipeline: number
  pipeline_value: number
  deals_created_in_period: number
  overdue_deals: number
  overdue_value: number
  performance_stats: {
    close_rate: number
    avg_days_to_close: number
    momentum_score: number
    on_time_close_rate: number
  }
  rank: number
}

export interface LeaderboardSummary {
  total_agents: number
  active_agents: number
  total_deals_closed: number
  total_closed_value: number
  total_commission: number
  average_deal_size: number
  pipeline_summary: {
    total_deals: number
    total_value: number
    average_deal_size: number
  }
  overdue_summary: {
    total_deals: number
    total_value: number
    percentage_of_pipeline: number
  }
  team_performance: {
    avg_deals_per_agent: number
    avg_value_per_agent: number
    avg_commission_per_agent: number
  }
}

export interface LeaderboardResponse {
  timeframe: {
    type: string
    start_date: string
    end_date: string
    display_name: string
  }
  filters: {
    stage_id: number | null
    type_id: number | null
    team_id: number | null
    excluded_users: number
    limit: string
  }
  team_info: any
  leaderboard: LeaderboardAgent[]
  summary: LeaderboardSummary
  last_updated: string
}

export interface LeaderboardOptionsResponse {
  teams: Array<{ id: number; name: string; email: string }>
  agents: Array<{ id: number; name: string; email: string }>
  stages: Array<{ id: number; name: string }>
  types: Array<{ id: number; name: string }>
}

export interface LeaderboardRequest {
  timeframe?: string
  limit?: number
  stage_id?: number
  type_id?: number
  team_id?: number
}

export const leaderboardApi = createApi({
  reducerPath: "leaderboardApi",
  baseQuery: customBaseQuery,
  tagTypes: ["Leaderboard"],
  endpoints: (builder) => ({
    getLeaderboard: builder.query<LeaderboardResponse, LeaderboardRequest>({
      query: (params) => {
        const searchParams = new URLSearchParams()

        if (params.timeframe) {
          searchParams.append("timeframe", params.timeframe)
        }
        if (params.limit) {
          searchParams.append("limit", params.limit.toString())
        }
        if (params.stage_id) {
          searchParams.append("stage_id", params.stage_id.toString())
        }
        if (params.type_id) {
          searchParams.append("type_id", params.type_id.toString())
        }
        if (params.team_id) {
          searchParams.append("team_id", params.team_id.toString())
        }

        return {
          url: `/reports/deals/leaderboard?${searchParams.toString()}`,
          method: "GET",
        }
      },
      providesTags: ["Leaderboard"],
    }),
    getLeaderboardOptions: builder.query<LeaderboardOptionsResponse, void>({
      query: () => ({
        url: "/reports/deals/leaderboard/options",
        method: "GET",
      }),
    }),
  }),
})

export const { useGetLeaderboardQuery, useGetLeaderboardOptionsQuery } = leaderboardApi
