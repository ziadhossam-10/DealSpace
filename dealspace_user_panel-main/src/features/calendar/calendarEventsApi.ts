import { createApi } from "@reduxjs/toolkit/query/react"
import { customBaseQuery } from "../../app/baseQueryHandler"
export type TaskSyncable = {
  id: number;
  person_id: number;
  assigned_user_id: number;
  name: string;
  type: string;
  is_completed: boolean;
  due_date: string;
  due_date_time: string;
  remind_seconds_before: number;
  created_at: string;
  updated_at: string;
  deleted_at: string | null;
  tenant_id: string;
  notes: string | null;
};

export type AppointmentSyncable = {
  id: number;
  title: string;
  description: string;
  all_day: boolean;
  start: string;
  end: string;
  location: string;
  created_by_id: number;
  type_id: number | null;
  outcome_id: number | null;
  created_at: string;
  updated_at: string;
  deleted_at: string | null;
  tenant_id: string;
};


// Types
export interface CalendarEvent {
  id: number
  calendar_account_id: number
  person_id: number | null
  user_id: number | null
  tenant_id: string
  external_id: string
  title: string
  description: string | null
  location: string | null
  start_time: string
  end_time: string
  timezone: string
  is_all_day: boolean
  status: "tentative" | "confirmed" | "cancelled"
  visibility: "default" | "public" | "private"
  attendees: any[]
  organizer_email: string | null
  meeting_link: string | null
  reminders: Array<{
    method: string
    minutes: number
  }>
  recurrence: any[]
  sync_status: string
  sync_direction: string
  last_synced_at: string | null
  external_updated_at: string
  sync_error: string | null
  crm_meeting_id: string | null
  syncable_type: string | null
  syncable_id: number | null
  event_type: "task" | "appointment" | "event"
  created_at: string
  updated_at: string
  display_title: string
  color: string
  formatted_attendees: any[]
  is_linked: boolean
  is_standalone: boolean
  needs_sync: boolean
  syncable: TaskSyncable | AppointmentSyncable | null
}

interface CalendarEventsResponse {
  status: boolean
  message: string
  data: CalendarEvent[]
}

interface GetEventsParams {
  start_date?: string
  end_date?: string
  calendar_account_id?: number
  event_type?: string
}

export type DealSyncable = {
  id: number;
  name: string;
  description: string;
  price: number;
  projected_close_date: string;
  order_weight: number;
  commission_value: number;
  agent_commission: number;
  team_commission: number;
  stage_id: number;
  type_id: number;
  created_at: string;
  updated_at: string;
  stage: {
    id: number;
    name: string;
    color: string;
    sort: number;
  };
  type: {
    id: number;
    name: string;
    sort: number;
  };
  people: any[];
  users: any[];
};


// New Deal response types
interface DealsResponse {
  status: boolean
  message: string
  data: {
    items: DealSyncable[]
    meta: {
      current_page: number
      per_page: number
      total: number
      last_page: number
    }
    totals: {
      total_deals_count: number
      total_deals_price: number
    }
  }
}

interface GetDealsParams {
  start_date?: string
  end_date?: string
}

export const calendarEventsApi = createApi({
  reducerPath: "calendarEventsApi",
  baseQuery: customBaseQuery,
  tagTypes: ["CalendarEvent", "Deal"],
  endpoints: (builder) => ({
    // Get calendar events
    getCalendarEvents: builder.query<CalendarEventsResponse, GetEventsParams>({
      query: (params) => {
        const searchParams = new URLSearchParams()

        if (params.start_date) {
          searchParams.append("start_date", params.start_date)
        }
        if (params.end_date) {
          searchParams.append("end_date", params.end_date)
        }
        if (params.calendar_account_id) {
          searchParams.append("calendar_account_id", params.calendar_account_id.toString())
        }
        if (params.event_type) {
          searchParams.append("event_type", params.event_type)
        }

        return `calendar/events?${searchParams.toString()}`
      },
      providesTags: ["CalendarEvent"],
    }),
    getDealsWithClosingDate: builder.query<DealsResponse, GetDealsParams>({
      query: (params) => {
        const searchParams = new URLSearchParams();

        if (params.start_date) {
          searchParams.append("start_date", params.start_date);
        }
        if (params.end_date) {
          searchParams.append("end_date", params.end_date);
        }

        return `deals-has-closing-date?${searchParams.toString()}`;
      },
      providesTags: ["Deal"],
    }),
    // Sync calendar events
    syncCalendarEvents: builder.mutation<{ status: boolean; message: string; synced_count: any }, number>({
      query: (accountId) => ({
        url: `calendar-accounts/${accountId}/sync`,
        method: "POST",
      }),
      invalidatesTags: ["CalendarEvent"],
    }),
  }),
})

export const { useGetCalendarEventsQuery, useSyncCalendarEventsMutation, useGetDealsWithClosingDateQuery } = calendarEventsApi
