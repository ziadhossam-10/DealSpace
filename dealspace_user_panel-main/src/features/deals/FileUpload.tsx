"use client"

import type React from "react"
import { useCallback, useState } from "react"
import { Upload, X, File, Download, Trash2 } from "lucide-react"
import type { DealAttachment } from "../../types/deals"

interface FileUploadProps {
  files: File[]
  existingAttachments?: DealAttachment[]
  onFilesChange: (files: File[]) => void
  onAttachmentDelete?: (attachmentId: number) => void
  onAttachmentDownload?: (attachmentId: number) => void
  maxFiles?: number
  maxFileSize?: number // in MB
  acceptedFileTypes?: string[]
  disabled?: boolean
}

export default function FileUpload({
  files,
  existingAttachments = [],
  onFilesChange,
  onAttachmentDelete,
  onAttachmentDownload,
  maxFiles = 10,
  maxFileSize = 10, // 10MB default
  acceptedFileTypes = [".pdf", ".doc", ".docx", ".xls", ".xlsx", ".jpg", ".jpeg", ".png", ".gif"],
  disabled = false,
}: FileUploadProps) {
  const [dragActive, setDragActive] = useState(false)
  const [errors, setErrors] = useState<string[]>([])

  const validateFile = (file: File): string | null => {
    // Check file size
    if (file.size > maxFileSize * 1024 * 1024) {
      return `File "${file.name}" is too large. Maximum size is ${maxFileSize}MB.`
    }

    // Check file type
    const fileExtension = "." + file.name.split(".").pop()?.toLowerCase()
    if (acceptedFileTypes.length > 0 && !acceptedFileTypes.includes(fileExtension)) {
      return `File "${file.name}" has an unsupported file type. Accepted types: ${acceptedFileTypes.join(", ")}`
    }

    return null
  }

  const handleFiles = useCallback(
    (newFiles: FileList | File[]) => {
      const fileArray = Array.from(newFiles)
      const currentTotalFiles = files.length + existingAttachments.length

      if (currentTotalFiles + fileArray.length > maxFiles) {
        setErrors([`Maximum ${maxFiles} files allowed. You can upload ${maxFiles - currentTotalFiles} more files.`])
        return
      }

      const validFiles: File[] = []
      const newErrors: string[] = []

      fileArray.forEach((file) => {
        const error = validateFile(file)
        if (error) {
          newErrors.push(error)
        } else {
          // Check for duplicates
          const isDuplicate = files.some(
            (existingFile) => existingFile.name === file.name && existingFile.size === file.size,
          )
          if (!isDuplicate) {
            validFiles.push(file)
          } else {
            newErrors.push(`File "${file.name}" is already selected.`)
          }
        }
      })

      setErrors(newErrors)

      if (validFiles.length > 0) {
        onFilesChange([...files, ...validFiles])
      }
    },
    [files, existingAttachments.length, maxFiles, onFilesChange],
  )

  const handleDrag = useCallback((e: React.DragEvent) => {
    e.preventDefault()
    e.stopPropagation()
    if (e.type === "dragenter" || e.type === "dragover") {
      setDragActive(true)
    } else if (e.type === "dragleave") {
      setDragActive(false)
    }
  }, [])

  const handleDrop = useCallback(
    (e: React.DragEvent) => {
      e.preventDefault()
      e.stopPropagation()
      setDragActive(false)

      if (disabled) return

      if (e.dataTransfer.files && e.dataTransfer.files[0]) {
        handleFiles(e.dataTransfer.files)
      }
    },
    [handleFiles, disabled],
  )

  const handleFileInput = useCallback(
    (e: React.ChangeEvent<HTMLInputElement>) => {
      if (e.target.files && e.target.files[0]) {
        handleFiles(e.target.files)
      }
    },
    [handleFiles],
  )

  const removeFile = useCallback(
    (index: number) => {
      const newFiles = files.filter((_, i) => i !== index)
      onFilesChange(newFiles)
    },
    [files, onFilesChange],
  )

  const formatFileSize = (bytes: number): string => {
    if (bytes === 0) return "0 Bytes"
    const k = 1024
    const sizes = ["Bytes", "KB", "MB", "GB"]
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    return Number.parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i]
  }

  return (
    <div className="space-y-4">
      {/* Upload Area */}
      <div
        className={`relative border-2 border-dashed rounded-lg p-6 transition-colors ${
          dragActive ? "border-blue-400 bg-blue-50" : "border-gray-300 hover:border-gray-400"
        } ${disabled ? "opacity-50 cursor-not-allowed" : "cursor-pointer"}`}
        onDragEnter={handleDrag}
        onDragLeave={handleDrag}
        onDragOver={handleDrag}
        onDrop={handleDrop}
        onClick={() => !disabled && document.getElementById("file-upload")?.click()}
      >
        <input
          id="file-upload"
          type="file"
          multiple
          accept={acceptedFileTypes.join(",")}
          onChange={handleFileInput}
          className="hidden"
          disabled={disabled}
        />

        <div className="text-center">
          <Upload className="mx-auto h-12 w-12 text-gray-400" />
          <div className="mt-4">
            <p className="text-sm text-gray-600">
              <span className="font-medium text-blue-600 hover:text-blue-500">Click to upload</span> or drag and drop
            </p>
            <p className="text-xs text-gray-500 mt-1">
              {acceptedFileTypes.join(", ")} up to {maxFileSize}MB each
            </p>
            <p className="text-xs text-gray-500">
              Maximum {maxFiles} files ({files.length + existingAttachments.length}/{maxFiles} used)
            </p>
          </div>
        </div>
      </div>

      {/* Error Messages */}
      {errors.length > 0 && (
        <div className="bg-red-50 border border-red-200 rounded-md p-3">
          <div className="text-sm text-red-600">
            {errors.map((error, index) => (
              <div key={index}>{error}</div>
            ))}
          </div>
        </div>
      )}

      {/* Existing Attachments */}
      {existingAttachments.length > 0 && (
        <div className="space-y-2">
          <h4 className="text-sm font-medium text-gray-700">Current Attachments</h4>
          {existingAttachments.map((attachment) => (
            <div key={attachment.id} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg border">
              <div className="flex items-center space-x-3">
                <File className="h-5 w-5 text-gray-400" />
                <div>
                  <p className="text-sm font-medium text-gray-900">{attachment.name}</p>
                  <p className="text-xs text-gray-500">{formatFileSize(attachment.size)}</p>
                </div>
              </div>
              <div className="flex items-center space-x-2">
                {onAttachmentDownload && (
                  <button
                    type="button"
                    onClick={() => onAttachmentDownload(attachment.id)}
                    className="p-1 text-gray-400 hover:text-blue-600 transition-colors"
                    title="Download"
                  >
                    <Download className="h-4 w-4" />
                  </button>
                )}
                {onAttachmentDelete && (
                  <button
                    type="button"
                    onClick={() => onAttachmentDelete(attachment.id)}
                    className="p-1 text-gray-400 hover:text-red-600 transition-colors"
                    title="Delete"
                  >
                    <Trash2 className="h-4 w-4" />
                  </button>
                )}
              </div>
            </div>
          ))}
        </div>
      )}

      {/* New Files List */}
      {files.length > 0 && (
        <div className="space-y-2">
          <h4 className="text-sm font-medium text-gray-700">New Files to Upload</h4>
          {files.map((file, index) => (
            <div
              key={index}
              className="flex items-center justify-between p-3 bg-blue-50 rounded-lg border border-blue-200"
            >
              <div className="flex items-center space-x-3">
                <File className="h-5 w-5 text-blue-500" />
                <div>
                  <p className="text-sm font-medium text-gray-900">{file.name}</p>
                  <p className="text-xs text-gray-500">{formatFileSize(file.size)}</p>
                </div>
              </div>
              <button
                type="button"
                onClick={() => removeFile(index)}
                className="p-1 text-gray-400 hover:text-red-600 transition-colors"
                title="Remove"
              >
                <X className="h-4 w-4" />
              </button>
            </div>
          ))}
        </div>
      )}
    </div>
  )
}
