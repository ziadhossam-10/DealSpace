import { createApi } from "@reduxjs/toolkit/query/react"
import { customBaseQuery } from "../../app/baseQueryHandler";
import { CreateTeamRequest, Team, TeamApiResponse, TeamsApiResponse, UpdateTeamRequest } from "../../types/teams";
import { ApiResponse } from "../../types/meta";

export const teamsApi = createApi({
  reducerPath: "teamsApi",
  baseQuery: customBaseQuery,
  tagTypes: ["Team"],
  endpoints: (builder) => ({
    getTeams: builder.query<TeamsApiResponse, { page: number; per_page: number; search?: string }>({
      query: ({ page, per_page, search }) => ({
        url: `/teams`,
        params: { page, per_page, search },
      }),
      providesTags: ["Team"],
    }),
    getTeamById: builder.query<TeamApiResponse, number>({
      query: (id) => `/teams/${id}`,
      providesTags: (_result, _error, id) => [{ type: "Team", id }],
    }),
    createTeam: builder.mutation<ApiResponse<Team>, CreateTeamRequest>({
      query: (teamData) => ({
        url: "/teams",
        method: "POST",
        body: teamData,
      }),
      invalidatesTags: ["Team"],
    }),
    updateTeam: builder.mutation<ApiResponse<Team>, { id: number } & UpdateTeamRequest>({
      query: ({ id, ...teamData }) => ({
        url: `/teams/${id}`,
        method: "PUT",
        body: teamData,
      }),
      invalidatesTags: (_result, _error, { id }) => [{ type: "Team", id }, "Team"],
    }),
    deleteTeam: builder.mutation<ApiResponse<Team>, number>({
      query: (id) => ({
        url: `/teams/${id}`,
        method: "DELETE",
      }),
      invalidatesTags: ["Team"],
    }),
    bulkDeleteTeams: builder.mutation<
      ApiResponse<Team>,
      { ids?: number[]; exceptionIds?: number[]; isAllSelected: boolean }
    >({
      query: ({ ids, exceptionIds, isAllSelected }) => ({
        url: `/teams/bulk-delete`,
        method: "DELETE",
        body: {
          ids,
          exception_ids: exceptionIds,
          is_all_selected: isAllSelected,
        },
      }),
      invalidatesTags: ["Team"],
    }),
  }),
})

export const {
  useGetTeamsQuery,
  useGetTeamByIdQuery,
  useCreateTeamMutation,
  useUpdateTeamMutation,
  useDeleteTeamMutation,
  useBulkDeleteTeamsMutation,
} = teamsApi
