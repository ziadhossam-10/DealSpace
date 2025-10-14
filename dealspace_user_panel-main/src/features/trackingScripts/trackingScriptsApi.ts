import { createApi } from "@reduxjs/toolkit/query/react"
import type {
  CreateTrackingScriptRequest,
  UpdateTrackingScriptRequest,
  TrackingScript,
  TrackingScriptsApiResponse,
  TrackingScriptApiResponse,
  TrackingCodeResponse,
} from "../../types/trackingScripts"
import type { ApiResponse } from "../../types/meta"
import { customBaseQuery } from "../../app/baseQueryHandler"

export const trackingScriptsApi = createApi({
  reducerPath: "trackingScriptsApi",
  baseQuery: customBaseQuery,
  tagTypes: ["TrackingScript"],
  endpoints: (builder) => ({
    getTrackingScripts: builder.query<TrackingScriptsApiResponse, { page: number; per_page: number; search?: string }>({
      query: ({ page, per_page, search }) => ({
        url: `/tracking-scripts`,
        params: { page, per_page, search },
      }),
      providesTags: ["TrackingScript"],
    }),
    getTrackingScriptById: builder.query<TrackingScriptApiResponse, number>({
      query: (id) => `/tracking-scripts/${id}`,
      providesTags: (_result, _error, id) => [{ type: "TrackingScript", id }],
    }),
    createTrackingScript: builder.mutation<ApiResponse<TrackingScript>, CreateTrackingScriptRequest>({
      query: (scriptData) => ({
        url: "/tracking-scripts",
        method: "POST",
        body: scriptData,
      }),
      invalidatesTags: ["TrackingScript"],
    }),
    updateTrackingScript: builder.mutation<ApiResponse<TrackingScript>, { id: number } & UpdateTrackingScriptRequest>({
      query: ({ id, ...scriptData }) => ({
        url: `/tracking-scripts/${id}`,
        method: "PUT",
        body: scriptData,
      }),
      invalidatesTags: (_result, _error, { id }) => [{ type: "TrackingScript", id }, "TrackingScript"],
    }),
    deleteTrackingScript: builder.mutation<ApiResponse<TrackingScript>, number>({
      query: (id) => ({
        url: `/tracking-scripts/${id}`,
        method: "DELETE",
      }),
      invalidatesTags: ["TrackingScript"],
    }),
    toggleTrackingScriptStatus: builder.mutation<ApiResponse<TrackingScript>, number>({
      query: (id) => ({
        url: `/tracking-scripts/${id}/toggle-status`,
        method: "POST",
      }),
      invalidatesTags: (_result, _error, id) => [{ type: "TrackingScript", id }, "TrackingScript"],
    }),
    regenerateScriptKey: builder.mutation<ApiResponse<TrackingScript>, number>({
      query: (id) => ({
        url: `/tracking-scripts/${id}/regenerate-key`,
        method: "POST",
      }),
      invalidatesTags: (_result, _error, id) => [{ type: "TrackingScript", id }, "TrackingScript"],
    }),
    getTrackingCode: builder.query<TrackingCodeResponse, number>({
      query: (id) => `/tracking-scripts/${id}/tracking-code`,
      providesTags: (_result, _error, id) => [{ type: "TrackingScript", id }],
    }),
  }),
})

export const {
  useGetTrackingScriptsQuery,
  useGetTrackingScriptByIdQuery,
  useCreateTrackingScriptMutation,
  useUpdateTrackingScriptMutation,
  useDeleteTrackingScriptMutation,
  useToggleTrackingScriptStatusMutation,
  useRegenerateScriptKeyMutation,
  useGetTrackingCodeQuery,
} = trackingScriptsApi
