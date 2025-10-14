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

export interface Call {
  id: number
  phone: string
  note: string
  outcome: number
  outcome_text: string
  duration: number
  to_number: string
  from_number: string
  recording_url: string | null
  is_incoming: boolean
  person_id: number
  user_id: number
  user: User
  created_at: string
  updated_at: string
}

export interface CreateCallRequest {
  person_id: number
  phone: string
  is_incoming: boolean
  note: string
  outcome: number
  duration: number
  to_number: string
  from_number: string
  recording_url?: string
}

export interface UpdateCallRequest {
  id: number
  person_id: number
  phone: string
  is_incoming: boolean
  note: string
  outcome: number
  duration: number
  to_number: string
  from_number: string
  recording_url?: string
}

export interface CallsResponse {
  status: boolean
  message: string
  data: {
    items: Call[]
    meta: {
      current_page: number
      per_page: number
      total: number
      last_page: number
    }
  }
}

export interface CallResponse {
  status: boolean
  message: string
  data: Call
}

// Outcome options enum (matching your PHP enum)
export const OutcomeOptions = {
  INTERESTED: 0,
  NOT_INTERESTED: 1,
  LEFT_MESSAGE: 2,
  NO_ANSWER: 3,
  BUSY: 4,
  BAD_NUMBER: 5,
} as const

export const getOutcomeLabel = (outcome: number): string => {
  switch (outcome) {
    case OutcomeOptions.INTERESTED:
      return "Interested"
    case OutcomeOptions.NOT_INTERESTED:
      return "Not Interested"
    case OutcomeOptions.LEFT_MESSAGE:
      return "Left Message"
    case OutcomeOptions.NO_ANSWER:
      return "No Answer"
    case OutcomeOptions.BUSY:
      return "Busy"
    case OutcomeOptions.BAD_NUMBER:
      return "Bad Number"
    default:
      return "Unknown"
  }
}

export const getOutcomeOptions = () => [
  { value: OutcomeOptions.INTERESTED, label: "Interested" },
  { value: OutcomeOptions.NOT_INTERESTED, label: "Not Interested" },
  { value: OutcomeOptions.LEFT_MESSAGE, label: "Left Message" },
  { value: OutcomeOptions.NO_ANSWER, label: "No Answer" },
  { value: OutcomeOptions.BUSY, label: "Busy" },
  { value: OutcomeOptions.BAD_NUMBER, label: "Bad Number" },
]

export const callsApi = createApi({
  reducerPath: "callsApi",
  baseQuery: customBaseQuery,
  tagTypes: ["Calls", "Call"],
  endpoints: (builder) => ({
    getCalls: builder.query<CallsResponse, { person_id: number; page?: number; per_page?: number }>({
      query: ({ person_id, page = 1, per_page = 15 }) => ({
        url: `/calls`,
        params: { person_id, page, per_page },
      }),
      providesTags: (result) =>
        result
          ? [...result.data.items.map(({ id }) => ({ type: "Call" as const, id })), { type: "Calls", id: "LIST" }]
          : [{ type: "Calls", id: "LIST" }],
    }),

    getCallById: builder.query<CallResponse, number>({
      query: (id) => `/calls/${id}`,
      providesTags: (result, error, id) => [{ type: "Call", id }],
    }),

    createCall: builder.mutation<CallResponse, CreateCallRequest>({
      query: (call) => ({
        url: "/calls",
        method: "POST",
        body: call,
      }),
      invalidatesTags: [{ type: "Calls", id: "LIST" }],
    }),

    updateCall: builder.mutation<CallResponse, UpdateCallRequest>({
      query: ({ id, ...call }) => ({
        url: `/calls/${id}`,
        method: "PUT",
        body: call,
      }),
      invalidatesTags: (result, error, { id }) => [
        { type: "Call", id },
        { type: "Calls", id: "LIST" },
      ],
    }),

    deleteCall: builder.mutation<{ status: boolean; message: string }, number>({
      query: (id) => ({
        url: `/calls/${id}`,
        method: "DELETE",
      }),
      invalidatesTags: [{ type: "Calls", id: "LIST" }],
    }),
  }),
})

export const {
  useGetCallsQuery,
  useGetCallByIdQuery,
  useCreateCallMutation,
  useUpdateCallMutation,
  useDeleteCallMutation,
} = callsApi
