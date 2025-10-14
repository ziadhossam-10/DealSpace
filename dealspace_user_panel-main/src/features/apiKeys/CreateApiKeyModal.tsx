"use client"

import type React from "react"
import { useState, useEffect } from "react"
import { toast } from "react-toastify"
import { X, Plus, Trash2 } from "lucide-react"
import { useCreateApiKeyMutation } from "./apiKeysApi"
import type { CreateApiKeyRequest } from "../../types/apiKeys"

interface CreateApiKeyModalProps {
  isOpen: boolean
  onClose: () => void
  onSuccess: () => void
  onApiKeyCreated: (apiKey: string) => void
}

export default function CreateApiKeyModal({ isOpen, onClose, onSuccess, onApiKeyCreated }: CreateApiKeyModalProps) {
  const [createApiKey, { isLoading }] = useCreateApiKeyMutation()
  const [formData, setFormData] = useState<CreateApiKeyRequest>({
    name: "",
    allowed_domains: [""],
  })

  const [errors, setErrors] = useState({
    name: "",
    allowed_domains: "",
  })

  const [enableDomainRestrictions, setEnableDomainRestrictions] = useState(false)

  useEffect(() => {
    if (!isOpen) {
      setFormData({
        name: "",
        allowed_domains: [""],
      })
      setEnableDomainRestrictions(false)
      setErrors({ name: "", allowed_domains: "" })
    }
  }, [isOpen])

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

    if (enableDomainRestrictions) {
      const validDomains = formData.allowed_domains.filter((domain) => domain.trim() !== "")
      if (validDomains.length === 0) {
        newErrors.allowed_domains = "At least one domain is required when restrictions are enabled"
        isValid = false
      }
    }

    setErrors(newErrors)
    return isValid
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!validateForm()) {
      return
    }

    try {
      const submitData = {
        ...formData,
        allowed_domains: enableDomainRestrictions
          ? formData.allowed_domains.filter((domain) => domain.trim() !== "")
          : [],
      }
      const response = await createApiKey(submitData).unwrap()

      if (response.status && response.data.key) {
        toast.success("API Key created successfully!")
        onApiKeyCreated(response.data.key)
        onSuccess()
        onClose()
      }
    } catch (error: any) {
      console.error("Failed to create API key:", error)
      toast.error(error.data?.message || "Failed to create API key. Please try again.")
    }
  }

  if (!isOpen) return null

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 z-50 flex justify-center items-center p-4">
      <div className="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-hidden">
        <div className="flex justify-between items-center p-6 border-b border-gray-200">
          <h2 className="text-xl font-semibold text-gray-900">Create New API Key</h2>
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

          {/* Domain Restrictions Toggle */}
          <div>
            <div className="flex items-center space-x-3">
              <input
                id="enableDomainRestrictions"
                type="checkbox"
                checked={enableDomainRestrictions}
                onChange={(e) => {
                  setEnableDomainRestrictions(e.target.checked)
                  if (!e.target.checked) {
                    handleChange("allowed_domains", [""])
                  }
                }}
                className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
              />
              <label htmlFor="enableDomainRestrictions" className="text-sm font-medium text-gray-700">
                Enable Domain Restrictions
              </label>
            </div>
            <p className="mt-1 text-sm text-gray-500">
              When enabled, this API key will only work from the specified domains. Leave unchecked to allow usage from
              any domain.
            </p>
          </div>

          {/* Allowed Domains */}
          {enableDomainRestrictions && (
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
          )}

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
              {isLoading ? "Creating..." : "Create API Key"}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}
