import { Mail, Search, Filter, MoreVertical } from "lucide-react"

// Mock email data - replace with actual API call
const mockEmails = [
  {
    id: 1,
    sender: "Emily Davis",
    subject: "Re: New order #34562",
    preview: "Hi Emily, Thanks for your order. We are pleased to inform you that your order has been shipped...",
    time: "2 days ago",
    isRead: false,
  },
  {
    id: 2,
    sender: "Marketing Team",
    subject: "New Product Announcement",
    preview: "Dear valued customer, we are excited to introduce our latest product! Check it out on our website now.",
    time: "1 hour ago",
    isRead: true,
  },
  {
    id: 3,
    sender: "Support Team",
    subject: "Important Update",
    preview: "Hello, we have important updates regarding your account security. Please review the changes...",
    time: "3 days ago",
    isRead: true,
  },
]

export default function InboxContent() {
  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold text-gray-900">Inbox</h1>
          <p className="text-gray-600">Manage your emails from all connected accounts</p>
        </div>
        <button className="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors flex items-center">
          <Mail className="mr-2 h-4 w-4" />
          Compose
        </button>
      </div>

      {/* Search and Filters */}
      <div className="bg-white rounded-lg shadow-lg border border-gray-200">
        <div className="p-6">
          <div className="flex items-center space-x-4">
            <div className="flex-1 relative">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
              <input
                type="text"
                placeholder="Search emails..."
                className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
              />
            </div>
            <button className="border border-gray-300 hover:bg-gray-50 text-gray-700 font-medium py-2 px-4 rounded-lg transition-colors flex items-center">
              <Filter className="mr-2 h-4 w-4" />
              Filter
            </button>
          </div>
        </div>
      </div>

      {/* Email List */}
      <div className="bg-white rounded-lg shadow-lg border border-gray-200">
        <div className="p-6 border-b border-gray-200">
          <div className="flex items-center justify-between">
            <h3 className="text-lg font-semibold text-gray-900">Recent Emails</h3>
            <span className="bg-gray-100 text-gray-800 text-sm font-medium px-2.5 py-0.5 rounded-full">
              {mockEmails.length} emails
            </span>
          </div>
        </div>
        <div className="divide-y divide-gray-200">
          {mockEmails.map((email) => (
            <div
              key={email.id}
              className={`p-4 hover:bg-gray-50 cursor-pointer transition-colors ${
                !email.isRead ? "bg-blue-50 border-l-4 border-l-blue-500" : ""
              }`}
            >
              <div className="flex items-start justify-between">
                <div className="flex-1 min-w-0">
                  <div className="flex items-center space-x-2 mb-1">
                    <p className={`text-sm font-medium text-gray-900 ${!email.isRead ? "font-semibold" : ""}`}>
                      {email.sender}
                    </p>
                    {!email.isRead && <div className="w-2 h-2 bg-blue-500 rounded-full"></div>}
                  </div>
                  <p className={`text-sm text-gray-900 mb-1 ${!email.isRead ? "font-medium" : ""}`}>{email.subject}</p>
                  <p className="text-sm text-gray-500 line-clamp-2">{email.preview}</p>
                </div>
                <div className="flex items-center space-x-2 ml-4">
                  <span className="text-xs text-gray-400 whitespace-nowrap">{email.time}</span>
                  <button className="p-1 hover:bg-gray-100 rounded transition-colors">
                    <MoreVertical className="h-4 w-4 text-gray-400" />
                  </button>
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  )
}
