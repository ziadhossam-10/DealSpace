import { TrendingUp, Users, Calendar, DollarSign } from "lucide-react"
import type { MarketingTotals } from "./marketingApi"

interface MarketingMetricsProps {
  totals: MarketingTotals
}

export default function MarketingMetrics({ totals }: MarketingMetricsProps) {
  const formatCurrency = (value: number) => {
    return new Intl.NumberFormat("en-US", {
      style: "currency",
      currency: "USD",
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }).format(value)
  }

  const formatNumber = (value: number) => {
    return new Intl.NumberFormat("en-US").format(value)
  }

  const conversionRate = totals?.total_leads > 0 ? (totals?.total_closed_deals / totals?.total_leads) * 100 : 0
  const appointmentRate = totals?.total_leads > 0 ? (totals?.total_appointments / totals?.total_leads) * 100 : 0

  const metrics = [
    {
      title: "Total Leads",
      value: formatNumber(totals?.total_leads),
      icon: Users,
      color: "bg-blue-500",
      textColor: "text-blue-600",
      bgColor: "bg-blue-50",
    },
    {
      title: "Appointments",
      value: formatNumber(totals?.total_appointments),
      subtitle: `${appointmentRate.toFixed(1)}% of leads`,
      icon: Calendar,
      color: "bg-green-500",
      textColor: "text-green-600",
      bgColor: "bg-green-50",
    },
    {
      title: "Closed Deals",
      value: formatNumber(totals?.total_closed_deals),
      subtitle: `${conversionRate.toFixed(1)}% conversion`,
      icon: TrendingUp,
      color: "bg-purple-500",
      textColor: "text-purple-600",
      bgColor: "bg-purple-50",
    },
    {
      title: "Deal Value",
      value: formatCurrency(totals?.total_deal_value),
      subtitle:
        totals?.total_closed_deals > 0
          ? `${formatCurrency(totals?.total_deal_value / totals?.total_closed_deals)} avg`
          : "No deals",
      icon: DollarSign,
      color: "bg-orange-500",
      textColor: "text-orange-600",
      bgColor: "bg-orange-50",
    },
  ]

  return (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      {metrics.map((metric) => {
        const Icon = metric.icon
        return (
          <div key={metric.title} className="bg-white rounded-lg shadow p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-gray-600">{metric.title}</p>
                <p className="text-2xl font-bold text-gray-900 mt-1">{metric.value}</p>
                {metric.subtitle && <p className="text-sm text-gray-500 mt-1">{metric.subtitle}</p>}
              </div>
              <div className={`${metric.bgColor} p-3 rounded-full`}>
                <Icon className={`w-6 h-6 ${metric.textColor}`} />
              </div>
            </div>
          </div>
        )
      })}
    </div>
  )
}
