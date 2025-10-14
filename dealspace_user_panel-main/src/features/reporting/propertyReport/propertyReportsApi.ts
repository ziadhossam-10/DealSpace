import { createApi } from "@reduxjs/toolkit/query/react"
import { customBaseQuery } from "../../../app/baseQueryHandler"

export interface PropertyReportData {
  mls_number: string
  street: string
  city: string
  state: string
  zip_code: string
  price: string
  total_events: number
  total_inquiries: number
  unique_leads: number
  latest_event_date: string
  first_event_date: string
}

export interface ZipCodeReportData {
  zip_code: string
  city: string
  state: string
  total_events: number
  total_inquiries: number
  unique_properties: number
  unique_leads: number
}

export interface PropertyReportResponse {
  status: boolean
  message: string
  data: PropertyReportData[]
}

export interface ZipCodeReportResponse {
  status: boolean
  message: string
  data: ZipCodeReportData[]
}

export interface PropertyReportRequest {
  date_from: string
  date_to: string
  event_types?: string[]
}

export interface PropertyReportExportRequest extends PropertyReportRequest {
  view_mode: "property" | "zipcode"
}

export const propertyReportsApi = createApi({
  reducerPath: "propertyReportsApi",
  baseQuery: customBaseQuery,
  tagTypes: ["PropertyReports"],
  endpoints: (builder) => ({
    getPropertyReports: builder.query<PropertyReportResponse, PropertyReportRequest>({
      query: (params) => ({
        url: "/reports/property-reports",
        method: "POST",
        body: {
          date_from: params.date_from,
          date_to: params.date_to,
          event_types: params.event_types,
        },
      }),
      providesTags: ["PropertyReports"],
    }),

    getPropertyReportsByZip: builder.query<ZipCodeReportResponse, PropertyReportRequest>({
      query: (params) => ({
        url: "/reports/property-reports/by-zip-code",
        method: "POST",
        body: {
          date_from: params.date_from,
          date_to: params.date_to,
          event_types: params.event_types,
        },
      }),
      providesTags: ["PropertyReports"],
    }),

    downloadPropertyReportsExcel: builder.mutation<void, PropertyReportExportRequest>({
      query: (params) => ({
        url: `/reports/property-reports${params.view_mode === "zipcode" ? "/by-zip-code" : ""}/download-excel`,
        method: "POST",
        body: {
          date_from: params.date_from,
          date_to: params.date_to,
          event_types: params.event_types,
        },
        responseHandler: async (response: Response) => {
          const blob = await response.blob()
          const url = window.URL.createObjectURL(blob)
          const a = document.createElement("a")
          a.href = url
          a.download = `property-reports-${params.view_mode}-${params.date_from}-to-${params.date_to}.xlsx`
          document.body.appendChild(a)
          a.click()
          window.URL.revokeObjectURL(url)
          a.remove()
        },
        cache: "no-cache",
      }),
    }),
  }),
})

export const { useGetPropertyReportsQuery, useGetPropertyReportsByZipQuery, useDownloadPropertyReportsExcelMutation } =
  propertyReportsApi
