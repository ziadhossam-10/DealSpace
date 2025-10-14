import { TrendingUp, Users, DollarSign, Target } from "lucide-react"
import type { DealTotals } from "./dealsApi"

interface DealsMetricsProps {
  totals: DealTotals
}

export default function DealsMetrics({ totals }: DealsMetricsProps) {
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

  const avgDealValue = totals?.deals_created > 0 ? totals?.total_deal_value / totals?.deals_created : 0

  const metrics = [
    {
      title: "Total Deals",
      value: formatNumber(totals?.deals_created),
      subtitle: `${formatNumber(totals?.deals_in_pipeline)} in pipeline`,
      icon: Users,
      color: "bg-blue-500",
      textColor: "text-blue-600",
      bgColor: "bg-blue-50",
    },
    {
      title: "Closed Won",
      value: formatNumber(totals?.deals_closed_won),
      subtitle: `${totals?.close_rate?.toFixed(1)}% close rate`,
      icon: Target,
      color: "bg-green-500",
      textColor: "text-green-600",
      bgColor: "bg-green-50",
    },
    {
      title: "Total Value",
      value: formatCurrency(totals?.total_deal_value),
      subtitle: `${formatCurrency(avgDealValue)} avg deal`,
      icon: DollarSign,
      color: "bg-purple-500",
      textColor: "text-purple-600",
      bgColor: "bg-purple-50",
    },
    {
      title: "Closed Value",
      value: formatCurrency(totals?.closed_deal_value),
      subtitle: `${formatCurrency(totals?.total_commission)} commission`,
      icon: TrendingUp,
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
