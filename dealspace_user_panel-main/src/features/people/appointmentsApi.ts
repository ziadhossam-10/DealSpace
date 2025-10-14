import { createApi, fetchBaseQuery } from "@reduxjs/toolkit/query/react"
import { customBaseQuery } from "../../app/baseQueryHandler"

// Types

interface AppointmentType {
  id: number
  name: string
  description: string
  sort: number
}

interface AppointmentOutcome {
  id: number
  name: string
  description: string
  sort: number
}

interface User {
  id: number
  name: string
  email: string
  avatar: string | null
  role: number
  role_name: string
}

export interface Appointment {
  id: number
  title: string
  description: string
  invitees: null // Backend returns null for this field
  all_day: boolean
  start: string
  end: string
  location: string
  created_by_id: number
  type_id: number
  outcome_id: number
  formatted_start: string
  formatted_end: string
  formatted_date_range: string
  status: string
  is_today: boolean
  is_tomorrow: boolean
  is_past: boolean
  is_upcoming: boolean
  is_current: boolean
  is_this_week: boolean
  is_next_week: boolean
  is_this_month: boolean
  duration_minutes: number
  duration_hours: number
  user_invitees: Array<{
    id: number
    name: string
    email: string
    response_status: string
    responded_at: string | null
  }>
  person_invitees: Array<{
    id: number
    name: string
    email: string | null
    response_status: string
    responded_at: string | null
  }>
  invitee_names: string[]
  created_at: string
  updated_at: string
  deleted_at: string | null
  created_by: User
  type: AppointmentType
  outcome: AppointmentOutcome
  check_conflicts: boolean
}

interface CreateAppointmentRequest {
  title: string
  description: string
  start: string
  end: string
  all_day: boolean
  location: string
  type_id: number | null
  outcome_id: number | null
  user_ids: number[]
  person_ids: number[]
  check_conflicts: boolean
}

interface UpdateAppointmentRequest extends Partial<CreateAppointmentRequest> {
  id: number
  user_ids_to_delete?: number[]
  person_ids_to_delete?: number[]
}

interface AppointmentsResponse {
  status: boolean
  message: string
  data: {
    items: Appointment[]
    meta: {
      current_page: number
      per_page: number
      total: number
      last_page: number
    }
  }
}

interface AppointmentResponse {
  status: boolean
  message: string
  data: Appointment
}


export const appointmentsApi = createApi({
  reducerPath: "appointmentsApi",
  baseQuery: customBaseQuery,
  tagTypes: ["Appointment"],
  endpoints: (builder) => ({
    // Get all appointments
    getAppointments: builder.query<AppointmentsResponse, { page?: number; per_page?: number; person_id?: number }>({
      query: ({ page = 1, per_page = 15, person_id }) => {
        const params = new URLSearchParams({
          page: page.toString(),
          per_page: per_page.toString(),
        })
        if (person_id) {
          params.append("person_id", person_id.toString())
        }
        return `appointments?${params.toString()}`
      },
      providesTags: ["Appointment"],
    }),

    // Get single appointment
    getAppointment: builder.query<AppointmentResponse, number>({
      query: (id) => `appointments/${id}`,
      providesTags: (result, error, id) => [{ type: "Appointment", id }],
    }),

    // Create appointment
    createAppointment: builder.mutation<AppointmentResponse, CreateAppointmentRequest>({
      query: (data) => ({
        url: "appointments",
        method: "POST",
        body: data,
      }),
      invalidatesTags: ["Appointment"],
    }),

    // Update appointment
    updateAppointment: builder.mutation<AppointmentResponse, UpdateAppointmentRequest>({
      query: ({ id, ...data }) => ({
        url: `appointments/${id}`,
        method: "PUT",
        body: data,
      }),
      invalidatesTags: (result, error, { id }) => [{ type: "Appointment", id }, "Appointment"],
    }),

    // Delete appointment
    deleteAppointment: builder.mutation<{ status: boolean; message: string }, number>({
      query: (id) => ({
        url: `appointments/${id}`,
        method: "DELETE",
      }),
      invalidatesTags: (result, error, id) => [{ type: "Appointment", id }, "Appointment"],
    }),
  }),
})

export const {
  useGetAppointmentsQuery,
  useGetAppointmentQuery,
  useCreateAppointmentMutation,
  useUpdateAppointmentMutation,
  useDeleteAppointmentMutation,
} = appointmentsApi
