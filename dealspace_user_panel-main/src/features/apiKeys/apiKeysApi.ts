import { createApi } from "@reduxjs/toolkit/query/react"
import type {
  CreateApiKeyRequest,
  UpdateApiKeyRequest,
  ApiKey,
  ApiKeysApiResponse,
  ApiKeyApiResponse,
} from "../../types/apiKeys"
import type { ApiResponse } from "../../types/meta"
import { customBaseQuery } from "../../app/baseQueryHandler"

export const apiKeysApi = createApi({
  reducerPath: "apiKeysApi",
  baseQuery: customBaseQuery,
  tagTypes: ["ApiKey"],
  endpoints: (builder) => ({
    getApiKeys: builder.query<ApiKeysApiResponse, { page: number; per_page: number; search?: string }>({
      query: ({ page, per_page, search }) => ({
        url: `/api-keys`,
        params: { page, per_page, search },
      }),
      providesTags: ["ApiKey"],
    }),
    getApiKeyById: builder.query<ApiKeyApiResponse, number>({
      query: (id) => `/api-keys/${id}`,
      providesTags: (_result, _error, id) => [{ type: "ApiKey", id }],
    }),
    createApiKey: builder.mutation<ApiResponse<ApiKey>, CreateApiKeyRequest>({
      query: (apiKeyData) => ({
        url: "/api-keys",
        method: "POST",
        body: apiKeyData,
      }),
      invalidatesTags: ["ApiKey"],
    }),
    updateApiKey: builder.mutation<ApiResponse<ApiKey>, { id: number } & UpdateApiKeyRequest>({
      query: ({ id, ...apiKeyData }) => ({
        url: `/api-keys/${id}`,
        method: "PUT",
        body: apiKeyData,
      }),
      invalidatesTags: (_result, _error, { id }) => [{ type: "ApiKey", id }, "ApiKey"],
    }),
    deleteApiKey: builder.mutation<ApiResponse<ApiKey>, number>({
      query: (id) => ({
        url: `/api-keys/${id}`,
        method: "DELETE",
      }),
      invalidatesTags: ["ApiKey"],
    }),
    revokeApiKey: builder.mutation<ApiResponse<ApiKey>, number>({
      query: (id) => ({
        url: `/api-keys/${id}/revoke`,
        method: "POST",
      }),
      invalidatesTags: (_result, _error, id) => [{ type: "ApiKey", id }, "ApiKey"],
    }),
    activateApiKey: builder.mutation<ApiResponse<ApiKey>, number>({
      query: (id) => ({
        url: `/api-keys/${id}/activate`,
        method: "POST",
      }),
      invalidatesTags: (_result, _error, id) => [{ type: "ApiKey", id }, "ApiKey"],
    }),
    regenerateApiKey: builder.mutation<ApiResponse<ApiKey>, number>({
      query: (id) => ({
        url: `/api-keys/${id}/regenerate`,
        method: "POST",
      }),
      invalidatesTags: (_result, _error, id) => [{ type: "ApiKey", id }, "ApiKey"],
    }),
  }),
})

export const {
  useGetApiKeysQuery,
  useGetApiKeyByIdQuery,
  useCreateApiKeyMutation,
  useUpdateApiKeyMutation,
  useDeleteApiKeyMutation,
  useRevokeApiKeyMutation,
  useActivateApiKeyMutation,
  useRegenerateApiKeyMutation,
} = apiKeysApi
