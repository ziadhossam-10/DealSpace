import { createApi } from "@reduxjs/toolkit/query/react"
import type {
  CreateGroupRequest,
  UpdateGroupRequest,
  Group,
  GroupsApiResponse,
  GroupApiResponse,
} from "../../types/groups"
import type { ApiResponse } from "../../types/meta"
import { customBaseQuery } from "../../app/baseQueryHandler"

export const groupsApi = createApi({
  reducerPath: "groupsApi",
  baseQuery: customBaseQuery,
  tagTypes: ["Group"],
  endpoints: (builder) => ({
    getGroups: builder.query<GroupsApiResponse, { page: number; per_page: number, search?: string }>({
      query: ({ page, per_page, search }) => ({
        url: `/groups`,
        params: { page, per_page , search },
      }),
      providesTags: ["Group"],
    }),

    getGroupById: builder.query<GroupApiResponse, number>({
      query: (id) => `/groups/${id}`,
      providesTags: (_result, _error, id) => [{ type: "Group", id }],
    }),

    createGroup: builder.mutation<ApiResponse<Group>, CreateGroupRequest>({
      query: (groupData) => ({
        url: "/groups",
        method: "POST",
        body: groupData,
      }),
      invalidatesTags: ["Group"],
    }),

    updateGroup: builder.mutation<ApiResponse<Group>, { id: number } & UpdateGroupRequest>({
      query: ({ id, ...groupData }) => ({
        url: `/groups/${id}`,
        method: "PUT",
        body: groupData,
      }),
      invalidatesTags: (_result, _error, { id }) => [{ type: "Group", id }, "Group"],
    }),

    deleteGroup: builder.mutation<ApiResponse<Group>, number>({
      query: (id) => ({
        url: `/groups/${id}`,
        method: "DELETE",
      }),
      invalidatesTags: ["Group"],
    }),

    updateUserSortOrder: builder.mutation<ApiResponse<any>, { groupId: number; userId: number; sortOrder: number }>({
      query: ({ groupId, userId, sortOrder }) => ({
        url: `/groups/${groupId}/users/${userId}/sort-order/${sortOrder}`,
        method: "PUT",
      }),
      invalidatesTags: (_result, _error, { groupId }) => [{ type: "Group", id: groupId }],
    }),
  }),
})

export const {
  useGetGroupsQuery,
  useGetGroupByIdQuery,
  useCreateGroupMutation,
  useUpdateGroupMutation,
  useDeleteGroupMutation,
  useUpdateUserSortOrderMutation,
} = groupsApi
