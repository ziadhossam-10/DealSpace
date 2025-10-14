"use client"

import type { ReactNode } from "react"
import { Link, useLocation } from "react-router"

interface ReportsLayoutProps {
  children: ReactNode
}

export default function ReportsLayout({ children }: ReportsLayoutProps) {
  const { pathname } = useLocation()

  const subNavItems = [
    { name: "Overview", href: "/reports" },
    { name: "Agent Activity", href: "/reports/agent-activity" },
    { name: "Properties", href: "/reports/properties" },
    { name: "Lead Sources", href: "/reports/lead-sources" },
    { name: "Calls", href: "/reports/calls" },
    { name: "Texts", href: "/reports/texts" },
    { name: "Batch Emails", href: "/reports/batch-emails" },
    { name: "Marketing", href: "/reports/marketing" },
    { name: "Deals", href: "/reports/deals" },
    { name: "Appointments", href: "/reports/appointments" },
    { name: "Leaderboard", href: "/reports/leaderboard" },
    { name: "Agent Goals", href: "/reports/agent-goals" },
  ]

  return (
    <div className="min-h-screen">
      {/* Sub Navigation */}
      <div className="bg-white border-b px-4 py-2 flex items-center justify-between">
        <div className="flex items-center space-x-6 overflow-x-auto">
          {subNavItems.map((item) => (
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
