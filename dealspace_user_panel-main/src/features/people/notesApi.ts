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

export interface Note {
  id: number
  subject: string
  body: string
  person_id: number
  created_by: User
  updated_by: User | null
  mentions: User[]
  created_at?: string
  updated_at?: string
}

export interface CreateNoteRequest {
  subject: string
  body: string
  person_id: number
  mentions: number[]
}

export interface UpdateNoteRequest {
  id: number
  subject: string
  body: string
  person_id: number
  mentions: number[]
}

export interface NotesResponse {
  status: boolean
  message: string
  data: {
    items: Note[]
    meta: {
      current_page: number
      per_page: number
      total: number
      last_page: number
    }
  }
}

export interface NoteResponse {
  status: boolean
  message: string
  data: Note
}

export const notesApi = createApi({
  reducerPath: "notesApi",
  baseQuery: customBaseQuery,
  tagTypes: ["Notes", "Note"],
  endpoints: (builder) => ({
    getNotes: builder.query<NotesResponse, { person_id: number; page?: number; per_page?: number }>({
      query: ({ person_id, page = 1, per_page = 15 }) => ({
        url: `/notes`,
        params: { person_id, page, per_page },
      }),
      providesTags: (result) =>
        result
          ? [...result.data.items.map(({ id }) => ({ type: "Note" as const, id })), { type: "Notes", id: "LIST" }]
          : [{ type: "Notes", id: "LIST" }],
    }),

    getNoteById: builder.query<NoteResponse, number>({
      query: (id) => `/notes/${id}`,
      providesTags: (result, error, id) => [{ type: "Note", id }],
    }),

    createNote: builder.mutation<NoteResponse, CreateNoteRequest>({
      query: (note) => ({
        url: "/notes",
        method: "POST",
        body: note,
      }),
      invalidatesTags: [{ type: "Notes", id: "LIST" }],
    }),

    updateNote: builder.mutation<NoteResponse, UpdateNoteRequest>({
      query: ({ id, ...note }) => ({
        url: `/notes/${id}`,
        method: "PUT",
        body: note,
      }),
      invalidatesTags: (result, error, { id }) => [
        { type: "Note", id },
        { type: "Notes", id: "LIST" },
      ],
    }),

    deleteNote: builder.mutation<{ status: boolean; message: string }, number>({
      query: (id) => ({
        url: `/notes/${id}`,
        method: "DELETE",
      }),
      invalidatesTags: [{ type: "Notes", id: "LIST" }],
    }),
  }),
})

export const {
  useGetNotesQuery,
  useGetNoteByIdQuery,
  useCreateNoteMutation,
  useUpdateNoteMutation,
  useDeleteNoteMutation,
} = notesApi
