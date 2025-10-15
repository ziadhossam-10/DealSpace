"use client"
import {
  Users,
  CheckSquare,
  Mail,
  MessageSquare,
  ArrowRight,
  Database,
  Zap,
  Download,
  PhoneCall,
  TableProperties,
  Settings,
  Layers,
  FileText,
  Calendar,
  Target,
  CreditCard,
} from "lucide-react"
import { Link } from "react-router"
import AdminLayout from "../../layout/AdminLayout"
import { useAuth } from "../../hooks/useAuth"

export default function AdminDashboardContent() {
  const { role } = useAuth()

  const leadDistributionCards = [
    {
      title: "Lead Flow",
      to: "/admin/lead-flow-rules",
      icon: <ArrowRight className="text-gray-600" size={20} />,
      description: "See your leads flowing in & choose how to assign them & action plans.",
      roles: [0, 1], // Owner, Admin
    },
    {
      title: "Groups",
      to: "/admin/groups",
      icon: <Users className="text-gray-600" size={20} />,
      description: "Distribute leads via round-robin or first-to-claim groups.",
      roles: [0, 1], // Owner, Admin
    },
    {
      title: "Ponds",
      to: "/admin/ponds",
      icon: <Database className="text-gray-600" size={20} />,              
      description: "Create ponds your agents can prospect from to gain opportunities.",
      roles: [0, 1], // Owner, Admin
    },
  ]

  const followUpCards = [
    {
      title: "Action Plans",
      to: "/admin/action-plans",
      icon: <CheckSquare className="text-gray-600" size={20} />,
      description: "Send personalized drip emails, setup tasks, change stages & more.",
      roles: [0, 1, 2, 3, 4], // All roles
    },
    {
      title: "Automations",
      to: "/admin/automations",
      icon: <Zap className="text-gray-600" size={20} />,
      description: "Trigger action plans & quick actions when a stage changes or other trigger events.",
      roles: [0, 1, 2, 3, 4], // All roles
    },
    {
      title: "Email Templates",
      to: "/admin/email-templates",
      icon: <Mail className="text-gray-600" size={20} />,
      description: "View & edit email templates, see opens & clickthrough roles.",
      roles: [0, 1, 2, 3, 4], // All roles
    },
    {
      title: "Text Templates",
      to: "/admin/text-templates",
      icon: <MessageSquare className="text-gray-600" size={20} />,
      description: "View & edit text templates, track effectiveness based on reply rates.",
      roles: [0, 1, 2, 3, 4], // All roles
    },
  ]

  const stagesAndTypesCards = [
    {
      title: "Stages",
      to: "/admin/stages",
      icon: <Layers className="text-gray-600" size={20} />,
      description: "Manage deal stages and pipeline progression.",
      roles: [0, 1], // Owner, Admin
    },
    {
      title: "Deal Types",
      to: "/admin/deal-types",
      icon: <FileText className="text-gray-600" size={20} />,
      description: "Configure different types of deals and their properties.",
      roles: [0, 1], // Owner, Admin
    },
    {
      title: "Appointment Types",
      to: "/admin/appointment-types",
      icon: <Calendar className="text-gray-600" size={20} />,
      description: "Set up different appointment types and their settings.",
      roles: [0, 1], // Owner, Admin
    },
    {
      title: "Appointment Outcomes",
      to: "/admin/appointment-outcomes",
      icon: <Target className="text-gray-600" size={20} />,
      description: "Define possible outcomes for appointments and meetings.",
      roles: [0, 1], // Owner, Admin
    },
  ]

  const accountCards = [
    {
      title: "Teams",
      to: "/admin/teams",
      icon: <TableProperties className="text-gray-500" size={20} />,
      description: "Add, edit & delete people on your team. Manage export permissions & pause leads.",
      roles: [0, 1], // Owner, Admin
    },
    {
      title: "All Users",
      to: "/admin/users",
      icon: <Users className="text-gray-500" size={20} />,
      description: "Add, edit & delete people on your team. Manage export permissions & pause leads.",
      roles: [0, 1], // Owner, Admin
    },
    {
      title: "Manage Emails",
      to: "/admin/manage-emails",
      icon: <Settings className="text-gray-600" size={20} />,
      description: "Connect and manage email accounts for your organization.",
      roles: [0, 1], // All roles
    },
    {
      title: "Import",
      to: "/admin/import",
      icon: <Download className="text-gray-600" size={20} />,
      description: "Bring your old CRM over? Use our quick import tool.",
      roles: [0, 1, 2, 3, 4], // All roles
    },
    {
      title: "Calling",
      to: "/admin/calling",
      icon: <PhoneCall className="text-gray-600" size={20} />,
      description: "Port numbers in & manage the virtual numbers in your account.",
      roles: [0, 1], // All roles
    },
    {
      title: "Subscriptions",
      to: "/admin/subscriptions",
      icon: <CreditCard className="text-gray-600" size={20} />,
      description: "Manage your workspace subscription",
      roles: [0, 1], // Owner, Admin
    },
  ]

  // Filter cards based on user role
  const visibleLeadDistributionCards = leadDistributionCards.filter((card) => {
    if (role === null || role === undefined) return false
    return card.roles.includes(role)
  })

  const visibleFollowUpCards = followUpCards.filter((card) => {
    if (role === null || role === undefined) return false
    return card.roles.includes(role)
  })

  const visibleStagesAndTypesCards = stagesAndTypesCards.filter((card) => {
    if (role === null || role === undefined) return false
    return card.roles.includes(role)
  })

  const visibleAccountCards = accountCards.filter((card) => {
    if (role === null || role === undefined) return false
    return card.roles.includes(role)
  })

  return (
    <AdminLayout>
      {/* Lead Distribution Section */}
      {visibleLeadDistributionCards.length > 0 && (
        <div className="mb-8">
          <h2 className="text-xl font-medium text-gray-700 mb-4">Lead Distribution</h2>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {visibleLeadDistributionCards.map((card) => (
              <Link key={card.title} to={card.to} className="block">
                <div className="bg-white rounded-lg shadow p-6 hover:shadow-md transition-shadow cursor-pointer h-full flex flex-col">
                  <div className="flex items-center mb-3">
                    {card.icon}
                    <h3 className="font-medium text-gray-800 ml-2">{card.title}</h3>
                  </div>
                  <p className="text-gray-600 text-sm flex-grow">{card.description}</p>
                </div>
              </Link>
            ))}
          </div>
        </div>
      )}

      {/* Follow Up Section */}
      {visibleFollowUpCards.length > 0 && (
        <div className="mb-8">
          <h2 className="text-xl font-medium text-gray-700 mb-4">Follow Up</h2>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {visibleFollowUpCards.map((card) => (
              <Link key={card.title} to={card.to} className="block">
                <div className="bg-white rounded-lg shadow p-6 hover:shadow-md transition-shadow cursor-pointer h-full flex flex-col">
                  <div className="flex items-center mb-3">
                    {card.icon}
                    <h3 className="font-medium text-gray-800 ml-2">{card.title}</h3>
                  </div>
                  <p className="text-gray-600 text-sm flex-grow">{card.description}</p>
                </div>
              </Link>
            ))}
          </div>
        </div>
      )}

      {/* Stages and Types Section */}
      {visibleStagesAndTypesCards.length > 0 && (
        <div className="mb-8">
          <h2 className="text-xl font-medium text-gray-700 mb-4">Stages and Types</h2>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {visibleStagesAndTypesCards.map((card) => (
              <Link key={card.title} to={card.to} className="block">
                <div className="bg-white rounded-lg shadow p-6 hover:shadow-md transition-shadow cursor-pointer h-full flex flex-col">
                  <div className="flex items-center mb-3">
                    {card.icon}
                    <h3 className="font-medium text-gray-800 ml-2">{card.title}</h3>
                  </div>
                  <p className="text-gray-600 text-sm flex-grow">{card.description}</p>
                </div>
              </Link>
            ))}
          </div>
        </div>
      )}

      {/* Account Section */}
      {visibleAccountCards.length > 0 && (
        <div>
          <h2 className="text-xl font-medium text-gray-700 mb-4">Account</h2>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            {visibleAccountCards.map((card) => (
              <Link key={card.title} to={card.to} className="block">
                <div className="bg-white rounded-lg shadow p-6 hover:shadow-md transition-shadow cursor-pointer h-full flex flex-col">
                  <div className="flex items-center mb-3">
                    {card.icon}
                    <h3 className="font-medium text-gray-800 ml-2">{card.title}</h3>
                  </div>
                  <p className="text-gray-600 text-sm flex-grow">{card.description}</p>
                </div>
              </Link>
            ))}
          </div>
        </div>
      )}
    </AdminLayout>
  )
}
