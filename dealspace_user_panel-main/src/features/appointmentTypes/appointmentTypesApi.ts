import { createApi } from "@reduxjs/toolkit/query/react"
import type { ApiResponse } from "../../types/meta"
import { customBaseQuery } from "../../app/baseQueryHandler"

export interface AppointmentType {
  id: number
  name: string
  description?: string
  sort: number
}

export interface CreateAppointmentTypeRequest {
  name: string
  description?: string
  sort?: number
}

export interface UpdateAppointmentTypeRequest {
  id: number
  name: string
  description?: string
  sort?: number
}

export interface UpdateAppointmentTypeSortRequest {
  id: number
  sort: number
}

export const appointmentTypesApi = createApi({
  reducerPath: "appointmentTypesApi",
  baseQuery: customBaseQuery,
  tagTypes: ["AppointmentType"],
  endpoints: (builder) => ({
    getAppointmentTypes: builder.query<ApiResponse<AppointmentType[]>, void>({
      query: () => ({
        url: `/appointment-types`,
      }),
      providesTags: ["AppointmentType"],
    }),
    getAppointmentTypeById: builder.query<ApiResponse<AppointmentType>, number>({
      query: (id) => `/appointment-types/${id}`,
      providesTags: (_result, _error, id) => [{ type: "AppointmentType", id }],
    }),
    createAppointmentType: builder.mutation<ApiResponse<AppointmentType>, CreateAppointmentTypeRequest>({
      query: (appointmentTypeData) => ({
        url: "/appointment-types",
        method: "POST",
        body: appointmentTypeData,
      }),
      invalidatesTags: ["AppointmentType"],
    }),
    updateAppointmentType: builder.mutation<ApiResponse<AppointmentType>, UpdateAppointmentTypeRequest>({
      query: ({ id, ...appointmentTypeData }) => ({
        url: `/appointment-types/${id}`,
        method: "PUT",
        body: appointmentTypeData,
      }),
      invalidatesTags: (_result, _error, { id }) => [{ type: "AppointmentType", id }, "AppointmentType"],
    }),
    updateAppointmentTypeSort: builder.mutation<ApiResponse<AppointmentType>, UpdateAppointmentTypeSortRequest>({
      query: ({ id, sort }) => ({
        url: `/appointment-types/${id}/sort-order`,
        method: "PUT",
        body: { sort_order: sort },
      }),
      invalidatesTags: ["AppointmentType"],
    }),
    deleteAppointmentType: builder.mutation<ApiResponse<AppointmentType>, number>({
      query: (id) => ({
        url: `/appointment-types/${id}`,
        method: "DELETE",
      }),
      invalidatesTags: ["AppointmentType"],
    }),
  }),
})

export const {
  useGetAppointmentTypesQuery,
  useGetAppointmentTypeByIdQuery,
  useCreateAppointmentTypeMutation,
  useUpdateAppointmentTypeMutation,
  useUpdateAppointmentTypeSortMutation,
  useDeleteAppointmentTypeMutation,
} = appointmentTypesApi
