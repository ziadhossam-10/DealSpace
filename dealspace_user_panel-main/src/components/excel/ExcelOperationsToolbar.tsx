import type React from "react"
import FileUploadComponent from "./FileUploadComponent"
import FileDownloadComponent from "./FileDownloadComponent"

interface ExcelOperationsToolbarProps {
  onUpload: (formData: FormData) => Promise<any>
  onDownloadTemplate: () => Promise<Blob>
  onExport: () => Promise<Blob>
  moduleNameSingular: string
  moduleNamePlural: string
  className?: string
}

const ExcelOperationsToolbar: React.FC<ExcelOperationsToolbarProps> = ({
  onUpload,
  onDownloadTemplate,
  onExport,
  moduleNameSingular,
  moduleNamePlural,
  className = "flex gap-2",
}) => {
  return (
    <div className={className}>
      <FileUploadComponent onUpload={onUpload} buttonText={`Upload ${moduleNamePlural}`} />
      <FileDownloadComponent
        onDownload={onExport}
        filename={`${moduleNamePlural}.xlsx`}
        buttonText={`Export ${moduleNamePlural}`}
        className="hover:bg-blue-700 bg-blue-800 text-white px-4 py-1 rounded"
      />
      <FileDownloadComponent
        onDownload={onDownloadTemplate}
        filename={`Empty${moduleNamePlural}.xlsx`}
        buttonText={`Download Template`}
        className="hover:bg-gray-600 bg-gray-700 text-white px-4 py-1 rounded"
      />
    </div>
  )
}

export default ExcelOperationsToolbar

