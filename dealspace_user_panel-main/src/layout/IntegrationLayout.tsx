"use client"

import type { ReactNode } from "react"
import { Link, useLocation } from "react-router"

interface IntegrationsLayoutProps {
  children: ReactNode
}

export default function IntegrationsLayout({ children }: IntegrationsLayoutProps) {
  const { pathname } = useLocation()

  const subNavItems = [
    { name: "Overview", href: "/integrations" },
    { name: "API Keys", href: "/integrations/api-keys" },
    { name: "Dealspace Pixel", href: "/integrations/dealspace-pixel" },
    { name: "API Documentation", href: "/integrations/api-documentation" },
  ]

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Sub Navigation */}
      <div className="bg-white border-b border-gray-200">
        <div className="container mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex space-x-8 overflow-x-auto">
            {subNavItems.map((item) => (
              <Link
                key={item.href}
                to={item.href}
                className={`whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm ${
                  pathname === item.href
                    ? "border-blue-500 text-blue-600"
                    : "border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300"
                }`}
              >
                {item.name}
              </Link>
            ))}
          </div>
        </div>
      </div>

      {/* Main Content */}
      <div className="container mx-auto px-4 py-6">{children}</div>
    </div>
  )
}
