import { createApi } from "@reduxjs/toolkit/query/react"
import { customBaseQuery } from "../../../app/baseQueryHandler"

export interface AppointmentAgent {
  agent_id: number
  agent_name: string
  agent_email: string
  agent_avatar: string | null
  total_appointments: number
  attended_appointments: number
  no_show_appointments: number
  rescheduled_appointments: number
  canceled_appointments: number
  attendance_rate: number
  conversion_rate: number
  total_appointment_value: number
  average_appointment_value: number
  upcoming_appointments: number
  overdue_appointments: number
  appointment_types: Record<string, number>
  outcomes: Record<string, number>
  rank: number
}

export interface AppointmentsSummary {
  total_agents: number
  active_agents: number
  total_appointments: number
  attended_appointments: number
  no_show_appointments: number
  rescheduled_appointments: number
  canceled_appointments: number
  overall_attendance_rate: number
  overall_conversion_rate: number
  total_appointment_value: number
  average_appointment_value: number
  upcoming_summary: {
    total_appointments: number
    total_value: number
    average_value: number
  }
  overdue_summary: {
    total_appointments: number
    total_value: number
    percentage_of_total: number
  }
  team_performance: {
    avg_appointments_per_agent: number
    avg_attendance_rate: number
    avg_conversion_rate: number
    avg_value_per_agent: number
  }
  appointment_types_breakdown: Record<string, number>
  outcomes_breakdown: Record<string, number>
}

export interface AppointmentsResponse {
  timeframe: {
    type: string
    start_date: string
    end_date: string
    display_name: string
  }
  filters: {
    appointment_type_id: number | null
    outcome_id: number | null
    team_id: number | null
    status: string | null
    excluded_users: number
    limit: string
  }
  team_info: any
  appointments: AppointmentAgent[]
  summary: AppointmentsSummary
  last_updated: string
}

export interface AppointmentsOptionsResponse {
  teams: Array<{ id: number; name: string; email: string }>
  agents: Array<{ id: number; name: string; email: string }>
  appointment_types: Array<{ id: number; name: string }>
  outcomes: Array<{ id: number; name: string }>
  status_options: Array<{ value: string; label: string }>
}

export interface AppointmentsRequest {
  timeframe?: string
  limit?: number
  appointment_type_id?: number
  outcome_id?: number
  team_id?: number
  status?: string
}

export const appointmentReportApi = createApi({
  reducerPath: "appointmentReportApi",
  baseQuery: customBaseQuery,
  tagTypes: ["Appointments"],
  endpoints: (builder) => ({
    getAppointments: builder.query<AppointmentsResponse, AppointmentsRequest>({
      query: (params) => {
        const searchParams = new URLSearchParams()

        if (params.timeframe) {
          searchParams.append("timeframe", params.timeframe)
        }
        if (params.limit) {
          searchParams.append("limit", params.limit.toString())
        }
        if (params.appointment_type_id) {
          searchParams.append("appointment_type_id", params.appointment_type_id.toString())
        }
        if (params.outcome_id) {
          searchParams.append("outcome_id", params.outcome_id.toString())
        }
        if (params.team_id) {
          searchParams.append("team_id", params.team_id.toString())
        }
        if (params.status) {
          searchParams.append("status", params.status)
        }

        return {
          url: `/reports/appointments?${searchParams.toString()}`,
          method: "GET",
        }
      },
      providesTags: ["Appointments"],
    }),
    getAppointmentsOptions: builder.query<AppointmentsOptionsResponse, void>({
      query: () => ({
        url: "/reports/appointments/options",
        method: "GET",
      }),
    }),
    exportAppointments: builder.mutation<void, AppointmentsRequest>({
      query: (params) => {
        const searchParams = new URLSearchParams()

        if (params.timeframe) {
          searchParams.append("timeframe", params.timeframe)
        }
        if (params.limit) {
          searchParams.append("limit", params.limit.toString())
        }
        if (params.appointment_type_id) {
          searchParams.append("appointment_type_id", params.appointment_type_id.toString())
        }
        if (params.outcome_id) {
          searchParams.append("outcome_id", params.outcome_id.toString())
        }
        if (params.team_id) {
          searchParams.append("team_id", params.team_id.toString())
        }
        if (params.status) {
          searchParams.append("status", params.status)
        }

        return {
          url: `/reports/appointments/export?${searchParams.toString()}`,
          method: "GET",
          responseHandler: async (response: Response) => {
            const blob = await response.blob()
            const url = window.URL.createObjectURL(blob)
            const a = document.createElement("a")
            a.href = url
            a.download = `appointments-report-${new Date().toISOString().split("T")[0]}.xlsx`
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

export const { useGetAppointmentsQuery, useGetAppointmentsOptionsQuery, useExportAppointmentsMutation } =
  appointmentReportApi
