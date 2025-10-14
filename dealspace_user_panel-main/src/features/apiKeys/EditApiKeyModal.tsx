"use client"

import type React from "react"
import { useState, useEffect } from "react"
import { toast } from "react-toastify"
import { X, Plus, Trash2 } from "lucide-react"
import { useUpdateApiKeyMutation } from "./apiKeysApi"
import type { ApiKey, UpdateApiKeyRequest } from "../../types/apiKeys"

interface EditApiKeyModalProps {
  isOpen: boolean
  onClose: () => void
  apiKey: ApiKey | null
  onApiKeyUpdated: () => void
}

export default function EditApiKeyModal({ isOpen, onClose, apiKey, onApiKeyUpdated }: EditApiKeyModalProps) {
  const [updateApiKey, { isLoading }] = useUpdateApiKeyMutation()
  const [formData, setFormData] = useState<UpdateApiKeyRequest>({
    name: "",
    allowed_domains: [""],
  })

  const [errors, setErrors] = useState({
    name: "",
    allowed_domains: "",
  })

  useEffect(() => {
    if (isOpen && apiKey) {
      setFormData({
        name: apiKey.name,
        allowed_domains: apiKey.allowed_domains.length > 0 ? apiKey.allowed_domains : [""],
      })
      setErrors({ name: "", allowed_domains: "" })
    }
  }, [isOpen, apiKey])

  const handleChange = (field: string, value: any) => {
    setFormData((prev) => ({
      ...prev,
      [field]: value,
    }))
    if (field in errors) {
      setErrors((prev) => ({ ...prev, [field]: "" }))
    }
  }

  const handleDomainChange = (index: number, value: string) => {
    const newDomains = [...formData.allowed_domains]
    newDomains[index] = value
    handleChange("allowed_domains", newDomains)
  }

  const addDomain = () => {
    handleChange("allowed_domains", [...formData.allowed_domains, ""])
  }

  const removeDomain = (index: number) => {
    if (formData.allowed_domains.length > 1) {
      const newDomains = formData.allowed_domains.filter((_, i) => i !== index)
      handleChange("allowed_domains", newDomains)
    }
  }

  const validateForm = (): boolean => {
    let isValid = true
    const newErrors = { name: "", allowed_domains: "" }

    if (!formData.name.trim()) {
      newErrors.name = "API Key name is required"
      isValid = false
    }

    const validDomains = formData.allowed_domains.filter((domain) => domain.trim() !== "")
    if (validDomains.length === 0) {
      newErrors.allowed_domains = "At least one domain is required"
      isValid = false
    }

    setErrors(newErrors)
    return isValid
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!validateForm() || !apiKey) {
      return
    }

    try {
      const submitData = {
        id: apiKey.id,
        name: formData.name,
        allowed_domains: formData.allowed_domains.filter((domain) => domain.trim() !== ""),
      }
      await updateApiKey(submitData).unwrap()
      toast.success("API Key updated successfully!")
      onApiKeyUpdated()
      onClose()
    } catch (error: any) {
      console.error("Failed to update API key:", error)
      toast.error(error.data?.message || "Failed to update API key. Please try again.")
    }
  }

  if (!isOpen) return null

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 z-50 flex justify-center items-center p-4">
      <div className="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-hidden">
        <div className="flex justify-between items-center p-6 border-b border-gray-200">
          <h2 className="text-xl font-semibold text-gray-900">Edit API Key</h2>
          <button onClick={onClose} className="p-1 rounded-md hover:bg-gray-100 transition-colors">
            <X className="h-4 w-4 text-gray-500" />
          </button>
        </div>

        <form onSubmit={handleSubmit} className="p-6 space-y-6 overflow-y-auto max-h-[calc(90vh-120px)]">
          {/* API Key Name */}
          <div>
            <label htmlFor="name" className="block text-sm font-medium text-gray-700 mb-1">
              API Key Name*
            </label>
            <input
              id="name"
              type="text"
              value={formData.name}
              onChange={(e) => handleChange("name", e.target.value)}
              className={`w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 ${
                errors.name ? "border-red-500" : "border-gray-300"
              }`}
              placeholder="Enter API key name"
            />
            {errors.name && <p className="mt-1 text-sm text-red-500">{errors.name}</p>}
          </div>

          {/* Allowed Domains */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Allowed Domains*</label>
            <div className="space-y-2">
              {formData.allowed_domains.map((domain, index) => (
                <div key={index} className="flex items-center space-x-2">
                  <input
                    type="text"
                    value={domain}
                    onChange={(e) => handleDomainChange(index, e.target.value)}
                    className="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="example.com"
                  />
                  {formData.allowed_domains.length > 1 && (
                    <button
                      type="button"
                      onClick={() => removeDomain(index)}
                      className="p-2 text-red-600 hover:bg-red-50 rounded-md transition-colors"
                    >
                      <Trash2 className="h-4 w-4" />
                    </button>
                  )}
                </div>
              ))}
            </div>
            <button
              type="button"
              onClick={addDomain}
              className="mt-2 flex items-center text-sm text-blue-600 hover:text-blue-700"
            >
              <Plus className="h-4 w-4 mr-1" />
              Add Domain
            </button>
            {errors.allowed_domains && <p className="mt-1 text-sm text-red-500">{errors.allowed_domains}</p>}
          </div>

          {/* Form Actions */}
          <div className="flex justify-end space-x-3 pt-4 border-t border-gray-200">
            <button
              type="button"
              onClick={onClose}
              className="px-4 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={isLoading}
              className={`px-4 py-2 border border-transparent rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 ${
                isLoading ? "opacity-75 cursor-not-allowed" : ""
              }`}
            >
              {isLoading ? "Updating..." : "Update API Key"}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}
