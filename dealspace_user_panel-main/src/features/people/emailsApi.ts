import { createApi } from "@reduxjs/toolkit/query/react"
import type { Email, SendEmailRequest, EmailsApiResponse, EmailAccountsApiResponse } from "../../types/emails"
import type { ApiResponse } from "../../types/meta"
import { customBaseQuery } from "../../app/baseQueryHandler"

export const emailsApi = createApi({
  reducerPath: "emailsApi",
  baseQuery: customBaseQuery,
  tagTypes: ["Email", "EmailAccount"],
  endpoints: (builder) => ({
    getEmails: builder.query<EmailsApiResponse, { person_id?: number; page?: number; per_page?: number }>({
      query: ({ person_id, page = 1, per_page = 15 }) => ({
        url: `/emails`,
        params: { person_id, page, per_page },
      }),
      providesTags: ["Email"],
    }),
    getEmailById: builder.query<{ status: boolean; message: string; data: Email }, number>({
      query: (id) => `/emails/${id}`,
      providesTags: (_result, _error, id) => [{ type: "Email", id }],
    }),
    sendEmail: builder.mutation<ApiResponse<Email>, SendEmailRequest>({
      query: (emailData) => ({
        url: "/emails",
        method: "POST",
        body: emailData,
      }),
      invalidatesTags: ["Email"],
    }),
    getActiveEmailAccounts: builder.query<EmailAccountsApiResponse, void>({
      query: () => "/oauth/accounts",
      providesTags: ["EmailAccount"],
    }),
  }),
})

export const { useGetEmailsQuery, useGetEmailByIdQuery, useSendEmailMutation, useGetActiveEmailAccountsQuery } =
  emailsApi
