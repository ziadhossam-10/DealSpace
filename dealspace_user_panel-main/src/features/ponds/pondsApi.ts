import { createApi } from "@reduxjs/toolkit/query/react"
import type { CreatePondRequest, UpdatePondRequest, Pond, PondsApiResponse, PondApiResponse } from "../../types/ponds"
import type { ApiResponse } from "../../types/meta"
import { customBaseQuery } from "../../app/baseQueryHandler"

export const pondsApi = createApi({
  reducerPath: "pondsApi",
  baseQuery: customBaseQuery,
  tagTypes: ["Pond"],
  endpoints: (builder) => ({
    getPonds: builder.query<PondsApiResponse, { page: number; per_page: number, search?: string }>({
      query: ({ page, per_page, search }) => ({
        url: `/ponds`,
        params: { page, per_page, search },
      }),
      providesTags: ["Pond"],
    }),

    getPondById: builder.query<PondApiResponse, number>({
      query: (id) => `/ponds/${id}`,
      providesTags: (_result, _error, id) => [{ type: "Pond", id }],
    }),

    createPond: builder.mutation<ApiResponse<Pond>, CreatePondRequest>({
      query: (pondData) => ({
        url: "/ponds",
        method: "POST",
        body: pondData,
      }),
      invalidatesTags: ["Pond"],
    }),

    updatePond: builder.mutation<ApiResponse<Pond>, { id: number } & UpdatePondRequest>({
      query: ({ id, ...pondData }) => ({
        url: `/ponds/${id}`,
        method: "PUT",
        body: pondData,
      }),
      invalidatesTags: (_result, _error, { id }) => [{ type: "Pond", id }, "Pond"],
    }),

    deletePond: builder.mutation<ApiResponse<Pond>, number>({
      query: (id) => ({
        url: `/ponds/${id}`,
        method: "DELETE",
      }),
      invalidatesTags: ["Pond"],
    }),
    bulkDeletePonds: builder.mutation<
      ApiResponse<Pond>,
      { ids?: number[]; exceptionIds?: number[]; isAllSelected: boolean }
    >({
      query: ({ ids, exceptionIds, isAllSelected }) => ({
        url: `/ponds/bulk-delete`,
        method: "DELETE",
        body: {
          ids,
          exception_ids: exceptionIds,
          is_all_selected: isAllSelected,
        },
      }),
      invalidatesTags: ["Pond"],
    }),

  }),
})

export const {
  useGetPondsQuery,
  useGetPondByIdQuery,
  useCreatePondMutation,
  useUpdatePondMutation,
  useDeletePondMutation,
  useBulkDeletePondsMutation,
} = pondsApi
