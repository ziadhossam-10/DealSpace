import { createApi } from "@reduxjs/toolkit/query/react"
import type { ActivitiesApiResponse } from "../../types/activities"
import { customBaseQuery } from "../../app/baseQueryHandler"

export const activitiesApi = createApi({
  reducerPath: "activitiesApi",
  baseQuery: customBaseQuery,
  tagTypes: ["Activity"],
  endpoints: (builder) => ({
    getActivities: builder.query<ActivitiesApiResponse, { person_id: number; page?: number; per_page?: number }>({
      query: ({ person_id, page = 1, per_page = 15 }) => ({
        url: `/activities`,
        params: { person_id, page, per_page },
      }),
      providesTags: ["Activity"],
    }),
  }),
})

export const { useGetActivitiesQuery } = activitiesApi
