import { createApi } from "@reduxjs/toolkit/query/react"
import { customBaseQuery } from "../../app/baseQueryHandler"

export interface User {
  id: number
  name: string
  email: string
  avatar: string | null
  role: number
  role_name: string
  created_at: string
  updated_at: string
}

export interface TextMessage {
  id: number
  message: string
  to_number: string
  from_number: string
  is_incoming: boolean
  external_label: string
  external_url: string | null
  person_id: number
  user_id: number
  user: User
  created_at: string
  updated_at: string
}

export interface CreateTextMessageRequest {
  person_id: number
  message: string
  to_number: string
  external_label?: string
  external_url?: string
}

export interface TextMessagesResponse {
  status: boolean
  message: string
  data: {
    items: TextMessage[]
    meta: {
      current_page: number
      per_page: number
      total: number
      last_page: number
    }
  }
}

export interface TextMessageResponse {
  status: boolean
  message: string
  data: TextMessage
}

export const textMessagesApi = createApi({
  reducerPath: "textMessagesApi",
  baseQuery: customBaseQuery,
  tagTypes: ["TextMessages", "TextMessage"],
  endpoints: (builder) => ({
    getTextMessages: builder.query<TextMessagesResponse, { person_id: number; page?: number; per_page?: number }>({
      query: ({ person_id, page = 1, per_page = 15 }) => ({
        url: `/text-messages`,
        params: { person_id, page, per_page },
      }),
      providesTags: (result) =>
        result
          ? [
              ...result.data.items.map(({ id }) => ({ type: "TextMessage" as const, id })),
              { type: "TextMessages", id: "LIST" },
            ]
          : [{ type: "TextMessages", id: "LIST" }],
    }),

    getTextMessageById: builder.query<TextMessageResponse, number>({
      query: (id) => `/text-messages/${id}`,
      providesTags: (result, error, id) => [{ type: "TextMessage", id }],
    }),

    createTextMessage: builder.mutation<TextMessageResponse, CreateTextMessageRequest>({
      query: (textMessage) => ({
        url: "/text-messages",
        method: "POST",
        body: textMessage,
      }),
      invalidatesTags: [{ type: "TextMessages", id: "LIST" }],
    }),
  }),
})

export const { useGetTextMessagesQuery, useGetTextMessageByIdQuery, useCreateTextMessageMutation } = textMessagesApi
