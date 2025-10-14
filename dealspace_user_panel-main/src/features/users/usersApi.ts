import { createApi } from "@reduxjs/toolkit/query/react"
import type { CreateUserRequest, UpdateUserRequest, User } from "../../types/users"
import type { ApiResponse, ImportResponseData, PaginatedResponse } from "../../types/meta"
import { customBaseQuery } from "../../app/baseQueryHandler"
import { createUserFormData, updateUserFormData } from "../../utils/userFormData"

export const usersApi = createApi({
  reducerPath: "usersApi",
  baseQuery: customBaseQuery,
  tagTypes: ["User"],
  endpoints: (builder) => ({
    getUsers: builder.query<PaginatedResponse<User>, { page: number; per_page: number; role?: number; search?: string }>({
      query: ({ page, per_page, role, search }) => ({
        url: `/users`,
        params: { page, per_page, role, search },
      }),
      providesTags: ["User"],
    }),

    getUserById: builder.query<ApiResponse<User>, number>({
      query: (id) => `/users/${id}`,
      providesTags: (_result, _error, id) => [{ type: "User", id }],
    }),

    createUser: builder.mutation<ApiResponse<User>, CreateUserRequest>({
      query: (userData) => ({
        url: "/users",
        method: "POST",
        body: createUserFormData(userData),
        prepareHeaders: (headers:Headers) => {
          headers.delete('content-type');
          return headers;
        },
      }),
      invalidatesTags: ["User"],
    }),
    
    updateUser: builder.mutation<ApiResponse<User>, { id: number } & UpdateUserRequest>({
      query: ({ id, ...userData }) => ({
        url: `/users/${id}`,
        method: "POST",
        body: updateUserFormData({ id, ...userData }),
        prepareHeaders: (headers:Headers) => {
          headers.delete('content-type');
          return headers;
        },
      }),
      invalidatesTags: (_result, _error, { id }) => [{ type: "User", id }, "User"],
    }),

    deleteUser: builder.mutation<ApiResponse<User>, number>({
      query: (id) => ({
        url: `/users/${id}`,
        method: "DELETE",
      }),
      invalidatesTags: ["User"],
    }),

    bulkDeleteUsers: builder.mutation<
      ApiResponse<User>,
      { ids?: number[]; exceptionIds?: number[]; isAllSelected: boolean }
    >({
      query: ({ ids, exceptionIds, isAllSelected }) => ({
        url: `/users/bulk-delete`,
        method: "DELETE",
        body: {
          ids,
          exception_ids: exceptionIds,
          is_all_selected: isAllSelected,
        },
      }),
      invalidatesTags: ["User"],
    }),

    downloadTemplate: builder.query<Blob, void>({
      query: () => ({
        url: `/users/download-template`,
        method: "GET",
        responseHandler: (response: Response) => response.blob(),
      }),
    }),

    importUsers: builder.mutation<ApiResponse<ImportResponseData>, FormData>({
      query: (formData) => ({
        url: `/users/import`,
        method: "POST",
        body: formData,
      }),
      invalidatesTags: ["User"],
    }),

    bulkExportUsers: builder.mutation<Blob, { ids?: number[]; exceptionIds?: number[]; isAllSelected: boolean }>({
      query: ({ ids, exceptionIds, isAllSelected }) => ({
        url: `/users/bulk-export`,
        method: "POST",
        body: {
          ids,
          exception_ids: exceptionIds,
          is_all_selected: isAllSelected,
        },
        responseHandler: (response: Response) => response.blob(),
      }),
    }),

    getRoles: builder.query<ApiResponse<string[]>, void>({
      query: () => `/enums/roles`,
    }),
  }),
})

export const {
  useGetUsersQuery,
  useGetUserByIdQuery,
  useCreateUserMutation,
  useUpdateUserMutation,
  useDeleteUserMutation,
  useBulkDeleteUsersMutation,
  useLazyDownloadTemplateQuery,
  useImportUsersMutation,
  useBulkExportUsersMutation,
  useGetRolesQuery,
} = usersApi
