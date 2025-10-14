import { createApi } from "@reduxjs/toolkit/query/react"
import { customBaseQuery } from "../../../app/baseQueryHandler"

export interface EventTypesResponse {
  status: boolean
  message: string
  data: string[]
}

export const eventTypesApi = createApi({
  reducerPath: "eventTypesApi",
  baseQuery: customBaseQuery,
  tagTypes: ["EventTypes"],
  endpoints: (builder) => ({
    getEventTypes: builder.query<EventTypesResponse, void>({
      query: () => ({
        url: "/events/types/available",
        method: "GET",
      }),
      providesTags: ["EventTypes"],
    }),
  }),
})

export const { useGetEventTypesQuery } = eventTypesApi
