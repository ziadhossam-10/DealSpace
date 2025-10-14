import { createApi } from "@reduxjs/toolkit/query/react"
import type { ApiResponse } from "../../types/meta"
import { customBaseQuery } from "../../app/baseQueryHandler"

export interface DealType {
  id: number
  name: string
  sort: number
}

export interface CreateDealTypeRequest {
  name: string
  sort?: number
}

export interface UpdateDealTypeRequest {
  id: number
  name: string
  sort?: number
}

export interface UpdateDealTypeSortRequest {
  id: number
  sort: number
}

export const dealTypesApi = createApi({
  reducerPath: "dealTypesApi",
  baseQuery: customBaseQuery,
  tagTypes: ["DealType"],
  endpoints: (builder) => ({
    getDealTypes: builder.query<ApiResponse<DealType[]>, void>({
      query: () => ({
        url: `/deal-types`,
      }),
      providesTags: ["DealType"],
    }),
    getDealTypeById: builder.query<ApiResponse<DealType>, number>({
      query: (id) => `/deal-types/${id}`,
      providesTags: (_result, _error, id) => [{ type: "DealType", id }],
    }),
    createDealType: builder.mutation<ApiResponse<DealType>, CreateDealTypeRequest>({
      query: (dealTypeData) => ({
        url: "/deal-types",
        method: "POST",
        body: dealTypeData,
      }),
      invalidatesTags: ["DealType"],
    }),
    updateDealType: builder.mutation<ApiResponse<DealType>, UpdateDealTypeRequest>({
      query: ({ id, ...dealTypeData }) => ({
        url: `/deal-types/${id}`,
        method: "PUT",
        body: dealTypeData,
      }),
      invalidatesTags: (_result, _error, { id }) => [{ type: "DealType", id }, "DealType"],
    }),
    updateDealTypeSort: builder.mutation<ApiResponse<DealType>, UpdateDealTypeSortRequest>({
      query: ({ id, sort }) => ({
        url: `/deal-types/${id}/sort-order`,
        method: "PUT",
        body: { sort_order: sort },
      }),
      invalidatesTags: ["DealType"],
    }),
    deleteDealType: builder.mutation<ApiResponse<DealType>, number>({
      query: (id) => ({
        url: `/deal-types/${id}`,
        method: "DELETE",
      }),
      invalidatesTags: ["DealType"],
    }),
  }),
})

export const {
  useGetDealTypesQuery,
  useGetDealTypeByIdQuery,
  useCreateDealTypeMutation,
  useUpdateDealTypeMutation,
  useUpdateDealTypeSortMutation,
  useDeleteDealTypeMutation,
} = dealTypesApi
