"use client"

import {
  Users,
  Phone,
  FileText,
  MessageSquare,
  Calendar,
  TrendingUp,
  Target,
  Trophy,
  BarChart3,
  Zap,
  PhoneCall,
  Mail,
  Home,
  Search,
  DollarSign,
} from "lucide-react"
import { Link } from "react-router"
import ReportsLayout from "../../layout/ReportLayout"

export default function ReportsOverview() {
  const agentsCards = [
    {
      title: "Agent Activity",
      to: "/reports/agent-activity",
      icon: <Users className="text-gray-600" size={20} />,
      description: "See the number of leads per agent alongside stats on follow up.",
    },
    {
      title: "Calls",
      to: "/reports/calls",
      icon: <Phone className="text-gray-600" size={20} />,
      description: "See calls made, conversations, missed calls, talk time and more by phone number.",
    },
    {
      title: "Call Logs",
      to: "/reports/call-logs",
      icon: <PhoneCall className="text-gray-600" size={20} />,
      description: "See and listen to recent inbound and outbound calls.",
    },
    {
      title: "Texts",
      to: "/reports/texts",
      icon: <MessageSquare className="text-gray-600" size={20} />,
      description: "See last message delivery rates and other stats by phone number.",
    },
    {
      title: "Appointments",
      to: "/reports/appointments",
      icon: <Calendar className="text-gray-600" size={20} />,
      description: "See a list of appointments & outcomes with details on lead source and agent.",
    },
    {
      title: "Deals",
      to: "/reports/deals",
      icon: <FileText className="text-gray-600" size={20} />,
      description: "See a list of deals with commissions by deal stage and lead source.",
    },
    {
      title: "Agent Leaderboard",
      to: "/reports/agent-leaderboard",
      icon: <Trophy className="text-gray-600" size={20} />,
      description: "Some friendly competition based on follow up and appointments.",
      badge: "PRO",
    },
    {
      title: "Deals Leaderboard",
      to: "/reports/deals-leaderboard",
      icon: <TrendingUp className="text-gray-600" size={20} />,
      description: "See which agent is closing the most deals.",
      badge: "PRO",
    },
    {
      title: "Agent Goals",
      to: "/reports/agent-goals",
      icon: <Target className="text-gray-600" size={20} />,
      description: "Manage annual commission and personal goals for each agent.",
    },
  ]

  const leadSourcesCards = [
    {
      title: "Source Report",
      to: "/reports/source-report",
      icon: <BarChart3 className="text-gray-600" size={20} />,
      description: "See your top lead providers and sources of appointments.",
    },
    {
      title: "Speed To Lead",
      to: "/reports/speed-to-lead",
      icon: <Zap className="text-gray-600" size={20} />,
      description: "See how quickly you follow up by source and follow up type.",
    },
    {
      title: "Contact Attempts",
      to: "/reports/contact-attempts",
      icon: <PhoneCall className="text-gray-600" size={20} />,
      description: "See how many times you follow up on average by source.",
    },
    {
      title: "Closed Deals By Source",
      to: "/reports/closed-deals-by-source",
      icon: <DollarSign className="text-gray-600" size={20} />,
      description: "See which lead source has the most closed deals, commission and conversion rate %.",
    },
  ]

  const marketingCards = [
    {
      title: "Batch Emails",
      to: "/reports/batch-emails",
      icon: <Mail className="text-gray-600" size={20} />,
      description: "See the results of your email campaigns, opens & clicks.",
    },
    {
      title: "Properties",
      to: "/reports/properties",
      icon: <Home className="text-gray-600" size={20} />,
      description: "See which properties and zipcodes have the most inquiries.",
    },
    {
      title: "Marketing UTM Report",
      to: "/reports/marketing",
      icon: <Search className="text-gray-600" size={20} />,
      description: "See advanced UTM and campaign metrics and appointments & deals.",
    },
  ]

  return (
    <ReportsLayout>
      {/* Agents Section */}
      <div className="mb-8">
        <h2 className="text-xl font-medium text-gray-700 mb-4">Agents</h2>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {agentsCards.map((card) => (
            <Link key={card.title} to={card.to} className="block">
              <div className="bg-white rounded-lg shadow p-6 hover:shadow-md transition-shadow cursor-pointer h-full flex flex-col">
                <div className="flex items-center mb-3">
                  {card.icon}
                  <h3 className="font-medium text-gray-800 ml-2">{card.title}</h3>
                  {card.badge && (
                    <span className="ml-auto bg-yellow-100 text-yellow-800 text-xs font-medium px-2 py-1 rounded">
                      {card.badge}
                    </span>
                  )}
                </div>
                <p className="text-gray-600 text-sm flex-grow">{card.description}</p>
              </div>
            </Link>
          ))}
        </div>
      </div>

      {/* Lead Sources Section */}
      <div className="mb-8">
        <h2 className="text-xl font-medium text-gray-700 mb-4">Lead Sources</h2>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          {leadSourcesCards.map((card) => (
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

      {/* Marketing Section */}
      <div>
        <h2 className="text-xl font-medium text-gray-700 mb-4">Marketing</h2>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {marketingCards.map((card) => (
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

      {/* Footer */}
      <div className="mt-8 pt-4 border-t">
        <p className="text-gray-500 text-sm">
          Looking for another kind of report?{" "}
          <Link to="/reports/suggest" className="text-blue-500 hover:underline">
            Suggest a feature.
          </Link>
        </p>
      </div>
    </ReportsLayout>
  )
}
