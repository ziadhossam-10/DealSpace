"use client"

import type React from "react"
import { useState, useEffect, type ReactNode, useRef } from "react"
import { Eye, EyeOff, ChevronDown } from "lucide-react"
import { TableErrorComponent } from "../ui/error/TableErrorComponent"
import { TableLoader } from "../ui/loader/TableLoader"

export interface Column {
  key: string
  label: string
  render?: (item: any) => React.ReactNode
  isMain?: boolean // Whether this column is shown by default
}

interface DynamicTableProps {
  columns: Column[]
  data: any[]
  totalCount?: number
  currentPage?: number
  onSelectionChange?: (selectedIds: number[], allSelected: boolean, deselectedIds: number[]) => void
  bulkActions?: Array<{
    label: string
    action: (selectedIds: number[]) => void
  }>
  idField?: string
  pageTitle: string
  actionButton?: ReactNode
  noDataTilte?: string
  noDataDescription?: string
  error?: any
  isLoading: boolean
}

const DynamicTable: React.FC<DynamicTableProps> = ({
  columns: initialColumns,
  data,
  totalCount = 0,
  currentPage = 1,
  onSelectionChange,
  bulkActions = [],
  idField = "id",
  pageTitle,
  actionButton,
  noDataTilte = "No data available",
  noDataDescription = "No data available upload or add data to show",
  error,
  isLoading,
}) => {
  // State for column visibility
  const [columns, setColumns] = useState<Column[]>(() => {
    // Set isMain to true by default if not specified
    return initialColumns.map((col) => ({
      ...col,
      isMain: col.isMain === undefined ? true : col.isMain,
    }))
  })

  const [visibleColumns, setVisibleColumns] = useState<string[]>(() => {
    return initialColumns.filter((col) => col.isMain !== false).map((col) => col.key)
  })

  // State for selection
  const [selectedRows, setSelectedRows] = useState<number[]>([])
  const [allSelected, setAllSelected] = useState(false)
  const [deselectedRows, setDeselectedRows] = useState<number[]>([])

  // Dropdown states
  const [bulkActionsOpen, setBulkActionsOpen] = useState(false)
  const [columnsMenuOpen, setColumnsMenuOpen] = useState(false)
  const [selectionMenuOpen, setSelectionMenuOpen] = useState(false)

  // Notify parent component when selection changes
  useEffect(() => {
    if (onSelectionChange) {
      onSelectionChange(selectedRows, allSelected, deselectedRows)
    }
  }, [selectedRows, allSelected, deselectedRows, onSelectionChange])

  // Toggle column visibility
  const toggleColumnVisibility = (key: string) => {
    if (visibleColumns.includes(key)) {
      setVisibleColumns(visibleColumns.filter((k) => k !== key))
    } else {
      setVisibleColumns([...visibleColumns, key])
    }
  }

  // Handle row selection
  const handleRowSelect = (id: number) => {
    if (allSelected) {
      // If all are selected, we're managing deselections
      if (deselectedRows.includes(id)) {
        // Remove from deselected (which means select it again)
        setDeselectedRows(deselectedRows.filter((rowId) => rowId !== id))
      } else {
        // Add to deselected
        setDeselectedRows([...deselectedRows, id])
      }
    } else {
      // Normal selection mode
      if (selectedRows.includes(id)) {
        setSelectedRows(selectedRows.filter((rowId) => rowId !== id))
      } else {
        setSelectedRows([...selectedRows, id])
      }
    }
  }

  // Handle "select all" for current page
  const handleSelectAllPage = () => {
    if (allSelected) {
      // If all are selected, we need to deselect all
      setSelectedRows([])
      setAllSelected(false)
      setDeselectedRows([])
    } else {
      setSelectedRows([])
      setAllSelected(true)
    }
  }

  // Handle "select all" across all pages
  const handleSelectAll = () => {
    if (allSelected) {
      // If all were selected, deselect all
      setAllSelected(false)
      setDeselectedRows([])
      setSelectedRows([])
    } else {
      // Select all across all pages
      setAllSelected(true)
      setDeselectedRows([])
      setSelectedRows([])
    }
  }

  // Check if a row is selected
  const isRowSelected = (id: number) => {
    if (allSelected) {
      return !deselectedRows.includes(id)
    }
    return selectedRows.includes(id)
  }

  // Calculate how many items are selected
  const selectedCount = allSelected ? totalCount - deselectedRows.length : selectedRows.length

  // Filter columns based on visibility
  const filteredColumns = columns.filter((col) => visibleColumns.includes(col.key))

  // Custom checkbox component with Tailwind
  const Checkbox = ({
    checked,
    indeterminate,
    onChange,
  }: {
    checked?: boolean
    indeterminate?: boolean
    onChange?: () => void
  }) => {
    return (
      <div className="relative flex items-center justify-center">
        <input
          type="checkbox"
          checked={checked}
          onChange={onChange}
          className="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 cursor-pointer"
          ref={(input) => {
            if (input) {
              input.indeterminate = indeterminate || false
            }
          }}
        />
      </div>
    )
  }

  // Close dropdowns when clicking outside
  const bulkActionsRef = useRef<HTMLDivElement>(null)
  const columnsMenuRef = useRef<HTMLDivElement>(null)

  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (bulkActionsRef.current && !bulkActionsRef.current.contains(event.target as Node)) {
        setBulkActionsOpen(false)
      }

      if (columnsMenuRef.current && !columnsMenuRef.current.contains(event.target as Node)) {
        setColumnsMenuOpen(false)
      }
    }

    document.addEventListener("mousedown", handleClickOutside)
    return () => {
      document.removeEventListener("mousedown", handleClickOutside)
    }
  }, [])
  return (
    <div className="space-y-4">
      {/* Bulk Actions and Column Visibility */}
      <div className="flex justify-between items-center">
        <h2 className="text-xl font-semibold text-gray-800 dark:text-white/90">{pageTitle}</h2>
        <div className="flex gap-2 justify-center items-center">
          {/* Bulk Actions */}
          {selectedCount > 0 && (
            <div className="flex items-center space-x-2">
              <span className="text-sm font-medium">{selectedCount} selected</span>
              <div className="relative" ref={bulkActionsRef}>
                <button
                  className="px-3 py-1 bg-gray-100 text-gray-800 rounded flex items-center"
                  onClick={(e) => {
                    e.stopPropagation()
                    setBulkActionsOpen(!bulkActionsOpen)
                  }}
                >
                  Bulk Actions <ChevronDown className="ml-1 h-4 w-4" />
                </button>
                {bulkActionsOpen && (
                  <div className="absolute left-0 mt-1 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-10">
                    <div className="py-1" role="menu" aria-orientation="vertical">
                      {bulkActions.map((action, index) => (
                        <button
                          key={index}
                          className="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                          onClick={(e) => {
                            e.stopPropagation()
                            const idsToProcess = allSelected
                              ? data.map((item) => item[idField]).filter((id) => !deselectedRows.includes(id))
                              : selectedRows
                            action.action(idsToProcess)
                          }}
                        >
                          {action.label}
                        </button>
                      ))}
                    </div>
                  </div>
                )}
              </div>
            </div>
          )}

          {/* Column Visibility */}
          <div className="relative">
            <button
              className="px-3 py-1 bg-gray-100 text-gray-800 rounded flex items-center"
              onClick={(e) => {
                e.stopPropagation()
                setColumnsMenuOpen(!columnsMenuOpen)
              }}
            >
              Columns <ChevronDown className="ml-1 h-4 w-4" />
            </button>
            {columnsMenuOpen && (
              <div className="absolute right-0 mt-1 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-10">
                <div className="py-1" role="menu" aria-orientation="vertical">
                  {columns.map((column) => (
                    <button
                      key={column.key}
                      className="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center justify-between"
                      onClick={(e) => {
                        e.stopPropagation()
                        toggleColumnVisibility(column.key)
                      }}
                    >
                      {column.label}
                      {visibleColumns.includes(column.key) ? (
                        <Eye className="h-4 w-4 ml-2" />
                      ) : (
                        <EyeOff className="h-4 w-4 ml-2" />
                      )}
                    </button>
                  ))}
                </div>
              </div>
            )}
          </div>
          {actionButton}
        </div>
      </div>

      {/* Table */}
      <div className="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-white/[0.05] dark:bg-white/[0.03]">
        <div className="max-w-full overflow-x-auto">
          <table className="min-w-full divide-y divide-gray-200">
            {/* Table Header */}
            <thead className="bg-gray-50 dark:bg-gray-800">
              <tr>
                {/* Selection Column */}
                {bulkActions.length > 0 && (
                  <th scope="col" className="w-10 px-5 py-3">
                    <div className="flex items-center">
                      <Checkbox
                        checked={data.length > 0 && (allSelected || selectedRows.length === data.length)}
                        indeterminate={!allSelected && selectedRows.length > 0 && selectedRows.length < data.length}
                        onChange={handleSelectAllPage}
                      />
                      {selectedRows.length > 0 && (
                        <div className="relative ml-2">
                          <button
                            onClick={(e) => {
                              e.stopPropagation()
                              setSelectionMenuOpen(!selectionMenuOpen)
                            }}
                          >
                            <ChevronDown className="h-4 w-4" />
                          </button>
                          {selectionMenuOpen && (
                            <div className="absolute left-0 mt-1 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-10">
                              <div className="py-1" role="menu" aria-orientation="vertical">
                                <button
                                  className="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                  onClick={(e) => {
                                    e.stopPropagation()
                                    handleSelectAll()
                                    setSelectionMenuOpen(false)
                                  }}
                                >
                                  {allSelected ? "Deselect all" : "Select all pages"}
                                </button>
                              </div>
                            </div>
                          )}
                        </div>
                      )}
                    </div>
                  </th>
                )}

                {/* Data Columns */}
                {filteredColumns.map((column) => (
                  <th
                    key={column.key}
                    scope="col"
                    className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400"
                  >
                    {column.label}
                  </th>
                ))}
              </tr>
            </thead>

            {/* Table Body */}
            <tbody className="bg-white divide-y divide-gray-200 dark:bg-gray-900 dark:divide-gray-700">
              {isLoading ? (
                <td colSpan={filteredColumns.length + (bulkActions.length > 0 ? 1 : 0)} className="px-5 py-4">
                  <TableLoader />
                </td>
              ) : error ? (
                <td colSpan={filteredColumns.length + (bulkActions.length > 0 ? 1 : 0)} className="px-5 py-4">
                  <TableErrorComponent />
                </td>
              ) : data.length === 0 ? (
                <tr>
                  <td colSpan={filteredColumns.length + (bulkActions.length > 0 ? 1 : 0)} className="px-5 py-4">
                    <div className="flex flex-col items-center justify-center p-10 border border-dashed border-gray-300 rounded-md text-center text-gray-500 dark:text-gray-400">
                      <svg
                        className="w-16 h-16 mb-4 text-gray-300 dark:text-gray-500"
                        fill="none"
                        stroke="currentColor"
                        strokeWidth="1.5"
                        viewBox="0 0 24 24"
                      >
                        <path
                          strokeLinecap="round"
                          strokeLinejoin="round"
                          d="M9 17v-1m0 0a3 3 0 003-3h4a3 3 0 013 3v1m-10 0h10m-10 0a3 3 0 01-3-3V7a3 3 0 013-3h6a3 3 0 013 3v6a3 3 0 01-3 3"
                        />
                      </svg>
                      <p className="text-lg font-medium">{noDataTilte}</p>
                      <p className="text-sm mt-1">{noDataDescription}</p>
                    </div>
                  </td>
                </tr>
              ) : (
                data.map((item, rowIndex) => (
                  <tr key={rowIndex} className="hover:bg-gray-50 dark:hover:bg-gray-800">
                    {/* Selection Cell */}
                    {bulkActions.length > 0 && (
                      <td className="w-10 px-5 py-4">
                        <Checkbox
                          checked={isRowSelected(item[idField])}
                          onChange={() => handleRowSelect(item[idField])}
                        />
                      </td>
                    )}

                    {/* Data Cells */}
                    {filteredColumns.map((column) => (
                      <td
                        key={column.key}
                        className="px-5 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-white/90"
                      >
                        {column.render ? column.render(item) : item[column.key]}
                      </td>
                    ))}
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  )
}

export default DynamicTable
