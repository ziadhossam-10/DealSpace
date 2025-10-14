import { createApi } from "@reduxjs/toolkit/query/react"
import type {
  CreateEmailTemplateRequest,
  UpdateEmailTemplateRequest,
  EmailTemplate,
  EmailTemplatesApiResponse,
  EmailTemplateApiResponse,
}  from "../../types/emailTemplates"
import type { ApiResponse } from "../../types/meta"
import { customBaseQuery } from "../../app/baseQueryHandler"

export const emailTemplatesApi = createApi({
  reducerPath: "emailTemplatesApi",
  baseQuery: customBaseQuery,
  tagTypes: ["EmailTemplate"],
  endpoints: (builder) => ({
    getEmailTemplates: builder.query<EmailTemplatesApiResponse, { page: number; per_page: number; search?: string }>({
      query: ({ page, per_page, search }) => ({
        url: `/email-templates`,
        params: { page, per_page, search },
      }),
      providesTags: ["EmailTemplate"],
    }),
    getEmailTemplateById: builder.query<EmailTemplateApiResponse, number>({
      query: (id) => `/email-templates/${id}`,
      providesTags: (_result, _error, id) => [{ type: "EmailTemplate", id }],
    }),
    createEmailTemplate: builder.mutation<ApiResponse<EmailTemplate>, CreateEmailTemplateRequest>({
      query: (templateData) => ({
        url: "/email-templates",
        method: "POST",
        body: templateData,
      }),
      invalidatesTags: ["EmailTemplate"],
    }),
    updateEmailTemplate: builder.mutation<ApiResponse<EmailTemplate>, { id: number } & UpdateEmailTemplateRequest>({
      query: ({ id, ...templateData }) => ({
        url: `/email-templates/${id}`,
        method: "PUT",
        body: templateData,
      }),
      invalidatesTags: (_result, _error, { id }) => [{ type: "EmailTemplate", id }, "EmailTemplate"],
    }),
    deleteEmailTemplate: builder.mutation<ApiResponse<EmailTemplate>, number>({
      query: (id) => ({
        url: `/email-templates/${id}`,
        method: "DELETE",
      }),
      invalidatesTags: ["EmailTemplate"],
    }),
    bulkDeleteEmailTemplates: builder.mutation<
      ApiResponse<EmailTemplate>,
      { ids?: number[]; exceptionIds?: number[]; isAllSelected: boolean }
    >({
      query: ({ ids, exceptionIds, isAllSelected }) => ({
        url: `/email-templates/bulk-delete`,
        method: "DELETE",
        body: {
          ids,
          exception_ids: exceptionIds,
          is_all_selected: isAllSelected,
        },
      }),
      invalidatesTags: ["EmailTemplate"],
    }),
  }),
})

export const {
  useGetEmailTemplatesQuery,
  useGetEmailTemplateByIdQuery,
  useCreateEmailTemplateMutation,
  useUpdateEmailTemplateMutation,
  useDeleteEmailTemplateMutation,
  useBulkDeleteEmailTemplatesMutation,
} = emailTemplatesApi
