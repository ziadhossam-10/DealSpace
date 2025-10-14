import { createApi } from "@reduxjs/toolkit/query/react"
import type {
  Deal,
  DealStage,
  CreateDealRequest,
  UpdateDealRequest,
  DealsApiResponse,
  DealTypesApiResponse,
  DealStagesApiResponse,
  ApiResponse,
  User,
  Person,
} from "../../types/deals"
import { customBaseQuery } from "../../app/baseQueryHandler"

// Helper function to create FormData for file uploads
// Fixed Helper function to create FormData for file uploads
const createFormDataWithFiles = (data: any): FormData => {
  const formData = new FormData()

  Object.keys(data).forEach((key) => {
    if (key === "attachments" && data[key]) {
      // Handle file attachments
      data[key].forEach((file: File, index: number) => {
        formData.append(`attachments[${index}]`, file)
      })
    } else if (key === "attachments_to_delete" && data[key]) {
      // Handle attachments to delete
      data[key].forEach((id: number, index: number) => {
        formData.append(`attachments_to_delete[${index}]`, id.toString())
      })
    } else if (key === "users_ids" && data[key] && Array.isArray(data[key]) && data[key].length > 0) {
      // Handle user IDs array - FIXED: now matches the actual field name
      data[key].forEach((id: number, index: number) => {
        formData.append(`users_ids[${index}]`, id.toString())
      })
    } else if (key === "people_ids" && data[key] && Array.isArray(data[key]) && data[key].length > 0) {
      // Handle person IDs array - FIXED: now matches the actual field name
      data[key].forEach((id: number, index: number) => {
        formData.append(`people_ids[${index}]`, id.toString())
      })
    } else if (data[key] !== undefined && data[key] !== null) {
      // Handle regular fields
      formData.append(key, data[key].toString())
    }
  })

  return formData
}

