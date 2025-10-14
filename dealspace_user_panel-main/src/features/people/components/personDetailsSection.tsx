"use client"
import { Edit, Plus, X } from "lucide-react"

interface PersonDetailsSectionProps {
  contact: any
  onEditDetails: () => void
  onEditTag: (tag: any) => void
  onDeleteTag: (id: number) => void
  onAddTag: () => void
}

export const PersonDetailsSection = ({
  contact,
  onEditDetails,
  onEditTag,
  onDeleteTag,
  onAddTag,
}: PersonDetailsSectionProps) => {
  return (
    <div className="p-4 border-b">
      <div className="flex justify-between items-center">
        <h3 className="text-xs font-semibold text-gray-500 mb-2">DETAILS</h3>
        <button onClick={onEditDetails} className="text-blue-500 hover:text-blue-700">
          <Edit size={14} />
        </button>
      </div>
      <div className="space-y-2">
        <div className="flex justify-between">
          <span className="text-gray-600">Stage</span>
          <span className="font-medium">{contact?.stage}</span>
        </div>
        <div className="flex justify-between">
          <span className="text-gray-600">Source</span>
          <div>
            <span className="font-medium">{contact?.source}</span>
          </div>
        </div>
        <div className="flex justify-between">
          <span className="text-gray-600">Agent</span>
          <span className="font-medium">{contact?.assigned_user?.name && contact?.assigned_pond?.name ? `${contact?.assigned_pond?.name} (${contact?.assigned_user?.name})` : contact?.assigned_user?.name || contact?.assigned_pond?.name || "None"}</span>
        </div>
        <div className="flex justify-between">
          <span className="text-gray-600">Lender</span>
          <span className="font-medium">{contact?.assigned_lender?.name || "None"}</span>
        </div>
        <div className="flex justify-between">
          <span className="text-gray-600">Price</span>  
          <span className="font-medium">{contact?.price ? '$' + Number(contact?.price).toLocaleString() : 'N/A'}</span>
        </div>
        <div>
          <span className="text-gray-600">Tags</span>
          <div className="flex mt-1 flex-wrap gap-1">
            {contact?.tags.map((tag: any) => (
              <div
                key={tag.id}
                className="rounded-sm border border-gray-200 px-2 py-0.5 text-sm flex items-center group"
                style={{ backgroundColor: `${tag.color}20` }}
              >
                {tag.name}
                <div className="hidden group-hover:flex items-center ml-1">
                  <button onClick={() => onEditTag(tag)} className="text-gray-400 hover:text-blue-500 ml-1">
                    <Edit size={12} />
                  </button>
                  <button onClick={() => onDeleteTag(Number(tag.id))} className="text-gray-400 hover:text-red-500 ml-1">
                    <X size={12} />
                  </button>
                </div>
              </div>
            ))}
            <button
              onClick={onAddTag}
              className="h-6 w-6 p-0 rounded-full flex items-center justify-center text-gray-500 hover:bg-gray-100"
            >
              <Plus size={16} />
            </button>
          </div>
        </div>
      </div>
    </div>
  )
}
