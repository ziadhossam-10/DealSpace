"use client"

import { useState } from "react"
import { ChevronDown, ChevronUp, Copy, Check } from "lucide-react"

interface FieldMappingHelperProps {
  isOpen: boolean
  onClose: () => void
}

const fieldExamples = {
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

export default function FieldMappingHelper({ isOpen, onClose }: FieldMappingHelperProps) {
  const [expandedFields, setExpandedFields] = useState<string[]>([])
  const [copiedField, setCopiedField] = useState("")

  const toggleField = (fieldName: string) => {
    setExpandedFields((prev) => (prev.includes(fieldName) ? prev.filter((f) => f !== fieldName) : [...prev, fieldName]))
  }

  const copyFieldName = async (fieldName: string) => {
    try {
      await navigator.clipboard.writeText(fieldName)
      setCopiedField(fieldName)
      setTimeout(() => setCopiedField(""), 2000)
    } catch (err) {
      console.error("Failed to copy field name:", err)
    }
  }

  if (!isOpen) return null

  return (
    <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
      <div className="flex justify-between items-center mb-3">
        <h4 className="text-sm font-medium text-blue-900">Field Mapping Reference</h4>
        <button onClick={onClose} className="text-blue-600 hover:text-blue-800 text-sm">
          Hide
        </button>
      </div>

      <p className="text-sm text-blue-700 mb-4">
        Common field names for each mapping type. Click to expand and see examples, or copy field names directly.
      </p>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
        {Object.entries(fieldExamples).map(([fieldType, examples]) => (
          <div key={fieldType} className="bg-white rounded border border-blue-200">
            <button
              onClick={() => toggleField(fieldType)}
              className="w-full flex items-center justify-between p-3 text-left hover:bg-blue-50 transition-colors"
            >
              <span className="font-medium text-gray-900 capitalize">{fieldType.replace("_", " ")}</span>
              {expandedFields.includes(fieldType) ? (
                <ChevronUp className="h-4 w-4 text-gray-500" />
              ) : (
                <ChevronDown className="h-4 w-4 text-gray-500" />
              )}
            </button>

            {expandedFields.includes(fieldType) && (
              <div className="px-3 pb-3 border-t border-blue-100">
                <div className="space-y-2 mt-2">
                  {examples.map((example, index) => (
                    <div key={index} className="flex items-center justify-between group">
                      <code className="text-xs bg-gray-100 px-2 py-1 rounded text-gray-800 flex-1">{example}</code>
                      <button
                        onClick={() => copyFieldName(example)}
                        className="ml-2 p-1 opacity-0 group-hover:opacity-100 hover:bg-gray-200 rounded transition-all"
                        title="Copy field name"
                      >
                        {copiedField === example ? (
                          <Check className="h-3 w-3 text-green-600" />
                        ) : (
                          <Copy className="h-3 w-3 text-gray-500" />
                        )}
                      </button>
                    </div>
                  ))}
                </div>
              </div>
            )}
          </div>
        ))}
      </div>

      <div className="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded">
        <p className="text-xs text-yellow-800">
          <strong>Tip:</strong> These are common field names found in web forms. You can add multiple field names for
          each mapping type to ensure your tracking script captures data regardless of how developers name their form
          fields.
        </p>
      </div>
    </div>
  )
}
