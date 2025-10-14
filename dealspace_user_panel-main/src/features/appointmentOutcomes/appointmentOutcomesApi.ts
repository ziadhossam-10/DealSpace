import { createApi } from "@reduxjs/toolkit/query/react"
import type { ApiResponse } from "../../types/meta"
import { customBaseQuery } from "../../app/baseQueryHandler"

export interface AppointmentOutcome {
  id: number
  name: string
  description?: string
  sort: number
}

export interface CreateAppointmentOutcomeRequest {
  name: string
  description?: string
  sort?: number
}

export interface UpdateAppointmentOutcomeRequest {
  id: number
  name: string
  description?: string
  sort?: number
}

export interface UpdateAppointmentOutcomeSortRequest {
  id: number
  sort: number
}

export const appointmentOutcomesApi = createApi({
  reducerPath: "appointmentOutcomesApi",
  baseQuery: customBaseQuery,
  tagTypes: ["AppointmentOutcome"],
  endpoints: (builder) => ({
    getAppointmentOutcomes: builder.query<ApiResponse<AppointmentOutcome[]>, void>({
      query: () => ({
        url: `/appointment-outcomes`,
      }),
      providesTags: ["AppointmentOutcome"],
    }),
    getAppointmentOutcomeById: builder.query<ApiResponse<AppointmentOutcome>, number>({
      query: (id) => `/appointment-outcomes/${id}`,
      providesTags: (_result, _error, id) => [{ type: "AppointmentOutcome", id }],
    }),
    createAppointmentOutcome: builder.mutation<ApiResponse<AppointmentOutcome>, CreateAppointmentOutcomeRequest>({
      query: (appointmentOutcomeData) => ({
        url: "/appointment-outcomes",
        method: "POST",
        body: appointmentOutcomeData,
      }),
      invalidatesTags: ["AppointmentOutcome"],
    }),
    updateAppointmentOutcome: builder.mutation<ApiResponse<AppointmentOutcome>, UpdateAppointmentOutcomeRequest>({
      query: ({ id, ...appointmentOutcomeData }) => ({
        url: `/appointment-outcomes/${id}`,
        method: "PUT",
        body: appointmentOutcomeData,
      }),
      invalidatesTags: (_result, _error, { id }) => [{ type: "AppointmentOutcome", id }, "AppointmentOutcome"],
    }),
    updateAppointmentOutcomeSort: builder.mutation<ApiResponse<AppointmentOutcome>, UpdateAppointmentOutcomeSortRequest>({
      query: ({ id, sort }) => ({
        url: `/appointment-outcomes/${id}/sort-order`,
        method: "PUT",
        body: { sort_order: sort },
      }),
      invalidatesTags: ["AppointmentOutcome"],
    }),
    deleteAppointmentOutcome: builder.mutation<ApiResponse<AppointmentOutcome>, number>({
      query: (id) => ({
        url: `/appointment-outcomes/${id}`,
        method: "DELETE",
      }),
      invalidatesTags: ["AppointmentOutcome"],
    }),
  }),
})

export const {
  useGetAppointmentOutcomesQuery,
  useGetAppointmentOutcomeByIdQuery,
  useCreateAppointmentOutcomeMutation,
  useUpdateAppointmentOutcomeMutation,
  useUpdateAppointmentOutcomeSortMutation,
  useDeleteAppointmentOutcomeMutation,
} = appointmentOutcomesApi
