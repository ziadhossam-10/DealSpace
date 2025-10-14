import { createApi } from "@reduxjs/toolkit/query/react"
import { customBaseQuery } from "../../app/baseQueryHandler"

export const subscriptionApi = createApi({
  reducerPath: 'subscriptionApi',
  baseQuery: customBaseQuery,
  tagTypes: ['Subscription', 'Usage'],
  endpoints: (builder) => ({
    getPlans: builder.query({
      query: () => '/subscriptions/plans',
      providesTags: ['Subscription'],
    }),
    getStatus: builder.query({
      query: () => '/subscriptions/status',
      providesTags: ['Subscription'],
    }),
    getUsage: builder.query({
      query: () => '/subscriptions/usage',
      providesTags: ['Usage'],
    }),
    checkFeature: builder.query({
      query: (feature: string) => `/subscriptions/check-feature/${feature}`,
    }),
    createCheckoutSession: builder.mutation({
      query: (plan: string) => ({
        url: '/subscriptions/checkout',
        method: 'POST',
        body: { plan },
      }),
    }),
    createPortalSession: builder.mutation({
      query: () => ({
        url: '/subscriptions/portal',
        method: 'POST',
      }),
    }),
    verifyCheckoutSession: builder.mutation({
      query: (sessionId: string) => ({
        url: '/subscriptions/verify-session',
        method: 'POST',
        body: { session_id: sessionId },
      }),
      invalidatesTags: ['Subscription', 'Usage'],
    }),
    cancelSubscription: builder.mutation({
      query: () => ({
        url: '/subscriptions/cancel',
        method: 'POST',
      }),
      invalidatesTags: ['Subscription'],
    }),
    resumeSubscription: builder.mutation({
      query: () => ({
        url: '/subscriptions/resume',
        method: 'POST',
      }),
      invalidatesTags: ['Subscription'],
    }),
    getInvoices: builder.query({
      query: () => '/subscriptions/invoices',
    }),
  }),
});

export const {
  useGetPlansQuery,
  useGetStatusQuery,
  useGetUsageQuery,
  useLazyCheckFeatureQuery,
  useCreateCheckoutSessionMutation,
  useCreatePortalSessionMutation,
  useVerifyCheckoutSessionMutation,
  useCancelSubscriptionMutation,
  useResumeSubscriptionMutation,
  useGetInvoicesQuery,
} = subscriptionApi;