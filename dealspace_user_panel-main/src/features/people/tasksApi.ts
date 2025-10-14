import { createApi, fetchBaseQuery } from "@reduxjs/toolkit/query/react"
import { customBaseQuery } from "../../app/baseQueryHandler"

// Types
interface User {
  id: number
  name: string
  email: string
  avatar: string | null
  role: number
  role_name: string
}

export interface Task {
  id: number
  person_id: number
  assigned_user_id: number
  name: string
  type: string
  is_completed: boolean
  due_date: string
  due_date_time: string
  remind_seconds_before: number
  notes: string | null
  status: string
  priority: string
  formatted_due_date: string
  is_overdue: boolean
  is_due_today: boolean
  is_due_soon: boolean
  is_future: boolean
  reminder_time: string
  needs_reminder_now: boolean
  created_at: string
  updated_at: string
  person: {
    id: number
    name: string
    first_name: string
    last_name: string
  }
  assigned_user: User
}

interface CreateTaskRequest {
  person_id: number
  assigned_user_id: number
  name: string
  type: string
  is_completed: boolean
  due_date: string
  due_date_time: string
  remind_seconds_before: number
  notes?: string
  priority?: string
}

interface UpdateTaskRequest extends Partial<CreateTaskRequest> {
  id: number
}

interface TasksResponse {
  status: boolean
  message: string
  data: {
    items: Task[]
    meta: {
      current_page: number
      per_page: number
      total: number
      last_page: number
    }
  }
}

interface TaskResponse {
  status: boolean
  message: string
  data: Task
}

// Task types constant
export const TASK_TYPES = [
  "Follow Up",
  "Call",
  "Text",
  "Email",
  "Appointment",
  "Showing",
  "Closing",
  "Open House",
  "Thank You",
]

export const TASK_PRIORITIES = ["Low", "Medium", "High", "Urgent"]

export const TASK_STATUSES = ["Pending", "In Progress", "Completed", "Cancelled"]

export const tasksApi = createApi({
  reducerPath: "tasksApi",
  baseQuery: customBaseQuery,
  tagTypes: ["Task"],
  endpoints: (builder) => ({
    // Get all tasks
    getTasks: builder.query<TasksResponse, { page?: number; per_page?: number; person_id?: number }>({
      query: ({ page = 1, per_page = 15, person_id }) => {
        const params = new URLSearchParams({
          page: page.toString(),
          per_page: per_page.toString(),
        })
        if (person_id) {
          params.append("person_id", person_id.toString())
        }
        return `tasks?${params.toString()}`
      },
      providesTags: ["Task"],
    }),

    // Get single task
    getTask: builder.query<TaskResponse, number>({
      query: (id) => `tasks/${id}`,
      providesTags: (result, error, id) => [{ type: "Task", id }],
    }),

    // Create task
    createTask: builder.mutation<TaskResponse, CreateTaskRequest>({
      query: (data) => ({
        url: "tasks",
        method: "POST",
        body: data,
      }),
      invalidatesTags: ["Task"],
    }),

    // Update task
    updateTask: builder.mutation<TaskResponse, UpdateTaskRequest>({
      query: ({ id, ...data }) => ({
        url: `tasks/${id}`,
        method: "PUT",
        body: data,
      }),
      invalidatesTags: (result, error, { id }) => [{ type: "Task", id }, "Task"],
    }),

    // Delete task
    deleteTask: builder.mutation<{ status: boolean; message: string }, number>({
      query: (id) => ({
        url: `tasks/${id}`,
        method: "DELETE",
      }),
      invalidatesTags: (result, error, id) => [{ type: "Task", id }, "Task"],
    }),

    // Mark task as completed
    completeTask: builder.mutation<TaskResponse, number>({
      query: (id) => ({
        url: `tasks/${id}/complete`,
        method: "PATCH",
      }),
      invalidatesTags: (result, error, id) => [{ type: "Task", id }, "Task"],
    }),
  }),
})

export const {
  useGetTasksQuery,
  useGetTaskQuery,
  useCreateTaskMutation,
  useUpdateTaskMutation,
  useDeleteTaskMutation,
  useCompleteTaskMutation,
} = tasksApi
