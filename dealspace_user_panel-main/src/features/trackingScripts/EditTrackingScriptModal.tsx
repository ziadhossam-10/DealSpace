"use client"

import type React from "react"
import { useState, useEffect } from "react"
import { toast } from "react-toastify"
import { X, Plus, Trash2 } from "lucide-react"
import { useUpdateTrackingScriptMutation, useGetTrackingScriptByIdQuery } from "./trackingScriptsApi"
import type { UpdateTrackingScriptRequest } from "../../types/trackingScripts"
import FieldMappingHelper from "./FieldMappingHelper"

interface EditTrackingScriptModalProps {
  isOpen: boolean
  onClose: () => void
  scriptId: number | null
  onScriptUpdated: () => void
}

export default function EditTrackingScriptModal({
  isOpen,
  onClose,
  scriptId,
  onScriptUpdated,
}: EditTrackingScriptModalProps) {
  const [updateScript, { isLoading }] = useUpdateTrackingScriptMutation()

  // Fetch script data by ID
  const { data: scriptData, isLoading: isLoadingScript } = useGetTrackingScriptByIdQuery(scriptId || 0, {
    skip: !scriptId || !isOpen,
  })

  const script = scriptData?.data

  const [formData, setFormData] = useState<UpdateTrackingScriptRequest>({
    id: 0,
    name: "",
    description: "",
    domain: [""],
    track_all_forms: false,
    form_selectors: [""],
    field_mappings: {
      name: [""],
      first_name: [""],
      last_name: [""],
      email: [""],
      phone: [""],
      message: [""],
      company: [""],
      property_interest: [""],
      budget: [""],
    },
    auto_lead_capture: true,
    track_page_views: true,
    track_utm_parameters: true,
    custom_events: [""],
  })

  const [errors, setErrors] = useState({
    name: "",
    description: "",
    domain: "",
  })

  const [showFieldHelper, setShowFieldHelper] = useState(false)
  const [enableDomainRestrictions, setEnableDomainRestrictions] = useState(false)

  useEffect(() => {
    if (isOpen && script) {
      const hasExistingDomains = script.domain && script.domain.length > 0
      setEnableDomainRestrictions(hasExistingDomains)

      const getDefaultOrExisting = (scriptValues: string[] | undefined, defaultValues: string[]) => {
        return scriptValues && scriptValues.length > 0 ? scriptValues : defaultValues
      }

      setFormData({
        id: script.id,
        name: script.name,
        description: script.description,
        domain: script.domain.length > 0 ? script.domain : [""],
        track_all_forms: script.track_all_forms,
        form_selectors: script.form_selectors.length > 0 ? script.form_selectors : [""],
        field_mappings: {
          name: script.field_mappings.name?.length > 0 ? script.field_mappings.name : [""],
          first_name: script.field_mappings.first_name?.length > 0 ? script.field_mappings.first_name : [""],
          last_name: script.field_mappings.last_name?.length > 0 ? script.field_mappings.last_name : [""],
          email: script.field_mappings.email?.length > 0 ? script.field_mappings.email : [""],
          phone: script.field_mappings.phone?.length > 0 ? script.field_mappings.phone : [""],
          message: script.field_mappings.message?.length > 0 ? script.field_mappings.message : [""],
          company: script.field_mappings.company?.length > 0 ? script.field_mappings.company : [""],
          property_interest:
            script.field_mappings.property_interest?.length > 0 ? script.field_mappings.property_interest : [""],
          budget: script.field_mappings.budget?.length > 0 ? script.field_mappings.budget : [""],
        },
        auto_lead_capture: script.auto_lead_capture,
        track_page_views: script.track_page_views,
        track_utm_parameters: script.track_utm_parameters,
        custom_events: script.custom_events.length > 0 ? script.custom_events : [""],
      })
      setErrors({ name: "", description: "", domain: "" })
    }
  }, [isOpen, script])

  const getFieldExamples = (fieldType: string): string[] => {
    const examples = {
      name: ["name", "full_name", "fullname", "person_name", "customer_name"],
      first_name: ["first_name", "firstname", "fname", "given_name"],
      last_name: ["last_name", "lastname", "lname", "family_name", "surname"],
      email: ["email", "email_address", "e_mail", "user_email", "contact_email"],
      phone: ["phone", "telephone", "mobile", "phone_number", "contact_phone"],
      message: ["message", "comment", "inquiry", "description", "notes"],
      company: ["company", "organization", "business", "company_name"],
      property_interest: ["property", "property_id", "listing", "property_interest"],
      budget: ["budget", "price_range", "max_price", "price_limit"],
    }
    return examples[fieldType as keyof typeof examples] || []
  }

  const handleChange = (field: string, value: any) => {
    setFormData((prev) => ({
      ...prev,
      [field]: value,
    }))
    if (field in errors) {
      setErrors((prev) => ({ ...prev, [field]: "" }))
    }
  }

  const handleArrayChange = (field: string, index: number, value: string) => {
    const newArray = [...(formData[field as keyof UpdateTrackingScriptRequest] as string[])]
    newArray[index] = value
    handleChange(field, newArray)
  }

  const addArrayItem = (field: string) => {
    const currentArray = formData[field as keyof UpdateTrackingScriptRequest] as string[]
    handleChange(field, [...currentArray, ""])
  }

  const removeArrayItem = (field: string, index: number) => {
    const currentArray = formData[field as keyof UpdateTrackingScriptRequest] as string[]
    if (currentArray.length > 1) {
      const newArray = currentArray.filter((_, i) => i !== index)
      handleChange(field, newArray)
    }
  }

  const handleFieldMappingChange = (mappingKey: string, index: number, value: string) => {
    const newMappings = { ...formData.field_mappings }
    newMappings[mappingKey][index] = value
    handleChange("field_mappings", newMappings)
  }

  const addFieldMapping = (mappingKey: string) => {
    const newMappings = { ...formData.field_mappings }
    newMappings[mappingKey] = [...newMappings[mappingKey], ""]
    handleChange("field_mappings", newMappings)
  }

  const removeFieldMapping = (mappingKey: string, index: number) => {
    const newMappings = { ...formData.field_mappings }
    if (newMappings[mappingKey].length > 1) {
      newMappings[mappingKey] = newMappings[mappingKey].filter((_, i) => i !== index)
      handleChange("field_mappings", newMappings)
    }
  }

  const validateForm = (): boolean => {
    let isValid = true
    const newErrors = { name: "", description: "", domain: "" }

    if (!formData.name.trim()) {
      newErrors.name = "Script name is required"
      isValid = false
    }

    if (!formData.description.trim()) {
      newErrors.description = "Description is required"
      isValid = false
    }

    if (enableDomainRestrictions) {
      const validDomains = formData.domain.filter((domain) => domain.trim() !== "")
      if (validDomains.length === 0) {
        newErrors.domain = "At least one domain is required when restrictions are enabled"
        isValid = false
      }
    }

    setErrors(newErrors)
    return isValid
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    if (!validateForm() || !script) {
      return
    }

    try {
      const submitData = {
        id: script.id,
        name: formData.name,
        description: formData.description,
        domain: formData.domain.filter((domain) => domain.trim() !== ""),
        track_all_forms: formData.track_all_forms,
        form_selectors: formData.form_selectors.filter((selector) => selector.trim() !== ""),
        field_mappings: Object.fromEntries(
          Object.entries(formData.field_mappings).map(([key, values]) => [
            key,
            values.filter((value) => value.trim() !== ""),
          ]),
        ),
        auto_lead_capture: formData.auto_lead_capture,
        track_page_views: formData.track_page_views,
        track_utm_parameters: formData.track_utm_parameters,
        custom_events: formData.custom_events.filter((event) => event.trim() !== ""),
      }

      await updateScript(submitData).unwrap()
      toast.success("Tracking script updated successfully!")
      onScriptUpdated()
      onClose()
    } catch (error: any) {
      console.error("Failed to update tracking script:", error)
      toast.error(error.data?.message || "Failed to update tracking script. Please try again.")
    }
  }

  if (!isOpen) return null

  if (isLoadingScript) {
    return (
      <div className="fixed inset-0 bg-black bg-opacity-50 z-50 flex justify-center items-center p-4">
        <div className="bg-white rounded-lg shadow-xl w-full max-w-4xl p-6">
          <div className="flex items-center justify-center h-32">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <span className="ml-2 text-gray-600">Loading script data...</span>
          </div>
        </div>
      </div>
    )
  }

  if (!script) {
    return (
      <div className="fixed inset-0 bg-black bg-opacity-50 z-50 flex justify-center items-center p-4">
        <div className="bg-white rounded-lg shadow-xl w-full max-w-4xl p-6">
          <div className="text-center text-red-600">Script not found</div>
        </div>
      </div>
    )
  }

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 z-50 flex justify-center items-center p-4">
      <div className="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-hidden">
        <div className="flex justify-between items-center p-6 border-b border-gray-200">
          <h2 className="text-xl font-semibold text-gray-900">Edit Tracking Script</h2>
          <button onClick={onClose} className="p-1 rounded-md hover:bg-gray-100 transition-colors">
            <X className="h-4 w-4 text-gray-500" />
          </button>
        </div>

        <form onSubmit={handleSubmit} className="p-6 space-y-6 overflow-y-auto max-h-[calc(90vh-120px)]">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            {/* Basic Information */}
            <div className="space-y-4">
              <h3 className="text-lg font-medium text-gray-900">Basic Information</h3>

              <div>
                <label htmlFor="name" className="block text-sm font-medium text-gray-700 mb-1">
                  Script Name*
                </label>
                <input
                  id="name"
                  type="text"
                  value={formData.name}
                  onChange={(e) => handleChange("name", e.target.value)}
                  className={`w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 ${
                    errors.name ? "border-red-500" : "border-gray-300"
                  }`}
                  placeholder="Enter script name"
                />
                {errors.name && <p className="mt-1 text-sm text-red-500">{errors.name}</p>}
              </div>

              <div>
                <label htmlFor="description" className="block text-sm font-medium text-gray-700 mb-1">
                  Description*
                </label>
                <textarea
                  id="description"
                  value={formData.description}
                  onChange={(e) => handleChange("description", e.target.value)}
                  className={`w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 ${
                    errors.description ? "border-red-500" : "border-gray-300"
                  }`}
                  placeholder="Enter script description"
                  rows={3}
                />
                {errors.description && <p className="mt-1 text-sm text-red-500">{errors.description}</p>}
              </div>

              {/* Domains */}
              <div>
                <div className="flex items-center space-x-3 mb-3">
                  <input
                    id="enableDomainRestrictions"
                    type="checkbox"
                    checked={enableDomainRestrictions}
                    onChange={(e) => {
                      setEnableDomainRestrictions(e.target.checked)
                      if (!e.target.checked) {
                        handleChange("domain", [])
                      } else {
                        handleChange("domain", [""])
                      }
                    }}
                    className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                  />
                  <label htmlFor="enableDomainRestrictions" className="text-sm font-medium text-gray-700">
                    Enable Domain Restrictions
                  </label>
                </div>
                <p className="text-sm text-gray-500 mb-3">
                  When enabled, this tracking script will only work from the specified domains. Leave unchecked to allow
                  usage from any domain.
                </p>

                {enableDomainRestrictions && (
                  <>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Allowed Domains*</label>
                    <div className="space-y-2">
                      {formData.domain.map((domain, index) => (
                        <div key={index} className="flex items-center space-x-2">
                          <input
                            type="text"
                            value={domain}
                            onChange={(e) => handleArrayChange("domain", index, e.target.value)}
                            className="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="example.com"
                          />
                          {formData.domain.length > 1 && (
                            <button
                              type="button"
                              onClick={() => removeArrayItem("domain", index)}
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
                      onClick={() => addArrayItem("domain")}
                      className="mt-2 flex items-center text-sm text-blue-600 hover:text-blue-700"
                    >
                      <Plus className="h-4 w-4 mr-1" />
                      Add Domain
                    </button>
                    {errors.domain && <p className="mt-1 text-sm text-red-500">{errors.domain}</p>}
                  </>
                )}
              </div>
            </div>

            {/* Tracking Options */}
            <div className="space-y-4">
              <h3 className="text-lg font-medium text-gray-900">Tracking Options</h3>

              <div className="space-y-3">
                <div className="flex items-center space-x-3">
                  <input
                    id="track_page_views"
                    type="checkbox"
                    checked={formData.track_page_views}
                    onChange={(e) => handleChange("track_page_views", e.target.checked)}
                    className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                  />
                  <label htmlFor="track_page_views" className="text-sm font-medium text-gray-700">
                    Track Page Views
                  </label>
                </div>

                <div className="flex items-center space-x-3">
                  <input
                    id="auto_lead_capture"
                    type="checkbox"
                    checked={formData.auto_lead_capture}
                    onChange={(e) => handleChange("auto_lead_capture", e.target.checked)}
                    className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                  />
                  <label htmlFor="auto_lead_capture" className="text-sm font-medium text-gray-700">
                    Auto Lead Capture
                  </label>
                </div>

                <div className="flex items-center space-x-3">
                  <input
                    id="track_utm_parameters"
                    type="checkbox"
                    checked={formData.track_utm_parameters}
                    onChange={(e) => handleChange("track_utm_parameters", e.target.checked)}
                    className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                  />
                  <label htmlFor="track_utm_parameters" className="text-sm font-medium text-gray-700">
                    Track UTM Parameters
                  </label>
                </div>

                <div className="flex items-center space-x-3">
                  <input
                    id="track_all_forms"
                    type="checkbox"
                    checked={formData.track_all_forms}
                    onChange={(e) => handleChange("track_all_forms", e.target.checked)}
                    className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                  />
                  <label htmlFor="track_all_forms" className="text-sm font-medium text-gray-700">
                    Track All Forms
                  </label>
                </div>
              </div>

              {/* Custom Events */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Custom Events</label>
                <div className="space-y-2">
                  {formData.custom_events.map((event, index) => (
                    <div key={index} className="flex items-center space-x-2">
                      <input
                        type="text"
                        value={event}
                        onChange={(e) => handleArrayChange("custom_events", index, e.target.value)}
                        className="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Event name"
                      />
                      {formData.custom_events.length > 1 && (
                        <button
                          type="button"
                          onClick={() => removeArrayItem("custom_events", index)}
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
                  onClick={() => addArrayItem("custom_events")}
                  className="mt-2 flex items-center text-sm text-blue-600 hover:text-blue-700"
                >
                  <Plus className="h-4 w-4 mr-1" />
                  Add Event
                </button>
              </div>
            </div>
          </div>

          {/* Form Configuration */}
          <div className="space-y-4">
            <h3 className="text-lg font-medium text-gray-900">Form Configuration</h3>

            {/* Form Selectors - Only show when track_all_forms is false */}
            {!formData.track_all_forms && (
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Form Selectors</label>
                <div className="space-y-2">
                  {formData.form_selectors.map((selector, index) => (
                    <div key={index} className="flex items-center space-x-2">
                      <input
                        type="text"
                        value={selector}
                        onChange={(e) => handleArrayChange("form_selectors", index, e.target.value)}
                        className="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="#contact-form, .lead-form"
                      />
                      {formData.form_selectors.length > 1 && (
                        <button
                          type="button"
                          onClick={() => removeArrayItem("form_selectors", index)}
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
                  onClick={() => addArrayItem("form_selectors")}
                  className="mt-2 flex items-center text-sm text-blue-600 hover:text-blue-700"
                >
                  <Plus className="h-4 w-4 mr-1" />
                  Add Selector
                </button>
              </div>
            )}

            {/* Field Mapping Helper */}
            <FieldMappingHelper isOpen={showFieldHelper} onClose={() => setShowFieldHelper(false)} />

            {/* Field Mappings - Always show regardless of track_all_forms */}
            <div>
              <div className="flex items-center justify-between mb-3">
                <label className="block text-sm font-medium text-gray-700">Field Mappings</label>
                <button
                  type="button"
                  onClick={() => setShowFieldHelper(!showFieldHelper)}
                  className="text-sm text-blue-600 hover:text-blue-700"
                >
                  {showFieldHelper ? "Hide" : "Show"} Field Reference
                </button>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                {Object.entries(formData.field_mappings).map(([mappingKey, mappingValues]) => (
                  <div key={mappingKey}>
                    <div className="flex items-center mb-1">
                      <label className="block text-sm font-medium text-gray-700 capitalize">
                        {mappingKey.replace("_", " ")} Fields
                      </label>
                      <div className="ml-2 group relative">
                        <svg className="h-4 w-4 text-blue-500 cursor-help" fill="currentColor" viewBox="0 0 20 20">
                          <path
                            fillRule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                            clipRule="evenodd"
                          />
                        </svg>
                        <div className="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap z-10 max-w-xs">
                          <div className="text-center">
                            <div className="font-medium mb-1">Backend automatically maps:</div>
                            <div className="text-gray-300">{getFieldExamples(mappingKey).join(", ")}</div>
                            <div className="mt-1 text-gray-400">Add additional field names if needed</div>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div className="space-y-2">
                      {mappingValues.map((value, index) => (
                        <div key={index} className="flex items-center space-x-2">
                          <input
                            type="text"
                            value={value}
                            onChange={(e) => handleFieldMappingChange(mappingKey, index, e.target.value)}
                            className="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Additional field name (optional)"
                          />
                          {mappingValues.length > 1 && (
                            <button
                              type="button"
                              onClick={() => removeFieldMapping(mappingKey, index)}
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
                      onClick={() => addFieldMapping(mappingKey)}
                      className="mt-2 flex items-center text-sm text-blue-600 hover:text-blue-700"
                    >
                      <Plus className="h-4 w-4 mr-1" />
                      Add Additional Field
                    </button>
                  </div>
                ))}
              </div>
            </div>
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
              {isLoading ? "Updating..." : "Update Tracking Script"}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}
