"use client"

import type React from "react"
import { FileText, ImageIcon, Download, Trash2, Plus, Upload } from "lucide-react"

interface FileData {
  id: number
  name: string
  description: string | null
  size: number
  type: string
  path: string
}

interface PersonFilesSectionProps {
  files: FileData[]
  onAddFile: () => void
  onDeleteFile: (fileId: number) => void
  onDownloadFile: (file: FileData) => void
  isLoading?: boolean
}

export const PersonFilesSection: React.FC<PersonFilesSectionProps> = ({
  files,
  onAddFile,
  onDeleteFile,
  onDownloadFile,
  isLoading = false,
}) => {
  const formatFileSize = (bytes: number): string => {
    if (bytes === 0) return "0 Bytes"
    const k = 1024
    const sizes = ["Bytes", "KB", "MB", "GB"]
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    return Number.parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i]
  }

  const getFileIcon = (type: string) => {
    if (type.startsWith("image/")) {
      return <ImageIcon className="w-4 h-4 text-blue-500" />
    }
    return <FileText className="w-4 h-4 text-gray-500" />
  }

  const getFileTypeColor = (type: string) => {
    if (type.startsWith("image/")) return "bg-blue-100 text-blue-800"
    if (type.includes("pdf")) return "bg-red-100 text-red-800"
    if (type.includes("document") || type.includes("word")) return "bg-blue-100 text-blue-800"
    if (type.includes("spreadsheet") || type.includes("excel")) return "bg-green-100 text-green-800"
    return "bg-gray-100 text-gray-800"
  }

  if (isLoading) {
    return (
      <div className="p-6 border-b">
        <div className="flex items-center justify-between mb-4">
          <h3 className="text-lg font-semibold flex items-center gap-2">
            <FileText className="w-5 h-5" />
            Files
          </h3>
        </div>
        <div className="flex items-center justify-center py-8">
          <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        </div>
      </div>
    )
  }

  return (
    <div className="p-6 border-b">
      <div className="flex items-center justify-between mb-4">
        <h3 className="text-lg font-semibold flex items-center gap-2">
          <FileText className="w-5 h-5" />
          Files ({files.length})
        </h3>
        <button
          onClick={onAddFile}
          className="flex items-center gap-2 px-3 py-1.5 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
        >
          <Plus className="w-4 h-4" />
          Add File
        </button>
      </div>

      {files.length === 0 ? (
        <div className="text-center py-8">
          <Upload className="w-12 h-12 text-gray-400 mx-auto mb-4" />
          <p className="text-gray-500 mb-2">No files uploaded yet</p>
          <p className="text-sm text-gray-400 mb-4">Upload documents, images, and other files related to this person</p>
          <button
            onClick={onAddFile}
            className="flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors mx-auto"
          >
            <Plus className="w-4 h-4" />
            Upload First File
          </button>
        </div>
      ) : (
        <div className="space-y-3">
          {files.map((file) => (
            <div
              key={file.id}
              className="flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
            >
              <div className="flex items-center space-x-3 flex-1 min-w-0">
                {getFileIcon(file.type)}
                <div className="flex-1 min-w-0">
                  <div className="flex items-center gap-2 mb-1">
                    <p className="text-sm font-medium text-gray-900 truncate">{file.name}</p>
                    <span className={`text-xs px-2 py-1 rounded-full ${getFileTypeColor(file.type)}`}>
                      {file.type.split("/")[1]?.toUpperCase() || "FILE"}
                    </span>
                  </div>
                  <div className="flex items-center gap-4 text-xs text-gray-500">
                    <span>{formatFileSize(file.size)}</span>
                    {file.description && <span className="truncate max-w-xs">{file.description}</span>}
                  </div>
                </div>
              </div>
              <div className="flex items-center space-x-2 ml-4">
                <button
                  onClick={() => onDownloadFile(file)}
                  className="p-1.5 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded transition-colors"
                  title="Download file"
                >
                  <Download className="w-4 h-4" />
                </button>
                <button
                  onClick={() => onDeleteFile(file.id)}
                  className="p-1.5 text-red-600 hover:text-red-800 hover:bg-red-50 rounded transition-colors"
                  title="Delete file"
                >
                  <Trash2 className="w-4 h-4" />
                </button>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  )
}
