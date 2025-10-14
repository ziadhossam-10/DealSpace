import { createApi } from "@reduxjs/toolkit/query/react"
import { customBaseQuery } from "../../../app/baseQueryHandler"

export interface AgentActivityData {
  agent_id: number
  agent_name: string
  email: string
  new_leads: number
  initially_assigned_leads: number
  currently_assigned_leads: number
  calls: number
  emails: number
  texts: number
  notes: number
  tasks_completed: number
  appointments: number
  appointments_set: number
  leads_not_acted_on: number
  leads_not_called: number
  leads_not_emailed: number
  leads_not_texted: number
  avg_speed_to_action: number
  avg_speed_to_first_call: number
  avg_speed_to_first_email: number
  avg_speed_to_first_text: number
  avg_contact_attempts: number
  avg_call_attempts: number
  avg_email_attempts: number
  avg_text_attempts: number
  response_rate: number
  email_response_rate: number
  phone_response_rate: number
  text_response_rate: number
}

export interface AgentActivityTotals {
  new_leads: number
  initially_assigned_leads: number
  currently_assigned_leads: number
  calls: number
  emails: number
  texts: number
  notes: number
  tasks_completed: number
  appointments: number
  appointments_set: number
  leads_not_acted_on: number
  leads_not_called: number
  leads_not_emailed: number
  leads_not_texted: number
  avg_speed_to_action: number
  avg_speed_to_first_call: number
  avg_speed_to_first_email: number
  avg_speed_to_first_text: number
  avg_contact_attempts: number
  avg_call_attempts: number
  avg_email_attempts: number
  avg_text_attempts: number
  response_rate: number
  email_response_rate: number
  phone_response_rate: number
  text_response_rate: number
}

export interface TimeSeriesData {
  date: string
  new_leads: number
  calls: number
  emails: number
  texts: number
  appointments_set: number
}

export interface AgentActivityResponse {
  period: {
    start: string
    end: string
  }
  agents: AgentActivityData[]
  totals: AgentActivityTotals
  time_series: TimeSeriesData[]
}

export interface AgentActivityRequest {
  start_date?: string
  end_date?: string
  agent_ids?: number[]
  lead_types?: number[]
  team_id?: number
}

export const agentActivityApi = createApi({
  reducerPath: "agentActivityApi",
  baseQuery: customBaseQuery,
  tagTypes: ["AgentActivity"],
  endpoints: (builder) => ({
    getAgentActivity: builder.query<AgentActivityResponse, AgentActivityRequest>({
      query: (params) => ({
        url: "/reports/agent-activity",
        method: "POST",
        body: {
          start_date: params.start_date,
          end_date: params.end_date,
          agent_ids: params.agent_ids,
          lead_types: params.lead_types,
          team_id: params.team_id
        },
      }),
      providesTags: ["AgentActivity"],
    }),
    downloadAgentActivityExcel: builder.mutation<void, AgentActivityRequest>({
      query: (params) => ({
        url: "/reports/agent-activity/download-excel",
        method: "POST",
        body: {
          start_date: params.start_date,
          end_date: params.end_date,
          agent_ids: params.agent_ids,
          lead_types: params.lead_types,
        },
        responseHandler: async (response: Response) => {
          const blob = await response.blob()
          const url = window.URL.createObjectURL(blob)
          const a = document.createElement('a')
          a.href = url
          a.download = `agent-activity-${params.start_date}-to-${params.end_date}.xlsx`
          document.body.appendChild(a)
          a.click()
          window.URL.revokeObjectURL(url)
          a.remove()
        },
        cache: 'no-cache',
      }),
    }),
  }),
})

export const { 
  useGetAgentActivityQuery,
  useDownloadAgentActivityExcelMutation 
} = agentActivityApi