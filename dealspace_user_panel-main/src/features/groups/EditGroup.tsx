"use client"

import type React from "react"
import { useState, useEffect } from "react"
import { toast } from "react-toastify"
import { ArrowLeft, X } from "lucide-react"
import { useUpdateGroupMutation, useGetGroupByIdQuery, useGetGroupsQuery } from "./groupsApi"
import { useGetPondsQuery } from "../ponds/pondsApi"
import { useGetUsersQuery } from "../users/usersApi"
import type { UpdateGroupRequest } from "../../types/groups"
import { useNavigate, useParams } from "react-router"
import AdminLayout from "../../layout/AdminLayout"
import { TableLoader } from "../../components/ui/loader/TableLoader"
import { TableErrorComponent } from "../../components/ui/error/TableErrorComponent"

interface DefaultAssignment {
  type: "user" | "pond" | "group" | null
  id: number | null
  name: string
  email?: string
}

export default function EditGroup() {
  const { groupId } = useParams<{ groupId: string }>()
  const numericGroupId = Number(groupId)
  const navigate = useNavigate()

  const [updateGroup, { isLoading }] = useUpdateGroupMutation()
  const { data: groupData, isLoading: isLoadingGroup, error } = useGetGroupByIdQuery(numericGroupId)

  const [formData, setFormData] = useState<UpdateGroupRequest>({
    name: "",
    type: 0,
    distribution: 0,
    claim_window: undefined,
    default_user_id: undefined,
    default_pond_id: undefined,
    default_group_id: undefined,
  })

  // Default assignment state
  const [defaultAssignment, setDefaultAssignment] = useState<DefaultAssignment>({
    type: null,
    id: null,
    name: "",
    email: undefined,
  })
  const [defaultSearchTerm, setDefaultSearchTerm] = useState("")
  const [showDefaultSearch, setShowDefaultSearch] = useState(false)

  // Fetch data for default assignment based on selected type
  const { data: defaultUsersData, isLoading: isLoadingDefaultUsers } = useGetUsersQuery(
    { role: Number(formData.type) + 2, search: defaultSearchTerm, page: 1, per_page: 50 },
    { skip: !showDefaultSearch || defaultAssignment.type !== "user" },
  )

  const { data: defaultPondsData, isLoading: isLoadingDefaultPonds } = useGetPondsQuery(
    { page: 1, per_page: 50, search: defaultSearchTerm },
    { skip: !showDefaultSearch || defaultAssignment.type !== "pond" },
  )

  const { data: defaultGroupsData, isLoading: isLoadingDefaultGroups } = useGetGroupsQuery(
    { page: 1, per_page: 50, search: defaultSearchTerm },
    { skip: !showDefaultSearch || defaultAssignment.type !== "group" },
  )

  const [errors, setErrors] = useState({
    name: "",
  })

  // Load group data when available
  useEffect(() => {
    if (groupData?.data) {
      const group = groupData.data
      setFormData({
        name: group.name,
        type: group.type,
        distribution: group.distribution,
        claim_window: group.claim_window ? Math.round(group.claim_window / (1000 * 60)) : undefined, // Convert ms to minutes
        default_user_id: group.default_user_id,
        default_pond_id: group.default_pond_id,
        default_group_id: group.default_group_id,
      })

      // Set default assignment based on existing data
      if (group.default_user_id) {
        setDefaultAssignment({
          type: "user",
          id: Number(group.defaultUser?.id),
          name: String(group.defaultUser?.name), // Will be updated when user data is fetched
          email: undefined,
        })
      } else if (group.default_pond_id) {
        setDefaultAssignment({
          type: "pond",
          id: Number(group.defaultPond?.id),
          name: String(group.defaultPond?.name), // Will be updated when pond data is fetched
          email: undefined,
        })
      } else if (group.default_group_id) {
        setDefaultAssignment({
          type: "group",
          id: Number(group.defaultGroup?.id),
          name: String(group.defaultGroup?.name), // Will be updated when group data is fetched
          email: undefined,
        })
      }
    }
  }, [groupData])

  const handleChange = (field: string, value: any) => {
    setFormData((prev) => ({
      ...prev,
      [field]: value,
    }))

    if (field === "type") {
      // Reset default assignment when type changes
      setDefaultAssignment({ type: null, id: null, name: "", email: undefined })
      setDefaultSearchTerm("")
    }

    if (field in errors) {
      setErrors((prev) => ({ ...prev, [field]: "" }))
    }
  }

  const handleDefaultAssignmentTypeChange = (type: "user" | "pond" | "group" | null) => {
    setDefaultAssignment({ type, id: null, name: "", email: undefined })
    setDefaultSearchTerm("")
    setShowDefaultSearch(false)

    // Clear all default assignment fields in form data
    setFormData((prev) => ({
      ...prev,
      default_user_id: undefined,
      default_pond_id: undefined,
      default_group_id: undefined,
    }))
  }

  const handleDefaultAssignmentSelect = (item: any) => {
    setDefaultAssignment({
      type: defaultAssignment.type,
      id: item.id,
      name: item.name,
      email: item.email,
    })

    // Update form data based on type
    if (defaultAssignment.type === "user") {
      setFormData((prev) => ({ ...prev, default_user_id: item.id }))
    } else if (defaultAssignment.type === "pond") {
      setFormData((prev) => ({ ...prev, default_pond_id: item.id }))
    } else if (defaultAssignment.type === "group") {
      setFormData((prev) => ({ ...prev, default_group_id: item.id }))
    }

    setShowDefaultSearch(false)
    setDefaultSearchTerm("")
  }

  const validateForm = (): boolean => {
    let isValid = true
    const newErrors = { name: "" }

    if (!formData.name?.trim()) {
      newErrors.name = "Group name is required"
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

    try {
      // Convert claim_window from minutes to milliseconds for API
      const submitData = {
        ...formData,
        claim_window: formData.claim_window ? formData.claim_window * 60 * 1000 : undefined,
      }

      await updateGroup({ id: numericGroupId, ...submitData }).unwrap()
      toast.success("Group updated successfully!")
      navigate("/admin/groups")
    } catch (error: any) {
      console.error("Failed to update group:", error)
      toast.error(error.data?.message || "Failed to update group. Please try again.")
    }
  }

  // Handle click outside for default assignment search
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (showDefaultSearch && !(event.target as Element).closest(".default-search-container")) {
        setShowDefaultSearch(false)
      }
    }

    document.addEventListener("mousedown", handleClickOutside)
    return () => document.removeEventListener("mousedown", handleClickOutside)
  }, [showDefaultSearch])

  const getDefaultSearchData = () => {
    if (defaultAssignment.type === "user") return defaultUsersData?.data?.items || []
    if (defaultAssignment.type === "pond") return defaultPondsData?.data?.items || []
    if (defaultAssignment.type === "group")
      return defaultGroupsData?.data?.items?.filter((g) => g.id !== numericGroupId) || []
    return []
  }

  const getDefaultSearchLoading = () => {
    if (defaultAssignment.type === "user") return isLoadingDefaultUsers
    if (defaultAssignment.type === "pond") return isLoadingDefaultPonds
    if (defaultAssignment.type === "group") return isLoadingDefaultGroups
    return false
  }

  if (isLoadingGroup) return <TableLoader />
  if (error) return <TableErrorComponent />

  return (
    <AdminLayout>
      <div className="bg-gray-50 min-h-screen p-6">
        <div className="container mx-auto">
          <div className="flex justify-between items-center mb-6">
            <h3 className="text-xl font-semibold text-gray-900">Edit Group</h3>
            <button
              onClick={() => navigate("/admin/groups")}
              className="px-3 py-1 bg-black text-white rounded flex items-center gap-1"
            >
              <ArrowLeft className="h-4 w-4" />
              Back
            </button>
          </div>

          <div className="w-full mx-auto bg-white rounded-lg shadow-md overflow-hidden">
            <form onSubmit={handleSubmit} className="p-6 space-y-6">
              {/* Group Name */}
              <div>
                <label htmlFor="name" className="block text-sm font-medium text-gray-700 mb-1">
                  Group Name*
                </label>
                <input
                  id="name"
                  type="text"
                  value={formData.name}
                  onChange={(e) => handleChange("name", e.target.value)}
                  className={`w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 ${
                    errors.name ? "border-red-500" : "border-gray-300"
                  }`}
                />
                {errors.name && <p className="mt-1 text-sm text-red-500">{errors.name}</p>}
              </div>

              {/* Type */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Type*</label>
                <select
                  value={formData.type?.toString()}
                  onChange={(e) => handleChange("type", Number.parseInt(e.target.value))}
                  className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  <option value="0">Agent</option>
                  <option value="1">Lender</option>
                </select>
              </div>

              {/* Distribution */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Distribution*</label>
                <select
                  value={formData.distribution?.toString()}
                  onChange={(e) => handleChange("distribution", Number.parseInt(e.target.value))}
                  className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  <option value="0">First to Claim</option>
                  <option value="1">Round Robin</option>
                </select>
              </div>

              {/* First to Claim Settings */}
              {formData.distribution === 0 && (
                <>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Claim Window (minutes)</label>
                    <input
                      type="number"
                      value={formData.claim_window || ""}
                      onChange={(e) =>
                        handleChange("claim_window", e.target.value ? Number.parseInt(e.target.value) : undefined)
                      }
                      placeholder="Enter claim window time in minutes"
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                    <p className="mt-1 text-xs text-gray-500">
                      Time in minutes before the lead is reassigned if not claimed
                    </p>
                  </div>

                  {/* Default Assignment */}
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">Default Assignment</label>
                    <p className="text-xs text-gray-500 mb-3">If claim window expires, lead will be assigned to:</p>

                    {/* Assignment Type Selection */}
                    <div className="space-y-3 mb-4">
                      <div className="flex space-x-4">
                        <label className="flex items-center">
                          <input
                            type="radio"
                            name="defaultType"
                            checked={defaultAssignment.type === "user"}
                            onChange={() => handleDefaultAssignmentTypeChange("user")}
                            className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                          />
                          <span className="ml-2 text-sm text-gray-700">User</span>
                        </label>
                        <label className="flex items-center">
                          <input
                            type="radio"
                            name="defaultType"
                            checked={defaultAssignment.type === "pond"}
                            onChange={() => handleDefaultAssignmentTypeChange("pond")}
                            className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                          />
                          <span className="ml-2 text-sm text-gray-700">Pond</span>
                        </label>
                        <label className="flex items-center">
                          <input
                            type="radio"
                            name="defaultType"
                            checked={defaultAssignment.type === "group"}
                            onChange={() => handleDefaultAssignmentTypeChange("group")}
                            className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                          />
                          <span className="ml-2 text-sm text-gray-700">Group</span>
                        </label>
                        <label className="flex items-center">
                          <input
                            type="radio"
                            name="defaultType"
                            checked={defaultAssignment.type === null}
                            onChange={() => handleDefaultAssignmentTypeChange(null)}
                            className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                          />
                          <span className="ml-2 text-sm text-gray-700">None</span>
                        </label>
                      </div>
                    </div>

                    {/* Searchable Select for Default Assignment */}
                    {defaultAssignment.type && (
                      <div className="default-search-container">
                        {!defaultAssignment.id ? (
                          <div className="relative">
                            <input
                              type="text"
                              placeholder={`Search and select ${defaultAssignment.type}...`}
                              value={defaultSearchTerm}
                              onChange={(e) => {
                                setDefaultSearchTerm(e.target.value)
                                setShowDefaultSearch(true)
                              }}
                              onFocus={() => setShowDefaultSearch(true)}
                              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />

                            {showDefaultSearch && (
                              <div className="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto">
                                {getDefaultSearchLoading() ? (
                                  <div className="p-3 text-center text-gray-500">Loading...</div>
                                ) : getDefaultSearchData().length ? (
                                  getDefaultSearchData().map((item: any) => (
                                    <button
                                      key={item.id}
                                      type="button"
                                      onClick={() => handleDefaultAssignmentSelect(item)}
                                      className="w-full text-left px-3 py-2 hover:bg-gray-100 focus:bg-gray-100 focus:outline-none"
                                    >
                                      <div className="font-medium text-gray-900">{item.name}</div>
                                      {item.email && <div className="text-sm text-gray-500">{item.email}</div>}
                                    </button>
                                  ))
                                ) : (
                                  <div className="p-3 text-center text-gray-500">
                                    No {defaultAssignment.type}s found
                                  </div>
                                )}
                              </div>
                            )}
                          </div>
                        ) : (
                          <div className="flex items-center justify-between p-3 bg-gray-50 border border-gray-300 rounded-md">
                            <div>
                              <div className="font-medium text-gray-900">{defaultAssignment.name}</div>
                              {defaultAssignment.email && (
                                <div className="text-sm text-gray-500">{defaultAssignment.email}</div>
                              )}
                            </div>
                            <button
                              type="button"
                              onClick={() => {
                                setDefaultAssignment({
                                  type: defaultAssignment.type,
                                  id: null,
                                  name: "",
                                  email: undefined,
                                })
                                setFormData((prev) => ({
                                  ...prev,
                                  default_user_id: undefined,
                                  default_pond_id: undefined,
                                  default_group_id: undefined,
                                }))
                              }}
                              className="p-1 rounded-md hover:bg-gray-200 transition-colors"
                            >
                              <X className="h-4 w-4 text-gray-500" />
                            </button>
                          </div>
                        )}
                      </div>
                    )}
                  </div>
                </>
              )}

              {/* Form Actions */}
              <div className="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                <button
                  type="button"
                  onClick={() => navigate("/admin/groups")}
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
                  {isLoading ? "Updating..." : "Update Group"}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </AdminLayout>
  )
}
