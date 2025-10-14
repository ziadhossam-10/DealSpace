"use client"

import type React from "react"

interface BulkDeleteModalProps {
  isOpen: boolean
  onClose: () => void
  onDelete: () => void
  isLoadingDelete: boolean
  count: number
}

const BulkDeleteModal: React.FC<BulkDeleteModalProps> = ({ isOpen, onClose, onDelete, isLoadingDelete, count }) => {
  if (!isOpen) return null

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
      <div className="bg-white p-6 rounded-lg shadow-lg max-w-md w-full">
        <h2 className="text-xl font-bold mb-4">Confirm Bulk Delete</h2>
        <p className="mb-6">
          Are you sure you want to delete {count} {count === 1 ? "person" : "people"}? This action cannot be undone.
        </p>
        <div className="flex justify-end gap-2">
          <button
            onClick={onClose}
            className="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300"
            disabled={isLoadingDelete}
          >
            Cancel
          </button>
          <button
            onClick={onDelete}
            className="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600"
            disabled={isLoadingDelete}
          >
            {isLoadingDelete ? "Deleting..." : "Delete"}
          </button>
        </div>
      </div>
    </div>
  )
}

export default BulkDeleteModal
