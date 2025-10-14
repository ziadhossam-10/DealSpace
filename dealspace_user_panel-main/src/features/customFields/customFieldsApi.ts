import { createApi } from "@reduxjs/toolkit/query/react"
import type { ApiResponse } from "../../types/meta"
import { customBaseQuery } from "../../app/baseQueryHandler"

export interface CustomField {
  id: number
  label: string
  type: number
  options?: string[]
  created_at?: string
  updated_at?: string
}

export interface CreateCustomFieldRequest {
  label: string
  type: number
  options?: string[]
}

export interface UpdateCustomFieldRequest {
  id: number
  label: string
  type: number
  options?: string[]
}

export const customFieldsApi = createApi({
  reducerPath: "customFieldsApi",
  baseQuery: customBaseQuery,
  tagTypes: ["CustomField"],
  endpoints: (builder) => ({
    getCustomFields: builder.query<ApiResponse<CustomField[]>, void>({
      query: () => ({
        url: `/people/custom-fields`,
      }),
      providesTags: ["CustomField"],
    }),

    getCustomFieldById: builder.query<ApiResponse<CustomField>, number>({
      query: (id) => `/people/custom-fields/${id}`,
      providesTags: (_result, _error, id) => [{ type: "CustomField", id }],
    }),

    createCustomField: builder.mutation<ApiResponse<CustomField>, CreateCustomFieldRequest>({
      query: (customFieldData) => ({
        url: "/people/custom-fields",
        method: "POST",
        body: customFieldData,
      }),
      invalidatesTags: ["CustomField"],
    }),

    updateCustomField: builder.mutation<ApiResponse<CustomField>, UpdateCustomFieldRequest>({
      query: ({ id, ...customFieldData }) => ({
        url: `/people/custom-fields/${id}`,
        method: "PUT",
        body: customFieldData,
      }),
      invalidatesTags: (_result, _error, { id }) => [{ type: "CustomField", id }, "CustomField"],
    }),

    deleteCustomField: builder.mutation<ApiResponse<CustomField>, number>({
      query: (id) => ({
        url: `/people/custom-fields/${id}`,
        method: "DELETE",
      }),
      invalidatesTags: ["CustomField"],
    }),

    setPersonCustomFieldValues: builder.mutation<
      ApiResponse<any>,
      { personId: number; custom_fields: Array<{ id: number; value: string }> }
    >({
      query: ({ personId, custom_fields }) => ({
        url: `/people/${personId}/custom-fields/set-value`,
        method: "POST",
        body: { custom_fields },
      }),
      invalidatesTags: ["CustomField"],
    }),
  }),
})

export const {
  useGetCustomFieldsQuery,
  useGetCustomFieldByIdQuery,
  useCreateCustomFieldMutation,
  useUpdateCustomFieldMutation,
  useDeleteCustomFieldMutation,
  useSetPersonCustomFieldValuesMutation,
} = customFieldsApi
