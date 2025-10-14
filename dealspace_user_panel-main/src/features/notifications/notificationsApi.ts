import { createApi } from "@reduxjs/toolkit/query/react"
import { customBaseQuery } from "../../app/baseQueryHandler"

export interface Notification {
  id: number
  title: string
  message: string
  action: string
  image: string | null
  created_at: string
  read_at?: string | null
}

export interface NotificationResponse {
  status: boolean
  message: string
  data: {
    items: Notification[]
    meta: {
      current_page: number
      per_page: number
      total: number
      last_page: number
    }
  }
}

export interface UnreadCountResponse {
  status: boolean
  message: string
  data: {
    count: number
  }
}

export const notificationsApi = createApi({
  reducerPath: "notificationsApi",
  baseQuery: customBaseQuery,
  tagTypes: ["Notification", "UnreadCount"],
  endpoints: (builder) => ({
    getNotifications: builder.query<NotificationResponse, { page?: number; per_page?: number }>({
      query: ({ page = 1, per_page = 10 }) => ({
        url: `/users/notifications`,
        params: { page, per_page },
      }),
      providesTags: ["Notification"],
      merge: (currentCache, newItems, { arg }) => {
        if (arg.page === 1) {
          return newItems
        }
        return {
          ...newItems,
          data: {
            ...newItems.data,
            items: [...currentCache.data.items, ...newItems.data.items],
          },
        }
      },
      forceRefetch({ currentArg, previousArg }) {
        return currentArg?.page !== previousArg?.page
      },
    }),

    // Separate endpoint for pagination that doesn't use cache merging
    getNotificationsPaginated: builder.query<NotificationResponse, { page: number; per_page?: number }>(
      {
        query: ({ page, per_page = 10 }) => ({
          url: `/users/notifications`,
          params: { page, per_page },
        }),
        providesTags: ["Notification"],
      },
    ),

    getUnreadCount: builder.query<UnreadCountResponse, void>({
      query: () => `/users/notifications/unread-count`,
      providesTags: ["UnreadCount"],
    }),

    markAllAsRead: builder.mutation<{ status: boolean; message: string }, void>({
      query: () => ({
        url: `/users/notifications/read-all`,
        method: "POST",
      }),
      invalidatesTags: ["Notification", "UnreadCount"],
    }),

    markAsRead: builder.mutation<{ status: boolean; message: string }, { notificationId: number }>({
      query: ({ notificationId }) => ({
        url: `/users/notifications/${notificationId}/read`,
        method: "POST",
      }),
      invalidatesTags: ["Notification", "UnreadCount"],
    }),
  }),
})

export const {
  useGetNotificationsQuery,
  useGetNotificationsPaginatedQuery,
  useLazyGetNotificationsPaginatedQuery,
  useGetUnreadCountQuery,
  useMarkAllAsReadMutation,
  useMarkAsReadMutation,
} = notificationsApi
