import { TrendingUp, MapPin, Users, Activity } from "lucide-react"

interface PropertyReportMetricsProps {
  data: any[]
  viewMode: "property" | "zipcode"
}

export default function PropertyReportMetrics({ data, viewMode }: PropertyReportMetricsProps) {
  const totalInquiries = data.reduce((sum, item) => sum + item.total_inquiries, 0)
  const totalEvents = data.reduce((sum, item) => sum + item.total_events, 0)
  const uniqueLeads = data.reduce((sum, item) => sum + item.unique_leads, 0)
  const uniqueProperties =
    viewMode === "zipcode" ? data.reduce((sum, item) => sum + item.unique_properties, 0) : data.length

  const avgInquiriesPerProperty = data.length > 0 ? (totalEvents / data.length).toFixed(1) : "0"

  const metrics = [
    {
      title: "Total Inquiries",
      value: totalInquiries.toLocaleString(),
      icon: TrendingUp,
      color: "text-green-600",
      bgColor: "bg-green-100",
    },
    {
      title: "Total Events",
      value: totalEvents.toLocaleString(),
      icon: Activity,
      color: "text-blue-600",
      bgColor: "bg-blue-100",
    },
    {
      title: "Unique Leads",
      value: uniqueLeads.toLocaleString(),
      icon: Users,
      color: "text-purple-600",
      bgColor: "bg-purple-100",
    },
    {
      title: viewMode === "property" ? "Properties" : "Unique Properties",
      value: uniqueProperties.toLocaleString(),
      icon: MapPin,
      color: "text-orange-600",
      bgColor: "bg-orange-100",
    },
  ]

  return (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      {metrics.map((metric, index) => {
        const Icon = metric.icon
        return (
          <div key={index} className="bg-white rounded-lg shadow p-6">
            <div className="flex items-center">
              <div className={`${metric.bgColor} p-3 rounded-lg`}>
                <Icon className={`w-6 h-6 ${metric.color}`} />
              </div>
              <div className="ml-4">
                <p className="text-sm font-medium text-gray-600">{metric.title}</p>
                <p className="text-2xl font-bold text-gray-900">{metric.value}</p>
              </div>
            </div>
          </div>
        )
      })}

      {/* Additional metric for average inquiries */}
      <div className="bg-white rounded-lg shadow p-6 md:col-span-2 lg:col-span-4">
        <div className="text-center">
          <p className="text-sm font-medium text-gray-600">
            Average Inquiries per {viewMode === "property" ? "Property" : "Zip Code"}
          </p>
          <p className="text-3xl font-bold text-gray-900">{avgInquiriesPerProperty}</p>
        </div>
      </div>
    </div>
  )
}
