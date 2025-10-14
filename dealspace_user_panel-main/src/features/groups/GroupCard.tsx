"use client"
import { Users, Edit, Trash2, MoreVertical } from "lucide-react"
import { useState } from "react"
import type { Group } from "../../types/groups"

interface GroupCardProps {
  group: Group
  onEdit: (group: Group) => void
  onDelete: (groupId: number) => void
  onManageUsers: (group: Group) => void
}

export default function GroupCard({ group, onEdit, onDelete, onManageUsers }: GroupCardProps) {
  const [isDropdownOpen, setIsDropdownOpen] = useState(false)

  const getDistributionColor = (distribution: number) => {
    return distribution === 0 ? "bg-blue-100 text-blue-800" : "bg-green-100 text-green-800"
  }

  const getTypeColor = (type: number) => {
    return type === 0 ? "bg-purple-100 text-purple-800" : "bg-orange-100 text-orange-800"
  }

  return (
    <div className="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-200 border border-gray-200">
      <div className="flex flex-row items-start justify-between space-y-0 p-4 pb-2">
        <div className="space-y-2">
          <h3 className="text-lg font-semibold text-gray-900">{group.name}</h3>
          <div className="flex gap-2">
            <span
              className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getTypeColor(group.type)}`}
            >
              {group.type_name}
            </span>
            <span
              className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getDistributionColor(group.distribution)}`}
            >
              {group.distribution_name}
            </span>
          </div>
        </div>
        <div className="relative">
          <button
            onClick={() => setIsDropdownOpen(!isDropdownOpen)}
            className="p-1 rounded-md hover:bg-gray-100 transition-colors"
          >
            <MoreVertical className="h-4 w-4 text-gray-500" />
          </button>
          {isDropdownOpen && (
            <div className="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg border border-gray-200 z-10">
              <div className="py-1">
                <button
                  onClick={() => {
                    onEdit(group)
                    setIsDropdownOpen(false)
                  }}
                  className="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                >
                  <Edit className="mr-2 h-4 w-4" />
                  Edit
                </button>
                <button
                  onClick={() => {
                    onManageUsers(group)
                    setIsDropdownOpen(false)
                  }}
                  className="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                >
                  <Users className="mr-2 h-4 w-4" />
                  Manage Users
                </button>
                <button
                  onClick={() => {
                    onDelete(group.id)
                    setIsDropdownOpen(false)
                  }}
                  className="flex items-center w-full px-4 py-2 text-sm text-red-600 hover:bg-gray-100"
                >
                  <Trash2 className="mr-2 h-4 w-4" />
                  Delete
                </button>
              </div>
            </div>
          )}
        </div>
      </div>
      <div className="p-4 pt-2">
        <div className="flex items-center justify-between text-sm text-gray-600">
          <div className="flex items-center gap-1">
            <Users className="h-4 w-4" />
            <span>{group.users_count || 0} users</span>
          </div>
          {group.created_at && <span>Created {new Date(group.created_at).toLocaleDateString()}</span>}
        </div>
      </div>
    </div>
  )
}