export const dealsApi = createApi({
  reducerPath: "dealsApi",
  baseQuery: customBaseQuery,
  tagTypes: ["Deal", "DealType", "DealStage", "User", "Person"],
  endpoints: (builder) => ({
    // Get deal types (buyers/sellers)
    getDealTypes: builder.query<DealTypesApiResponse, void>({
      query: () => "/deal-types",
      providesTags: ["DealType"],
    }),

    // Get stages for a specific type
    getDealStages: builder.query<DealStagesApiResponse, number>({
      query: (typeId) => `/deal-stages?type_id=${typeId}`,
      providesTags: (_result, _error, typeId) => [{ type: "DealStage", id: typeId }],
    }),

    // Create deal stage
    createDealStage: builder.mutation<ApiResponse<DealStage>, { name: string; type_id: number; color: string }>({
      query: (stageData) => ({
        url: "/deal-stages",
        method: "POST",
        body: stageData,
      }),
      invalidatesTags: ["DealStage"],
    }),

    // Update deal stage
    updateDealStage: builder.mutation<ApiResponse<DealStage>, { id: number; name?: string; color?: string }>({
      query: ({ id, ...stageData }) => ({
        url: `/deal-stages/${id}`,
        method: "PUT",
        body: stageData,
      }),
      invalidatesTags: (_result, _error, { id }) => [{ type: "DealStage", id }, "DealStage"],
    }),

    // Delete deal stage
    deleteDealStage: builder.mutation<ApiResponse<any>, number>({
      query: (id) => ({
        url: `/deal-stages/${id}`,
        method: "DELETE",
      }),
      invalidatesTags: ["DealStage"],
    }),

    // Update deal stage sort order
    updateDealTypeSortMutation: builder.mutation<ApiResponse<any>, { id: number; sort: number }>({
      query: ({ id, sort }) => ({
        url: `/deal-types/${id}/sort-order?sort_order=${sort}`,
        method: "PUT",
      }),
      invalidatesTags: ["DealType"],
    }),

    // Get deals
    getDeals: builder.query<
      DealsApiResponse,
      { page?: number; per_page?: number; search?: string; stage_id?: number; type_id?: number; person_id?: number }
    >({
      query: ({ page = 1, per_page = 10, search, stage_id, type_id, person_id }) => ({
        url: "/deals",
        params: { page, per_page, search, stage_id, type_id, person_id },
      }),
      providesTags: ["Deal"],
    }),

    // Get deal by ID
    getDealById: builder.query<ApiResponse<Deal>, number>({
      query: (id) => `/deals/${id}`,
      providesTags: (_result, _error, id) => [{ type: "Deal", id }],
    }),

    // Create deal - UPDATED to handle file attachments
    createDeal: builder.mutation<ApiResponse<Deal>, CreateDealRequest>({
      query: (dealData) => {
        // Check if there are attachments to handle as FormData
        const hasFiles = dealData.attachments && dealData.attachments.length > 0

        if (hasFiles) {
          return {
            url: "/deals",
            method: "POST",
            body: createFormDataWithFiles(dealData),
            // Don't set Content-Type header, let the browser set it for FormData
            headers: {},
          }
        } else {
          // No files, send as regular JSON
          const { attachments, ...jsonData } = dealData
          return {
            url: "/deals",
            method: "POST",
            body: jsonData,
          }
        }
      },
      invalidatesTags: ["Deal"],
    }),

    // Update deal - UPDATED to handle file attachments and deletions
    updateDeal: builder.mutation<ApiResponse<Deal>, { id: number } & UpdateDealRequest>({
      query: ({ id, ...dealData }) => {
        // Check if there are attachments or attachments to delete
        const hasFiles = dealData.attachments && dealData.attachments.length > 0
        const hasAttachmentsToDelete = dealData.attachments_to_delete && dealData.attachments_to_delete.length > 0

        if (hasFiles || hasAttachmentsToDelete) {
          return {
            url: `/deals/${id}`,
            method: "PUT",
            body: createFormDataWithFiles(dealData),
            // Don't set Content-Type header, let the browser set it for FormData
            headers: {},
          }
        } else {
          // No files, send as regular JSON
          const { attachments, attachments_to_delete, ...jsonData } = dealData
          return {
            url: `/deals/${id}`,
            method: "PUT",
            body: jsonData,
          }
        }
      },
      invalidatesTags: (_result, _error, { id }) => [{ type: "Deal", id }, "Deal"],
    }),

    // Delete deal
    deleteDeal: builder.mutation<ApiResponse<Deal>, number>({
      query: (id) => ({
        url: `/deals/${id}`,
        method: "DELETE",
      }),
      invalidatesTags: ["Deal"],
    }),

    // Bulk delete deals
    bulkDeleteDeals: builder.mutation<
      ApiResponse<Deal>,
      { ids?: number[]; exceptionIds?: number[]; isAllSelected: boolean }
    >({
      query: ({ ids, exceptionIds, isAllSelected }) => ({
        url: "/deals/bulk-delete",
        method: "DELETE",
        body: {
          ids,
          exception_ids: exceptionIds,
          is_all_selected: isAllSelected,
        },
      }),
      invalidatesTags: ["Deal"],
    }),

    // Get users for selection
    getUsers: builder.query<
      { status: boolean; message: string; data: { items: User[]; meta: any } },
      { page?: number; per_page?: number; search?: string }
    >({
      query: ({ page = 1, per_page = 50, search }) => ({
        url: "/users",
        params: { page, per_page, search },
      }),
      providesTags: ["User"],
    }),

    // Get people for selection
    getPeople: builder.query<
      { status: boolean; message: string; data: { items: Person[]; meta: any } },
      { page?: number; per_page?: number; search?: string }
    >({
      query: ({ page = 1, per_page = 50, search }) => ({
        url: "/people",
        params: { page, per_page, search },
      }),
      providesTags: ["Person"],
    }),

    // Update deal stage (when dragging deals between stages)
    updateDealStageOnly: builder.mutation<ApiResponse<Deal>, { id: number; stage_id: number }>({
      query: ({ id, stage_id }) => ({
        url: `/deals/${id}`,
        method: "PUT",
        body: { stage_id },
      }),
      invalidatesTags: (_result, _error, { id }) => [{ type: "Deal", id }, "Deal"],
    }),

    // Update stage sort order (when dragging stages to reorder)
    updateStageOrder: builder.mutation<ApiResponse<any>, { stage_id: number; sort_order: number }>({
      query: ({ stage_id, sort_order }) => ({
        url: `/deal-stages/${stage_id}/sort-order?sort_order=${sort_order}`,
        method: "PUT",
      }),
      invalidatesTags: ["DealStage"],
    }),

    // Delete attachment - NEW endpoint for deleting individual attachments
    deleteAttachment: builder.mutation<ApiResponse<any>, number>({
      query: (attachmentId) => ({
        url: `/deal-attachments/${attachmentId}`,
        method: "DELETE",
      }),
      invalidatesTags: ["Deal"],
    }),

    // Download attachment - NEW endpoint for downloading attachments
    downloadAttachment: builder.query<Blob, number>({
      query: (attachmentId) => ({
        url: `/deal-attachments/${attachmentId}/download`,
        responseHandler: (response: Response) => response.blob(),
      }),
    }),
  }),
})

export const {
  useGetDealTypesQuery,
  useGetDealStagesQuery,
  useCreateDealStageMutation,
  useUpdateDealStageMutation,
  useDeleteDealStageMutation,
  useGetDealsQuery,
  useGetDealByIdQuery,
  useCreateDealMutation,
  useUpdateDealMutation,
  useDeleteDealMutation,
  useBulkDeleteDealsMutation,
  useGetUsersQuery,
  useGetPeopleQuery,
  useUpdateDealStageOnlyMutation,
  useUpdateStageOrderMutation,
  useDeleteAttachmentMutation, // NEW
  useDownloadAttachmentQuery, // NEW
} = dealsApi
