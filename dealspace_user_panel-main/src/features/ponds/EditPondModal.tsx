"use client"

import type React from "react"
import { useState, useEffect, useRef } from "react"
import { useUpdatePondMutation } from "../ponds/pondsApi"
import { useGetUsersQuery } from "../users/usersApi"
import { Pond, UpdatePondRequest } from "../../types/ponds"
import { X } from "lucide-react"

interface EditPondModalProps {
  isOpen: boolean
  onClose: () => void
  pond: Pond | null
  onPondUpdated: () => void
}

const EditPondModal: React.FC<EditPondModalProps> = ({ isOpen, onClose, pond, onPondUpdated }) => {
  const [updatePond, { isLoading: isUpdating }] = useUpdatePondMutation()

  const [formData, setFormData] = useState<UpdatePondRequest>({
    name: "",
    user_id: 0,
  })

  const [ownerSearchTerm, setOwnerSearchTerm] = useState("")
  const [showOwnerSearch, setShowOwnerSearch] = useState(false)
  const [selectedOwner, setSelectedOwner] = useState<{ id: number; name: string; email: string } | null>(null)

  const { data: ownerSearchData, isLoading: isLoadingOwnerSearch } = useGetUsersQuery(
    { search: ownerSearchTerm, page: 1, per_page: 50 },
    { skip: !showOwnerSearch },
  )

  const { data: allUsersData, isLoading: isLoadingAllUsers } = useGetUsersQuery(
    { page: 1, per_page: 1000 }, // Fetch a large number to get all users
    { skip: !isOpen },
  )

  const [errors, setErrors] = useState({
    name: "",
    user_id: "",
  })

  useEffect(() => {
    if (isOpen && pond) {
      setFormData({
        name: pond.name,
        user_id: pond.user_id || 0,
      })

      // Set selected owner if pond has an owner
      if (pond.user) {
        setSelectedOwner({
          id: pond.user.id,
          name: pond.user.name,
          email: pond.user.email,
        })
      } else {
        setSelectedOwner(null)
      }

      setErrors({ name: "", user_id: "" })
      setOwnerSearchTerm("")
      setShowOwnerSearch(false)
    }
  }, [isOpen, pond])

  const handleChange = (key: keyof UpdatePondRequest, value: any) => {
    setFormData({ ...formData, [key]: value })
  }

  const validateForm = (): boolean => {
    let isValid = true
    const newErrors = { name: "", user_id: "" }

    if (!formData.name?.trim()) {
      newErrors.name = "Pond name is required"
      isValid = false
    }

    if (!selectedOwner) {
      newErrors.user_id = "Owner is required"
      isValid = false
    }

    setErrors(newErrors)
    return isValid
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()

    if (!validateForm()) {
      return
    }

    if (!pond) {
      return
    }

    try {
      await updatePond({ id: pond.id, ...formData, user_id: selectedOwner?.id }).unwrap()
      onPondUpdated()
      onClose()
    } catch (error) {
      console.error("Failed to update pond:", error)
    }
  }

  const ownerSearchRef = useRef<HTMLDivElement>(null)

  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (ownerSearchRef.current && !ownerSearchRef.current.contains(event.target as Node)) {
        setShowOwnerSearch(false)
      }
    }

    document.addEventListener("mousedown", handleClickOutside)
    return () => {
      document.removeEventListener("mousedown", handleClickOutside)
    }
  }, [])

  if (!isOpen) return null

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 z-50 flex justify-center items-center p-4">
      <div className="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-hidden">
        <div className="flex justify-between items-center p-6 border-b border-gray-200">
          <h2 className="text-xl font-semibold text-gray-900">Create New Pond</h2>
          <button onClick={onClose} className="p-1 rounded-md hover:bg-gray-100 transition-colors">
            <X className="h-4 w-4 text-gray-500" />
          </button>
        </div>
        <form onSubmit={handleSubmit} className="p-6 space-y-6 overflow-y-auto max-h-[calc(90vh-120px)]">
          {/* Pond Name */}
          <div>
            <label htmlFor="name" className="block text-sm font-medium text-gray-700 mb-1">
              Pond Name*
            </label>
            <input
              type="text"
              id="name"
              value={formData.name || ""}
              onChange={(e) => handleChange("name", e.target.value)}
              className={`w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 ${
                errors.name ? "border-red-500" : "border-gray-300"
              }`}
            />
            {errors.name && <p className="mt-1 text-sm text-red-500">{errors.name}</p>}
          </div>

          {/* Owner */}
          <div>
            <label htmlFor="owner" className="block text-sm font-medium text-gray-700 mb-1">
              Owner*
            </label>
            <div ref={ownerSearchRef} className="relative">
              <input
                type="text"
                id="owner"
                placeholder={selectedOwner ? `${selectedOwner.name} (${selectedOwner.email})` : "Search for owner"}
                onFocus={() => setShowOwnerSearch(true)}
                onChange={(e) => setOwnerSearchTerm(e.target.value)}
                className={`w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 ${
                  errors.user_id ? "border-red-500" : "border-gray-300"
                }`}
                value={selectedOwner ? `${selectedOwner.name} (${selectedOwner.email})` : ownerSearchTerm}
                onClick={() => setShowOwnerSearch(true)}
              />
              {showOwnerSearch && (
                <div className="absolute z-10 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg">
                  {isLoadingOwnerSearch ? (
                    <div className="px-4 py-2 text-gray-500">Loading...</div>
                  ) : ownerSearchData?.data?.items?.length === 0 ? (
                    <div className="px-4 py-2 text-gray-500">No users found.</div>
                  ) : (
                    ownerSearchData?.data?.items?.map((user: any) => (
                      <div
                        key={user.id}
                        className="px-4 py-2 hover:bg-gray-100 cursor-pointer"
                        onClick={() => {
                          setSelectedOwner({ id: user.id, name: user.name, email: user.email })
                          setFormData({ ...formData, user_id: user.id })
                          setShowOwnerSearch(false)
                        }}
                      >
                        {user.name} ({user.email})
                      </div>
                    ))
                  )}
                </div>
              )}
            </div>
            {errors.user_id && <p className="mt-1 text-sm text-red-500">{errors.user_id}</p>}
          </div>

          {/* Submit Button */}
          <div className="flex justify-end">
            <button
              type="submit"
              className="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-blue-300"
              disabled={isUpdating || isLoadingAllUsers}
            >
              {isUpdating ? "Updating..." : "Update Pond"}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}


export default EditPondModal;