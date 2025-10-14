"use client"

import type React from "react"
import { useState, useEffect } from "react"
import { useUpdateUserMutation, useGetUserByIdQuery, useGetRolesQuery } from "./usersApi"
import { useNavigate, useParams } from "react-router"
import { toast } from "react-toastify"
import { User, Upload, ArrowLeft } from "lucide-react"
import { UpdateUserRequest } from "../../types/users"
import { TableLoader } from "../../components/ui/loader/TableLoader"
import { TableErrorComponent } from "../../components/ui/error/TableErrorComponent"
import { ASSETS_URL } from "../../utils/helpers"

export default function EditUser() {
  const { userId } = useParams<{ userId: string }>()
  const numericUserId = Number(userId)

  const navigate = useNavigate()
  const [updateUser, { isLoading: isUpdating }] = useUpdateUserMutation()
  const { data: userData, isLoading, error } = useGetUserByIdQuery(numericUserId)
  const { data: rolesData, isLoading: isLoadingRoles } = useGetRolesQuery()

  // Form state with proper typing
  const [formData, setFormData] = useState<Omit<UpdateUserRequest, "avatar"> & { confirmPassword: string }>({
    id: 0,
    name: "",
    email: "",
    password: "",
    confirmPassword: "",
    role: 1,
  })

  // Avatar state
  const [avatar, setAvatar] = useState<File | null>(null)
  const [avatarPreview, setAvatarPreview] = useState<string | null>(null)

  // Validation state
  const [errors, setErrors] = useState({
    name: "",
    email: "",
    password: "",
    confirmPassword: "",
  })

  // Load user data when available
  useEffect(() => {
    if (userData?.data) {
      setFormData({
              id: userData.data.id || 0,
              name: userData.data.name || "",
              email: userData.data.email || "",
              password: "",
              confirmPassword: "",
              role: userData.data.role || 1,
            })

      if (userData.data.avatar) {
        setAvatarPreview(userData.data.avatar)
      }
    }
  }, [userData])

  // Handle input changes
  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
    const { name, value } = e.target
    setFormData((prev) => ({
      ...prev,
      [name]: name === "role" ? Number.parseInt(value) : value,
    }))

    // Clear error when field is edited
    if (name in errors) {
      setErrors((prev) => ({ ...prev, [name]: "" }))
    }
  }

  // Handle avatar upload
  const handleAvatarChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files && e.target.files[0]) {
      const file = e.target.files[0]
      setAvatar(file)

      // Create preview
      const reader = new FileReader()
      reader.onload = () => {
        setAvatarPreview(reader.result as string)
      }
      reader.readAsDataURL(file)
    }
  }

  // Validate form
  const validateForm = (): boolean => {
    let isValid = true
    const newErrors = { ...errors }

    // Validate name
    if (!formData.name.trim()) {
      newErrors.name = "Name is required"
      isValid = false
    } else {
      newErrors.name = ""
    }

    // Validate email
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    if (!formData.email.trim()) {
      newErrors.email = "Email is required"
      isValid = false
    } else if (!emailRegex.test(formData.email)) {
      newErrors.email = "Please enter a valid email address"
      isValid = false
    } else {
      newErrors.email = ""
    }

    // Only validate password if it's provided (optional on edit)
    if (formData.password) {
      if (formData.password.length < 8) {
        newErrors.password = "Password must be at least 8 characters"
        isValid = false
      } else {
        newErrors.password = ""
      }

      if (formData.password !== formData.confirmPassword) {
        newErrors.confirmPassword = "Passwords do not match"
        isValid = false
      } else {
        newErrors.confirmPassword = ""
      }
    } else {
      newErrors.password = ""
      newErrors.confirmPassword = ""
    }

    setErrors(newErrors)
    return isValid
  }

  // Handle form submission
  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()

    if (!validateForm()) {
      return
    }

    try {
      // Prepare typed data for API
      const userData: UpdateUserRequest = {
        id: formData.id,
        name: formData.name,
        email: formData.email,
        role: formData.role,
        ...(formData.password && { password: formData.password }),
        ...(avatar && { avatar }),
      }

      // Call API to update user
      await updateUser(userData).unwrap()

      // Show success message and navigate back to users list
      toast.success("User updated successfully!")
      navigate("/admin/users")
    } catch (error: any) {
      console.error("Failed to update user:", error)
      toast.error(error.data?.message || "Failed to update user. Please try again.")
    }
  }

  if (isLoading) return <TableLoader />

  if (error) return <TableErrorComponent />

  return (
    <div className="bg-gray-50 min-h-screen p-6">
      <div className="container mx-auto">
        <div className="flex justify-between items-center">
          <h3 className="text-xl font-semibold text-gray-900 dark:text-white mb-4">Edit user</h3>
          <button
            onClick={() => navigate("/admin/users")}
            className="px-3 py-1 bg-black text-white rounded flex items-center gap-1 mb-4"
          >
            <ArrowLeft />
            Back
          </button>
        </div>
        <div className="w-full mx-auto bg-white rounded-lg shadow-md overflow-hidden">
          <form onSubmit={handleSubmit} className="p-6">
            <div className="space-y-6">
              {/* Avatar Upload */}
              <div className="flex flex-col items-center justify-center">
                <div className="mb-4">
                  {avatarPreview ? (
                    <img
                      src={(ASSETS_URL +  '/storage/' + avatarPreview) || "/placeholder.svg"}
                      alt="Avatar Preview"
                      className="h-24 w-24 rounded-full object-cover border-2 border-gray-200"
                    />
                  ) : (
                    <div className="h-24 w-24 rounded-full bg-gray-200 flex items-center justify-center">
                      <User size={40} className="text-gray-400" />
                    </div>
                  )}
                </div>

                <label htmlFor="avatar" className="cursor-pointer flex items-center text-blue-600 hover:text-blue-800">
                  <Upload size={16} className="mr-1" />
                  <span>Change Avatar</span>
                  <input
                    type="file"
                    id="avatar"
                    name="avatar"
                    accept="image/*"
                    onChange={handleAvatarChange}
                    className="hidden"
                  />
                </label>
              </div>

              {/* Name */}
              <div>
                <label htmlFor="name" className="block text-sm font-medium text-gray-700 mb-1">
                  Name*
                </label>
                <input
                  type="text"
                  id="name"
                  name="name"
                  value={formData.name}
                  onChange={handleChange}
                  className={`w-full px-3 py-2 border ${
                    errors.name ? "border-red-500" : "border-gray-300"
                  } rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500`}
                />
                {errors.name && <p className="mt-1 text-sm text-red-500">{errors.name}</p>}
              </div>

              {/* Email */}
              <div>
                <label htmlFor="email" className="block text-sm font-medium text-gray-700 mb-1">
                  Email*
                </label>
                <input
                  type="email"
                  id="email"
                  name="email"
                  value={formData.email}
                  onChange={handleChange}
                  className={`w-full px-3 py-2 border ${
                    errors.email ? "border-red-500" : "border-gray-300"
                  } rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500`}
                />
                {errors.email && <p className="mt-1 text-sm text-red-500">{errors.email}</p>}
              </div>

              {/* Password (Optional on edit) */}
              <div>
                <label htmlFor="password" className="block text-sm font-medium text-gray-700 mb-1">
                  Password (Leave blank to keep current)
                </label>
                <input
                  type="password"
                  id="password"
                  name="password"
                  value={formData.password}
                  onChange={handleChange}
                  className={`w-full px-3 py-2 border ${
                    errors.password ? "border-red-500" : "border-gray-300"
                  } rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500`}
                />
                {errors.password && <p className="mt-1 text-sm text-red-500">{errors.password}</p>}
              </div>

              {/* Confirm Password */}
              <div>
                <label htmlFor="confirmPassword" className="block text-sm font-medium text-gray-700 mb-1">
                  Confirm Password
                </label>
                <input
                  type="password"
                  id="confirmPassword"
                  name="confirmPassword"
                  value={formData.confirmPassword}
                  onChange={handleChange}
                  className={`w-full px-3 py-2 border ${
                    errors.confirmPassword ? "border-red-500" : "border-gray-300"
                  } rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500`}
                />
                {errors.confirmPassword && <p className="mt-1 text-sm text-red-500">{errors.confirmPassword}</p>}
              </div>

              {/* Role */}
              <div>
                <label htmlFor="role" className="block text-sm font-medium text-gray-700 mb-1">
                  Role*
                </label>
                {isLoadingRoles ? (
                  <div className="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50">Loading roles...</div>
                ) : (
                  <select
                    id="role"
                    name="role"
                    value={formData.role}
                    onChange={handleChange}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                    {rolesData?.data?.map(
                      (role, index) =>
                        index > 0 && (
                          <option key={index} value={index}>
                            {role}
                          </option>
                        ),
                    )}
                  </select>
                )}
              </div>
            </div>

            {/* Form Actions */}
            <div className="mt-8 flex justify-end space-x-3">
              <button
                type="button"
                onClick={() => navigate("/admin/users")}
                className="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
              >
                Cancel
              </button>
              <button
                type="submit"
                disabled={isUpdating}
                className={`px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 ${
                  isUpdating ? "opacity-75 cursor-not-allowed" : ""
                }`}
              >
                {isUpdating ? "Updating..." : "Update User"}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  )
}
