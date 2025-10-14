import { createApi } from "@reduxjs/toolkit/query/react"
import { customBaseQuery } from "../../app/baseQueryHandler";
import { ApiResponse, PaginatedResponse } from "../../types/meta";
import { EmailAccount } from "../../types/emailAccounts";
export const emailAccountsApi = createApi({
  reducerPath: "emailAccountsApi",
  baseQuery: customBaseQuery,
  tagTypes: ["EmailAccount"],
  endpoints: (builder) => ({
    hasActiveAccounts: builder.query<ApiResponse<{has_active_accounts: boolean}>, void>({
      query: () => "/email-accounts/has-active",
      providesTags: ["EmailAccount"],
    }),

    getEmailAccounts: builder.query<PaginatedResponse<EmailAccount>, { page?: number; per_page?: number }>({
      query: ({ page = 1, per_page = 15 }) => ({
        url: "/email-accounts",
        params: { page, per_page },
      }),
      providesTags: ["EmailAccount"],
    }),

    deleteEmailAccount: builder.mutation<ApiResponse<any>, number>({
      query: (id) => ({
        url: `/email-accounts/${id}`,
        method: "DELETE",
      }),
      invalidatesTags: ["EmailAccount"],
    }),
    disconnectEmailAccount: builder.mutation<ApiResponse<any>, number>({
      query: (id) => ({
        url: `/oauth/accounts/disconnect/${id}`,
        method: "POST",
      }),
      invalidatesTags: ["EmailAccount"],
    }),
    connectEmailAccount: builder.mutation<ApiResponse<any>, number>({
      query: (id) => ({
        url: `/oauth/accounts/connect/${id}`,
        method: "POST",
      }),
      invalidatesTags: ["EmailAccount"],
    }),

  }),
})

export const { useHasActiveAccountsQuery, useGetEmailAccountsQuery, useDeleteEmailAccountMutation, useConnectEmailAccountMutation, useDisconnectEmailAccountMutation } = emailAccountsApi
