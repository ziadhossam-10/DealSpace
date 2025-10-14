"use client"
import type { ReactNode } from "react"
import { Link, useLocation } from "react-router"
import { ChevronDown, ChevronRight } from "lucide-react"
import { useState } from "react"
import { useAuth } from "../hooks/useAuth"
import { isAdminOrOwnerRole } from "../utils/roles"

interface AdminLayoutProps {
  children: ReactNode
}

export default function AdminLayout({ children }: AdminLayoutProps) {
  const { pathname } = useLocation()
  const { role } = useAuth()
  const [isStagesDropdownOpen, setIsStagesDropdownOpen] = useState(false)

  const isAdminOrOwner = isAdminOrOwnerRole(role) // true for role 0 or 1

  const stagesAndTypesItems = [
    { name: "Stages", href: "/admin/stages", roles: [0, 1] },
    { name: "Deal Types", href: "/admin/deal-types", roles: [0, 1] },
    { name: "Appointment Types", href: "/admin/appointment-types", roles: [0, 1] },
    { name: "Appointment Outcomes", href: "/admin/appointment-outcomes", roles: [0, 1] },
  ]

  // Define navigation items with role restrictions
  const subNavItems = [
    { name: "Overview", href: "/admin/overview", roles: [0, 1, 2, 3, 4] },
    { name: "Lead Flow", href: "/admin/lead-flow-rules", roles: [0, 1] },
    { name: "Groups", href: "/admin/groups", roles: [0, 1] },
    { name: "Teams", href: "/admin/teams", roles: [0, 1] },
    { name: "All Users", href: "/admin/users", roles: [0, 1] },
    { name: "Action Plans", href: "/admin/action-plans", roles: [0, 1, 2, 3, 4] },
    { name: "Automations", href: "/admin/automations", roles: [0, 1, 2, 3, 4] },
    { name: "Ponds", href: "/admin/ponds", roles: [0, 1] },
    { name: "Email Templates", href: "/admin/email-templates", roles: [0, 1, 2, 3, 4] },
    { name: "Text Templates", href: "/admin/text-templates", roles: [0, 1, 2, 3, 4] },
    { name: "Manage Emails", href: "/admin/manage-emails", roles: [0, 1] },
    { name: "Import", href: "/admin/import", roles: [0, 1, 2, 3, 4] },
    { name: "Custom Fields", href: "/admin/custom-fields", roles: [0, 1] },
    { name: "Calling", href: "/admin/calling", roles: [0, 1] }, 
  ]

  // Filter navigation items based on user role
  const visibleNavItems = subNavItems.filter((item) => {
    if (role === null || role === undefined) return false
    return item.roles.includes(role)
  })

  // Check if current path is in stages and types section
  const isInStagesSection = stagesAndTypesItems.some((item) => pathname === item.href)

  return (
    <div className="min-h-screen">
      {/* Sub Navigation */}
      <div className="bg-white border-b px-4 py-2 flex items-center justify-between">
        <div className="flex items-center space-x-6 overflow-x-auto">
          {visibleNavItems.map((item) => (
            <Link
              key={item.name}
              to={item.href}
              className={`cursor-pointer px-2 py-1 whitespace-nowrap ${
                pathname === item.href
                  ? "border-b-2 border-gray-600 font-medium text-gray-600"
                  : "text-gray-600 hover:text-gray-900"
              }`}
            >
              {item.name}
            </Link>
          ))}
        </div>
      </div>

      {/* Main Content */}
      <div className="container mx-auto px-4 py-6">{children}</div>
    </div>
  )
}
