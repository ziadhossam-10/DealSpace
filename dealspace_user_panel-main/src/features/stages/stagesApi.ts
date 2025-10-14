import { createApi } from "@reduxjs/toolkit/query/react"
import type { ApiResponse } from "../../types/meta"
import { customBaseQuery } from "../../app/baseQueryHandler"
import { CreateStageRequest, Stage, UpdateStageRequest } from "../../types/stages";

export const stagesApi = createApi({
  reducerPath: "stagesApi",
  baseQuery: customBaseQuery,
  tagTypes: ["Stage"],
  endpoints: (builder) => ({
    getStages: builder.query<ApiResponse<Stage[]>, void>({
      query: () => ({
        url: `/people/stages`
      }),
      providesTags: ["Stage"],
    }),

    getStageById: builder.query<ApiResponse<Stage>, number>({
      query: (id) => `/people/stages/${id}`,
      providesTags: (_result, _error, id) => [{ type: "Stage", id }],
    }),

    createStage: builder.mutation<ApiResponse<Stage>, CreateStageRequest>({
      query: (stageData) => ({
        url: "/people/stages",
        method: "POST",
        body: stageData,
      }),
      invalidatesTags: ["Stage"],
    }),

    updateStage: builder.mutation<ApiResponse<Stage>, UpdateStageRequest>({
      query: ({ id, ...stageData }) => ({
        url: `/people/stages/${id}`,
        method: "PUT",
        body: stageData,
      }),
      invalidatesTags: (_result, _error, { id }) => [{ type: "Stage", id }, "Stage"],
    }),

    deleteStage: builder.mutation<ApiResponse<Stage>, number>({
      query: (id) => ({
        url: `/people/stages/${id}`,
        method: "DELETE",
      }),
      invalidatesTags: ["Stage"],
    }),

  }),
})

export const {
  useGetStagesQuery,
  useGetStageByIdQuery,
  useCreateStageMutation,
  useUpdateStageMutation,
  useDeleteStageMutation,
} = stagesApi
