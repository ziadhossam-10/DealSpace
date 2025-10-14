"use client"
import {
  Briefcase,
  Plus,
  ChevronRight,
  Edit,
  Trash2,
  Calendar,
  DollarSign,
  Users,
  ChevronDown,
  Loader2,
} from "lucide-react"

// Import the correct Deal type from deals module
import type { Deal } from "../../../types/deals"

interface PersonDealsSectionProps {
  personId: number
  deals: Deal[]
  totalDeals: number
  currentPage: number
  hasMore: boolean
  isLoading: boolean
  isLoadingMore: boolean
  isExpanded: boolean
  onToggleExpanded: () => void
  onAddDeal: () => void
  onEditDeal: (deal: Deal) => void
  onDeleteDeal: (deal: Deal) => void
  onLoadMore: () => void
}

export const PersonDealsSection = ({
  personId,
  deals,
  totalDeals,
  currentPage,
  hasMore,
  isLoading,
  isLoadingMore,
  isExpanded,
  onToggleExpanded,
  onAddDeal,
  onEditDeal,
  onDeleteDeal,
  onLoadMore,
}: PersonDealsSectionProps) => {
  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat("en-US", {
      style: "currency",
      currency: "USD",
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }).format(amount)
  }

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString("en-US", {
      month: "short",
      day: "numeric",
      year: "numeric",
    })
  }

  return (
    <div className="p-4 border-b">
      <div className="w-full">
        <div className="flex items-center justify-between">
          <div className="flex items-center">
            <Briefcase size={16} className="mr-2 text-gray-500" />
            <span className="font-medium">Deals</span>
          </div>
          <div className="flex items-center">
            <button
              className="h-8 w-8 p-0 rounded-full flex items-center justify-center hover:bg-gray-100"
              onClick={onAddDeal}
            >
              <Plus size={16} className="text-blue-500" />
            </button>
            <button
              className="h-8 w-8 p-0 flex items-center justify-center hover:bg-gray-100"
              onClick={onToggleExpanded}
            >
              <ChevronRight
                size={16}
                className={`text-gray-500 transition-transform ${isExpanded ? "rotate-90" : ""}`}
              />
            </button>
          </div>
        </div>

        {isExpanded && (
          <div className="mt-2">
            {isLoading ? (
              <div className="flex items-center justify-center py-4">
                <div className="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
              </div>
            ) : deals.length > 0 ? (
              <div className="space-y-3 max-h-80 overflow-y-auto">
                {deals.map((deal) => (
                  <div key={deal.id} className="p-3 bg-gray-50 rounded-md hover:bg-gray-100 transition-colors group">
                    <div className="flex items-start justify-between">
                      <div className="flex-1 min-w-0">
                        <div className="flex items-center gap-2 mb-1">
                          <h4 className="font-medium text-gray-900 truncate text-sm">{deal.name}</h4>
                          {deal.stage && (
                            <div
                              className="w-2 h-2 rounded-full"
                              style={{ backgroundColor: deal.stage.color }}
                              title={deal.stage.name}
                            />
                          )}
                        </div>

                        {deal.description && (
                          <p className="text-xs text-gray-600 mb-2 line-clamp-2">{deal.description}</p>
                        )}

                        <div className="flex items-center gap-3 text-xs text-gray-500">
                          <div className="flex items-center gap-1">
                            <DollarSign size={12} />
                            <span className="font-medium text-green-600">{formatCurrency(deal.price)}</span>
                          </div>

                          {deal.projected_close_date && (
                            <div className="flex items-center gap-1">
                              <Calendar size={12} />
                              <span>{formatDate(deal.projected_close_date)}</span>
                            </div>
                          )}

                          {deal.users && deal.users.length > 0 && (
                            <div className="flex items-center gap-1">
                              <Users size={12} />
                              <span>
                                {deal.users.length} user{deal.users.length !== 1 ? "s" : ""}
                              </span>
                            </div>
                          )}
                        </div>

                        {deal.stage && <div className="mt-2 text-xs text-gray-400">Stage: {deal.stage.name}</div>}
                      </div>

                      <div className="flex items-center space-x-1 ml-2 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button
                          onClick={() => onEditDeal(deal)}
                          className="p-1 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded transition-colors"
                          title="Edit deal"
                        >
                          <Edit className="w-3 h-3" />
                        </button>
                        <button
                          onClick={() => onDeleteDeal(deal)}
                          className="p-1 text-red-600 hover:text-red-800 hover:bg-red-50 rounded transition-colors"
                          title="Delete deal"
                        >
                          <Trash2 className="w-3 h-3" />
                        </button>
                      </div>
                    </div>
                  </div>
                ))}

                {hasMore && (
                  <div className="text-center pt-2">
                    <button
                      onClick={onLoadMore}
                      disabled={isLoadingMore}
                      className="inline-flex items-center px-3 py-2 text-xs font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-md transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                      {isLoadingMore ? (
                        <>
                          <Loader2 className="w-3 h-3 mr-1 animate-spin" />
                          Loading...
                        </>
                      ) : (
                        <>
                          <ChevronDown className="w-3 h-3 mr-1" />
                          Load More ({deals.length} of {totalDeals})
                        </>
                      )}
                    </button>
                  </div>
                )}
              </div>
            ) : (
              <div className="text-center py-4">
                <Briefcase className="w-8 h-8 text-gray-400 mx-auto mb-2" />
                <p className="text-gray-500 text-sm mb-2">No deals found</p>
                <button onClick={onAddDeal} className="text-sm text-blue-600 hover:text-blue-800 underline">
                  Create first deal
                </button>
              </div>
            )}
          </div>
        )}
      </div>
    </div>
  )
}
