"use client"

import { Key, Code, BookOpen } from "lucide-react"
import { Link } from "react-router"
import IntegrationsLayout from "../../layout/IntegrationLayout"

export default function IntegrationsOverview() {
  const integrationItems = [
    {
      title: "API Keys",
      to: "/integrations/api-keys",
      icon: <Key className="h-5 w-5" />,
      description: "Create, manage, and rotate your API keys for secure access to our platform.",
    },
    {
      title: "Dealspace Pixel",
      to: "/integrations/dealspace-pixel",
      icon: <Code className="h-5 w-5" />,
      description: "Install and configure the Dealspace tracking pixel on your website.",
    },
    {
      title: "API Documentation",
      to: "/integrations/api-documentation",
      icon: <BookOpen className="h-5 w-5" />,
      description: "Complete API documentation with endpoints, parameters, and response examples.",
    },
  ]

  return (
    <IntegrationsLayout>
      <div className="space-y-8">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {integrationItems.map((item) => (
            <Link
              key={item.to}
              to={item.to}
              className="block p-6 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 hover:border-gray-300 transition-colors"
            >
              <div className="flex items-center gap-3 mb-3">
                <div className="text-blue-600">{item.icon}</div>
                <h3 className="text-lg font-medium text-gray-900">{item.title}</h3>
              </div>
              <p className="text-gray-600">{item.description}</p>
            </Link>
          ))}
        </div>

        {/* Footer */}
        <div className="text-center py-8 border-t border-gray-200">
          <p className="text-gray-600">
            Need help with integrations?{" "}
            <Link to="/support" className="text-blue-600 hover:text-blue-700 underline">
              Contact our support team
            </Link>
            .
          </p>
        </div>
      </div>
    </IntegrationsLayout>
  )
}
