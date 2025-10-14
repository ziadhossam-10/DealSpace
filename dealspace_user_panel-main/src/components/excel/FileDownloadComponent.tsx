"use client"

import type React from "react"
import { useState } from "react"
import { toast } from "react-toastify"

interface FileDownloadComponentProps {
  onDownload: () => Promise<Blob>
  filename: string
  buttonText: string
  className?: string
}

const FileDownloadComponent: React.FC<FileDownloadComponentProps> = ({
  onDownload,
  filename,
  buttonText,
  className = "text-white px-4 py-1 rounded",
}) => {
  const [isDownloading, setIsDownloading] = useState(false)

  const handleDownload = async () => {
    try {
      setIsDownloading(true)
      const blob = await onDownload()

      // Create a URL for the blob
      const url = window.URL.createObjectURL(blob)

      // Create a temporary anchor element
      const a = document.createElement("a")
      a.href = url
      a.download = filename
      document.body.appendChild(a)

      // Trigger the download
      a.click()

      // Clean up
      window.URL.revokeObjectURL(url)
      document.body.removeChild(a)

      toast.success("File downloaded successfully")
    } catch (error) {
      toast.error("Error downloading file")
    } finally {
      setIsDownloading(false)
    }
  }

  return (
    <button onClick={handleDownload} disabled={isDownloading} className={className}>
      {isDownloading ? "Downloading..." : buttonText}
    </button>
  )
}

export default FileDownloadComponent

