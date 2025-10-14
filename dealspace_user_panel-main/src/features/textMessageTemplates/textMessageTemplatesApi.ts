import { createApi } from "@reduxjs/toolkit/query/react"
import type {
  CreateTextMessageTemplateRequest,
  UpdateTextMessageTemplateRequest,
  TextMessageTemplate,
  TextMessageTemplatesApiResponse,
  TextMessageTemplateApiResponse,
} from "../../types/textMessageTemplates"
import type { ApiResponse } from "../../types/meta"
import { customBaseQuery } from "../../app/baseQueryHandler"

export const textMessageTemplatesApi = createApi({
  reducerPath: "textMessageTemplatesApi",
  baseQuery: customBaseQuery,
  tagTypes: ["TextMessageTemplate"],
  endpoints: (builder) => ({
    getTextMessageTemplates: builder.query<
      TextMessageTemplatesApiResponse,
      { page: number; per_page: number; search?: string }
    >({
      query: ({ page, per_page, search }) => ({
        url: `/text-message-templates`,
        params: { page, per_page, search },
      }),
      providesTags: ["TextMessageTemplate"],
    }),
    getTextMessageTemplateById: builder.query<TextMessageTemplateApiResponse, number>({
      query: (id) => `/text-message-templates/${id}`,
      providesTags: (_result, _error, id) => [{ type: "TextMessageTemplate", id }],
    }),
    createTextMessageTemplate: builder.mutation<ApiResponse<TextMessageTemplate>, CreateTextMessageTemplateRequest>({
      query: (templateData) => ({
        url: "/text-message-templates",
        method: "POST",
        body: templateData,
      }),
      invalidatesTags: ["TextMessageTemplate"],
    }),
    updateTextMessageTemplate: builder.mutation<
      ApiResponse<TextMessageTemplate>,
      { id: number } & UpdateTextMessageTemplateRequest
    >({
      query: ({ id, ...templateData }) => ({
        url: `/text-message-templates/${id}`,
        method: "PUT",
        body: templateData,
      }),
      invalidatesTags: (_result, _error, { id }) => [{ type: "TextMessageTemplate", id }, "TextMessageTemplate"],
    }),
    deleteTextMessageTemplate: builder.mutation<ApiResponse<TextMessageTemplate>, number>({
      query: (id) => ({
        url: `/text-message-templates/${id}`,
        method: "DELETE",
      }),
      invalidatesTags: ["TextMessageTemplate"],
    }),
    bulkDeleteTextMessageTemplates: builder.mutation<
      ApiResponse<TextMessageTemplate>,
      { ids?: number[]; exceptionIds?: number[]; isAllSelected: boolean }
    >({
      query: ({ ids, exceptionIds, isAllSelected }) => ({
        url: `/text-message-templates/bulk-delete`,
        method: "DELETE",
        body: {
          ids,
          exception_ids: exceptionIds,
          is_all_selected: isAllSelected,
        },
      }),
      invalidatesTags: ["TextMessageTemplate"],
    }),
  }),
})

export const {
  useGetTextMessageTemplatesQuery,
  useGetTextMessageTemplateByIdQuery,
  useCreateTextMessageTemplateMutation,
  useUpdateTextMessageTemplateMutation,
  useDeleteTextMessageTemplateMutation,
  useBulkDeleteTextMessageTemplatesMutation,
} = textMessageTemplatesApi
