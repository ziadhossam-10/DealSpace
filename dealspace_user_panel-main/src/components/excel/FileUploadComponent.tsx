"use client"

import type React from "react"
import { useState, useRef } from "react"
import { toast } from "react-toastify"

interface FileUploadComponentProps {
  onUpload: (formData: FormData) => Promise<any>
  acceptedFileTypes?: string
  maxFileSize?: number // in bytes
  buttonText?: string
  className?: string
}

const FileUploadComponent: React.FC<FileUploadComponentProps> = ({
  onUpload,
  acceptedFileTypes = ".xlsx, .xls",
  maxFileSize = 5 * 1024 * 1024, // 5MB default
  buttonText = "Upload Excel",
  className = "bg-green-800 text-white px-4 py-1 rounded hover:bg-green-700",
}) => {
  const [isUploading, setIsUploading] = useState(false)
  const fileInputRef = useRef<HTMLInputElement>(null)

  const handleFileChange = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0]
    if (!file) return

    // Validate file size
    if (file.size > maxFileSize) {
      toast.error(`File size exceeds the limit of ${maxFileSize / (1024 * 1024)}MB`)
      return
    }

    // Validate file type
    const fileExtension = file.name.split(".").pop()?.toLowerCase()
    if (
      !fileExtension ||
      (!acceptedFileTypes.includes(`.${fileExtension}`) && !acceptedFileTypes.includes(fileExtension))
    ) {
      toast.error(`Invalid file type. Accepted types: ${acceptedFileTypes}`)
      return
    }

    try {
      setIsUploading(true)
      const formData = new FormData()
      formData.append("file", file)

      const response = await onUpload(formData)

      if (response?.data?.succeeded) {
        toast.success(response.data.messages?.[0] || "File uploaded successfully")
      } else {
        toast.error(response.data?.messages?.[0] || "Error uploading file")
      }
    } catch (error: any) {
      toast.error(error.data?.messages?.[0] || "An error occurred while uploading the file")
    } finally {
      setIsUploading(false)
      // Reset the file input
      if (fileInputRef.current) {
        fileInputRef.current.value = ""
      }
    }
  }

  const handleButtonClick = () => {
    fileInputRef.current?.click()
  }

  return (
    <div>
      <input type="file" ref={fileInputRef} onChange={handleFileChange} accept={acceptedFileTypes} className="hidden" />
      <button onClick={handleButtonClick} disabled={isUploading} className={className}>
        {isUploading ? "Uploading..." : buttonText}
      </button>
    </div>
  )
}

export default FileUploadComponent

