import { createApi } from '@reduxjs/toolkit/query/react';
import { customBaseQuery } from '../../app/baseQueryHandler';
import { LeadFlowRule, LeadFlowRuleFormData } from '../../types/leadFlowRules';

export const leadFlowRulesApi = createApi({
  reducerPath: 'leadFlowRulesApi',
  baseQuery: customBaseQuery,
  tagTypes: ['LeadFlowRules'],
  endpoints: (builder) => ({
    getLeadFlowRules: builder.query<{ data: LeadFlowRule[] }, { source_type?: string; source_name?: string }>({
      query: (params) => ({
        url: '/lead-flow-rules',
        params,
      }),
      providesTags: ['LeadFlowRules'],
    }),

    createLeadFlowRule: builder.mutation<{ data: LeadFlowRule }, LeadFlowRuleFormData>({
      query: (data) => ({
        url: '/lead-flow-rules',
        method: 'POST',
        body: data,
      }),
      invalidatesTags: ['LeadFlowRules'],
    }),

    updateLeadFlowRule: builder.mutation<{ data: LeadFlowRule }, { id: number; data: LeadFlowRuleFormData }>({
      query: ({ id, data }) => ({
        url: `/lead-flow-rules/${id}`,
        method: 'PUT',
        body: data,
      }),
      invalidatesTags: ['LeadFlowRules'],
    }),

    deleteLeadFlowRule: builder.mutation<void, number>({
      query: (id) => ({
        url: `/lead-flow-rules/${id}`,
        method: 'DELETE',
      }),
      invalidatesTags: ['LeadFlowRules'],
    }),

    reorderRules: builder.mutation<void, Array<{ id: number; priority: number }>>({
      query: (rules) => ({
        url: '/lead-flow-rules/reorder',
        method: 'POST',
        body: { rules },
      }),
      invalidatesTags: ['LeadFlowRules'],
    }),

    copyRulesFromSource: builder.mutation<{ message: string }, {
      from_source_type: string;
      from_source_name: string;
      to_source_type: string;
      to_source_name: string;
    }>({
      query: (data) => ({
        url: '/lead-flow-rules/copy-from-source',
        method: 'POST',
        body: data,
      }),
      invalidatesTags: ['LeadFlowRules'],
    }),
  }),
});

export const {
  useGetLeadFlowRulesQuery,
  useCreateLeadFlowRuleMutation,
  useUpdateLeadFlowRuleMutation,
  useDeleteLeadFlowRuleMutation,
  useReorderRulesMutation,
  useCopyRulesFromSourceMutation,
} = leadFlowRulesApi;