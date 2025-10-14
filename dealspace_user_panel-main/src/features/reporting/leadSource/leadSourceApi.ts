import { createApi } from "@reduxjs/toolkit/query/react"
import { customBaseQuery } from "../../../app/baseQueryHandler"

export interface LeadSourceData {
  lead_source: string
  new_leads: number
  calls: number
  emails: number
  texts: number
  notes: number
  tasks_completed: number
  appointments: number
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
  deals_closed: number
  deal_value: number
  deal_commission: number
  conversion_rate: number
  website_registrations: number
  inquiries: number
  properties_viewed: number
  properties_saved: number
  page_views: number
}

export interface LeadSourceTotals {
  new_leads: number
  calls: number
  emails: number
  texts: number
  notes: number
  tasks_completed: number
  appointments: number
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
  deals_closed: number
  deal_value: number
  deal_commission: number
  conversion_rate: number
  website_registrations: number
  inquiries: number
  properties_viewed: number
  properties_saved: number
  page_views: number
}

export interface LeadSourceTimeSeriesData {
  date: string
  new_leads: number
  calls: number
  emails: number
  texts: number
  appointments: number
  deals_closed: number
  website_registrations: number
  inquiries: number
  properties_viewed: number
  properties_saved: number
  page_views: number
}

export interface LeadSourceResponse {
  period: {
    start: string
    end: string
  }
  lead_sources: LeadSourceData[]
  totals: LeadSourceTotals
  time_series: LeadSourceTimeSeriesData[]
}

export interface LeadSourceRequest {
  start_date?: string
  end_date?: string
  lead_sources?: string[]
  lead_types?: number[]
  user_ids?: number[]
}

export const leadSourceApi = createApi({
  reducerPath: "leadSourceApi",
  baseQuery: customBaseQuery,
  tagTypes: ["LeadSource"],
  endpoints: (builder) => ({
    getLeadSource: builder.query<LeadSourceResponse, LeadSourceRequest>({
      query: (params) => ({
        url: "/reports/lead-source",
        method: "POST",
        body: {
          start_date: params.start_date,
          end_date: params.end_date,
          lead_sources: params.lead_sources,
          lead_types: params.lead_types,
          user_ids: params.user_ids,
        },
      }),
      providesTags: ["LeadSource"],
    }),
    downloadLeadSourceExcel: builder.mutation<void, LeadSourceRequest>({
      query: (params) => ({
        url: "/reports/lead-source/download-excel",
        method: "POST",
        body: {
          start_date: params.start_date,
          end_date: params.end_date,
          lead_sources: params.lead_sources,
          lead_types: params.lead_types,
          user_ids: params.user_ids,
        },
        responseHandler: async (response: Response) => {
          const blob = await response.blob()
          const url = window.URL.createObjectURL(blob)
          const a = document.createElement("a")
          a.href = url
          a.download = `lead-source-${params.start_date}-to-${params.end_date}.xlsx`
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

export const { useGetLeadSourceQuery, useDownloadLeadSourceExcelMutation } = leadSourceApi
