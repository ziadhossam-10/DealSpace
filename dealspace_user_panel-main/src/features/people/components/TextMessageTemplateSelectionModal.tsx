"use client"

import type React from "react"
import { useEffect, useState } from "react"
import { Modal } from "../../../components/modal"
import { TableLoader } from "../../../components/ui/loader/TableLoader"
import { TableErrorComponent } from "../../../components/ui/error/TableErrorComponent"
import { TablePagination } from "../../../components/ui/pagination/TablePagination"
import DynamicTable from "../../../components/tables/BasicTableOne"
import { useGetTextMessageTemplatesQuery } from "../../textMessageTemplates/textMessageTemplatesApi"
import type { TextMessageTemplate } from "../../../types/textMessageTemplates"
import { Eye, Share2, Lock, MessageSquare } from "lucide-react"

interface TextMessageTemplateSelectionModalProps {
  isOpen: boolean
  onClose: () => void
  onSelect: (template: TextMessageTemplate) => void
  initialSearchValue?: string
}

const TextMessageTemplateSelectionModal: React.FC<TextMessageTemplateSelectionModalProps> = ({
  isOpen,
  onClose,
  onSelect,
  initialSearchValue = "",
}) => {
  // State Variables
  const [page, setPage] = useState(1)
  const [pageSize, setPageSize] = useState(10)
  const [totalPages, setTotalPages] = useState(1)
  const [totalCount, setTotalCount] = useState(0)
  const [searchValue, setSearchValue] = useState(initialSearchValue)
  const [previewTemplate, setPreviewTemplate] = useState<TextMessageTemplate | null>(null)

  // API Query
  const { data, isLoading, error } = useGetTextMessageTemplatesQuery({ page, per_page: pageSize, search: searchValue })

  // Table columns
  const columns = [
    {
      key: "name",
      label: "Template Name",
      render: (row: TextMessageTemplate) => (
        <div className="flex items-center space-x-2">
          <MessageSquare className="h-4 w-4 text-green-500" />
          <div>
            <div className="font-medium text-gray-900">{row.name}</div>
            <div className="text-sm text-gray-500 truncate max-w-xs">
              {row.message.length > 50 ? `${row.message.substring(0, 50)}...` : row.message}
            </div>
          </div>
        </div>
      ),
    },
    {
      key: "shared",
      label: "Sharing",
      render: (row: TextMessageTemplate) => (
        <div className="flex items-center space-x-1">
          {row.is_shared ? (
            <>
              <Share2 className="h-4 w-4 text-green-500" />
              <span className="text-sm text-green-600">Shared</span>
            </>
          ) : (
            <>
              <Lock className="h-4 w-4 text-gray-500" />
              <span className="text-sm text-gray-600">Private</span>
            </>
          )}
        </div>
      ),
    },
    {
      key: "length",
      label: "Length",
      render: (row: TextMessageTemplate) => <div className="text-sm text-gray-600">{row.message.length} chars</div>,
    },
    {
      key: "owner",
      label: "Owner",
      render: (row: TextMessageTemplate) => <div className="text-sm">{row.user?.name || "-"}</div>,
    },
    {
      key: "actions",
      label: "Actions",
      render: (row: TextMessageTemplate) => (
        <div className="flex items-center gap-2">
          <button
            className="px-3 py-1 bg-green-500 text-white rounded text-sm hover:bg-green-600"
            onClick={() => {
              onSelect(row)
              onClose()
            }}
          >
            Select
          </button>
        </div>
      ),
    },
  ]

  // Reset page when search changes
  useEffect(() => {
    setPage(1)
  }, [searchValue])

  // Initialize search value from props
  useEffect(() => {
    setSearchValue(initialSearchValue)
  }, [initialSearchValue])

  // Update pagination data from the query response
  useEffect(() => {
    if (data?.data) {
      if (data.data.meta.last_page) {
        setTotalPages(data.data.meta.last_page)
      }
      if (data.data.meta.total) {
        setTotalCount(data.data.meta.total)
      }
    }
  }, [data])

  return (
    <>
      <Modal isOpen={isOpen} onClose={onClose} showCloseButton isFullscreen={false} className="max-w-4xl p-4">
        <div className="flex items-center justify-between p-4 md:p-5 border-b border-gray-200 rounded-t">
          <h3 className="text-xl font-semibold text-gray-900">Select Text Message Template</h3>
        </div>

        <div className="p-4 md:p-5">
          {/* Search Input */}
          <div className="mb-6">
            <div className="relative">
              <div className="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                <svg
                  className="w-4 h-4 text-gray-500"
                  aria-hidden="true"
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 20 20"
                >
                  <path
                    stroke="currentColor"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth="2"
                    d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"
                  />
                </svg>
              </div>
              <input
                type="search"
                value={searchValue}
                onChange={(e) => setSearchValue(e.target.value)}
                className="block w-full p-4 ps-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Search text message templates..."
              />
              {searchValue && (
                <button
                  onClick={() => setSearchValue("")}
                  className="absolute end-2.5 bottom-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center"
                >
                  <svg
                    className="w-3 h-3"
                    aria-hidden="true"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 14 14"
                  >
                    <path
                      stroke="currentColor"
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      strokeWidth="2"
                      d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"
                    />
                  </svg>
                  <span className="sr-only">Clear search</span>
                </button>
              )}
            </div>
          </div>

          {/* Templates Table */}
          {isLoading && <TableLoader />}
          {error && <TableErrorComponent />}
          {!isLoading &&
            !error &&
            ((data?.data?.items.length || 0) > 0 ? (
              <DynamicTable 
                columns={columns} 
                data={data?.data?.items || []} 
                pageTitle="Text Message Templates" 
                isLoading={isLoading} 
              />
            ) : (
              <div className="text-center py-8">
                <p className="text-gray-500">No text message templates found</p>
              </div>
            ))}

          {(data?.data?.items.length || 0) > 0 && (
            <TablePagination
              page={page}
              totalPages={totalPages}
              totalCount={totalCount}
              setPage={setPage}
              pageSize={pageSize}
              setPageSize={setPageSize}
            />
          )}
        </div>

        <div className="flex items-center justify-end p-4 md:p-5 border-t border-gray-200 rounded-b">
          <button
            type="button"
            className="py-2.5 px-5 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:ring-gray-100"
            onClick={onClose}
          >
            Cancel
          </button>
        </div>
      </Modal>

      {/* Preview Modal */}
      {previewTemplate && (
        <div className="fixed inset-0 bg-black bg-opacity-50 z-[60] flex justify-center items-center p-4">
          <div className="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-hidden">
            <div className="flex justify-between items-center p-6 border-b border-gray-200">
              <h2 className="text-xl font-semibold text-gray-900">Preview: {previewTemplate.name}</h2>
              <button
                onClick={() => setPreviewTemplate(null)}
                className="p-1 rounded-md hover:bg-gray-100 transition-colors"
              >
                <Eye className="h-4 w-4 text-gray-500" />
              </button>
            </div>
            <div className="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
              <div className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Template Name:</label>
                  <div className="p-3 bg-gray-50 rounded-md">{previewTemplate.name}</div>
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Message:</label>
                  <div className="p-4 bg-gray-50 rounded-md">
                    <div className="whitespace-pre-wrap text-gray-900">{previewTemplate.message}</div>
                    <div className="mt-2 text-xs text-gray-500">Character count: {previewTemplate.message.length}</div>
                  </div>
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Sharing:</label>
                  <div className="p-3 bg-gray-50 rounded-md flex items-center space-x-2">
                    {previewTemplate.is_shared ? (
                      <>
                        <Share2 className="h-4 w-4 text-green-500" />
                        <span className="text-green-600">Shared with all users</span>
                      </>
                    ) : (
                      <>
                        <Lock className="h-4 w-4 text-gray-500" />
                        <span className="text-gray-600">Private template</span>
                      </>
                    )}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}
    </>
  )
}

export default TextMessageTemplateSelectionModal
