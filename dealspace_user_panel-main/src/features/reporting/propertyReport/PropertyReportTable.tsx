"use client"

import { ChevronUp, ChevronDown } from "lucide-react"
import { useState } from "react"

interface PropertyReportTableProps {
  data: any[]
  viewMode: "property" | "zipcode"
  isLoading: boolean
}

export default function PropertyReportTable({ data, viewMode, isLoading }: PropertyReportTableProps) {
  const [sortField, setSortField] = useState<string>("total_events")
  const [sortDirection, setSortDirection] = useState<"asc" | "desc">("desc")

  const handleSort = (field: string) => {
    if (sortField === field) {
      setSortDirection(sortDirection === "asc" ? "desc" : "asc")
    } else {
      setSortField(field)
      setSortDirection("desc")
    }
  }

  const sortedData = [...data].sort((a, b) => {
    const aValue = a[sortField]
    const bValue = b[sortField]

    if (typeof aValue === "string") {
      return sortDirection === "asc" ? aValue.localeCompare(bValue) : bValue.localeCompare(aValue)
    }

    return sortDirection === "asc" ? aValue - bValue : bValue - aValue
  })

  const SortIcon = ({ field }: { field: string }) => {
    if (sortField !== field) return null
    return sortDirection === "asc" ? <ChevronUp className="w-4 h-4" /> : <ChevronDown className="w-4 h-4" />
  }

  if (isLoading) {
    return (
      <div className="bg-white rounded-lg shadow">
        <div className="p-6 text-center">
          <div className="animate-pulse">Loading table data...</div>
        </div>
      </div>
    )
  }

  return (
    <div className="bg-white rounded-lg shadow overflow-hidden">
      <div className="px-6 py-4 border-b">
        <h3 className="text-lg font-semibold text-gray-900">
          {viewMode === "property" ? "Property" : "Zip Code"} Details
        </h3>
      </div>

      <div className="overflow-x-auto">
        <table className="min-w-full divide-y divide-gray-200">
          <thead className="bg-gray-50">
            <tr>
              {viewMode === "property" ? (
                <>
                  <th
                    className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                    onClick={() => handleSort("street")}
                  >
                    <div className="flex items-center gap-1">
                      Property
                      <SortIcon field="street" />
                    </div>
                  </th>
                  <th
                    className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                    onClick={() => handleSort("price")}
                  >
                    <div className="flex items-center gap-1">
                      Price
                      <SortIcon field="price" />
                    </div>
                  </th>
                </>
              ) : (
                <>
                  <th
                    className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                    onClick={() => handleSort("zip_code")}
                  >
                    <div className="flex items-center gap-1">
                      Zip Code
                      <SortIcon field="zip_code" />
                    </div>
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Location
                  </th>
                </>
              )}

              <th
                className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                onClick={() => handleSort("total_events")}
              >
                <div className="flex items-center gap-1">
                  Inquiries
                  <SortIcon field="total_events" />
                </div>
              </th>

              <th
                className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                onClick={() => handleSort("unique_leads")}
              >
                <div className="flex items-center gap-1">
                  Unique Leads
                  <SortIcon field="unique_leads" />
                </div>
              </th>

              {viewMode === "zipcode" && (
                <th
                  className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100"
                  onClick={() => handleSort("unique_properties")}
                >
                  <div className="flex items-center gap-1">
                    Properties
                    <SortIcon field="unique_properties" />
                  </div>
                </th>
              )}

              {viewMode === "property" && (
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Latest Activity
                </th>
              )}
            </tr>
          </thead>

          <tbody className="bg-white divide-y divide-gray-200">
            {sortedData.map((item, index) => (
              <tr key={viewMode === "property" ? item.mls_number : item.zip_code} className="hover:bg-gray-50">
                {viewMode === "property" ? (
                  <>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div>
                        <div className="text-sm font-medium text-gray-900">{item.street}</div>
                        <div className="text-sm text-gray-500">
                          {item.city}, {item.state} {item.zip_code}
                        </div>
                        <div className="text-xs text-gray-400">MLS: {item.mls_number}</div>
                      </div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="text-sm font-medium text-green-600">
                        ${Number.parseInt(item.price).toLocaleString()}
                      </div>
                    </td>
                  </>
                ) : (
                  <>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="text-sm font-medium text-gray-900">{item.zip_code}</div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="text-sm text-gray-900">
                        {item.city}, {item.state}
                      </div>
                    </td>
                  </>
                )}

                <td className="px-6 py-4 whitespace-nowrap">
                  <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    {item.total_events}
                  </span>
                </td>

                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{item.unique_leads}</td>

                {viewMode === "zipcode" && (
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{item.unique_properties}</td>
                )}

                {viewMode === "property" && (
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {new Date(item.latest_event_date).toLocaleDateString()}
                  </td>
                )}
              </tr>
            ))}
          </tbody>
        </table>

        {sortedData.length === 0 && (
          <div className="text-center py-12">
            <p className="text-gray-500">No data available for the selected criteria.</p>
          </div>
        )}
      </div>
    </div>
  )
}
