"use client"
import { Edit } from "lucide-react"
import { Link } from "react-router"

interface PersonCustomFieldsSectionProps {
  customFieldsWithValues: any[]
  onEditCustomFields: () => void
}

export const PersonCustomFieldsSection = ({
  customFieldsWithValues,
  onEditCustomFields,
}: PersonCustomFieldsSectionProps) => {
  return (
    <div className="p-4">
      <div className="flex justify-between items-center mb-2">
        <h3 className="text-xs font-semibold text-gray-500">CUSTOM FIELDS</h3>
        {customFieldsWithValues.length > 0 && (
          <button onClick={onEditCustomFields} className="text-blue-500 hover:text-blue-700">
            <Edit size={14} />
          </button>
        )}
      </div>
      <div className="space-y-2">
        {customFieldsWithValues.map((field) => (
          <div key={field.id} className="flex justify-between">
            <span className="text-gray-600">{field.label}</span>
            <span className="font-medium">
              {field.type === 3 && field.options
                ? field.options.find((opt: string) => opt === field.value) || field.value || "-"
                : field.value || "-"}
            </span>
          </div>
        ))}
        {customFieldsWithValues.length === 0 && (
          <>
            <p className="text-gray-500 text-sm">No custom fields configured</p>
            <Link to={"/admin/custom-fields"} className="text-blue-500 hover:underline">
              Add custom fields
            </Link>
          </>
        )}
      </div>
    </div>
  )
}
