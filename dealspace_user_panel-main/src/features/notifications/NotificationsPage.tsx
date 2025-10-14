"use client"

import type React from "react"
import { useState, useEffect, useCallback } from "react"
import { useLazyGetNotificationsPaginatedQuery, useMarkAsReadMutation } from "./notificationsApi"
import type { Notification } from "./notificationsApi"
import { formatDistanceToNow } from "date-fns"
import { useInView } from "react-intersection-observer"
import { ASSETS_URL } from "../../utils/helpers"

const NotificationsPage: React.FC = () => {
  const [notifications, setNotifications] = useState<Notification[]>([])
  const [currentPage, setCurrentPage] = useState(1)
  const [hasMore, setHasMore] = useState(true)
  const [isLoadingMore, setIsLoadingMore] = useState(false)
  const [isInitialLoad, setIsInitialLoad] = useState(true)

  const [getNotifications, { isLoading: isQueryLoading }] = useLazyGetNotificationsPaginatedQuery()
  const [markAsRead] = useMarkAsReadMutation()

  const { ref: loadMoreRef, inView } = useInView({
    threshold: 0,
    rootMargin: "100px",
  })

  const loadNotifications = useCallback(
    async (page: number, reset = false) => {
      try {
        if (page === 1) {
          setIsInitialLoad(true)
        } else {
          setIsLoadingMore(true)
        }

        const result = await getNotifications({
          page,
          per_page: 10, // Changed from 20 to 10
        }).unwrap()

        if (result.data.items.length === 0) {
          setHasMore(false)
          return
        }

        setNotifications((prev) => {
          if (reset) {
            return result.data.items
          }

          // Deduplicate to prevent duplicates
          const existingIds = new Set(prev.map((n) => n.id))
          const newItems = result.data.items.filter((item) => !existingIds.has(item.id))

          return [...prev, ...newItems]
        })

        setHasMore(page < result.data.meta.last_page)
        setCurrentPage(page)
      } catch (error) {
        console.error("Failed to load notifications:", error)
      } finally {
        setIsLoadingMore(false)
        setIsInitialLoad(false)
      }
    },
    [getNotifications],
  )

  // Initial load
  useEffect(() => {
    loadNotifications(1, true)
  }, [loadNotifications])

  // Load more when scrolling to bottom
  useEffect(() => {
    if (inView && hasMore && !isLoadingMore && !isInitialLoad) {
      const nextPage = currentPage + 1
      loadNotifications(nextPage, false)
    }
  }, [inView, hasMore, isLoadingMore, isInitialLoad, currentPage, loadNotifications])

  const handleNotificationClick = async (notification: Notification) => {
    if (!notification.read_at) {
      try {
        await markAsRead({ notificationId: notification.id }).unwrap()

        // Update local state
        setNotifications((prev) =>
          prev.map((n) => (n.id === notification.id ? { ...n, read_at: new Date().toISOString() } : n)),
        )
      } catch (error) {
        console.error("Failed to mark notification as read:", error)
      }
    }

    // Navigate to notification action if it's a valid URL
    if (notification.action && notification.action !== "/anything") {
      window.location.href = notification.action
    }
  }

  const formatTimeAgo = (dateString: string) => {
    try {
      return formatDistanceToNow(new Date(dateString), { addSuffix: true })
    } catch {
      return "Unknown time"
    }
  }

  const getNotificationIcon = (notification: Notification) => {
    if (notification.image) {
      return (
        <img
          width={48}
          height={48}
          src={ASSETS_URL + '/storage/' + notification.image || "/placeholder.svg"}
          alt="Notification"
          className="w-12 h-12 rounded-full object-cover"
        />
      )
    }

    return (
      <div className="flex items-center justify-center w-12 h-12 bg-blue-500 rounded-full">
        <svg className="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
          <path
            fillRule="evenodd"
            clipRule="evenodd"
            d="M10 2C6.686 2 4 4.686 4 8v4.586L2.293 14.293A1 1 0 0 0 3 16h14a1 1 0 0 0 .707-1.707L16 12.586V8c0-3.314-2.686-6-6-6zM8 18a2 2 0 1 0 4 0H8z"
          />
        </svg>
      </div>
    )
  }

  if (isInitialLoad) {
    return (
      <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
        <div className="max-w-4xl mx-auto px-4 py-8">
          <div className="flex items-center justify-center py-12">
            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
          </div>
        </div>
      </div>
    )
  }

  return (
    <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
      <div className="max-w-4xl mx-auto px-4 py-8">
        <div className="mb-8">
          <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Notifications</h1>
          <p className="mt-2 text-gray-600 dark:text-gray-400">Stay updated with your latest notifications</p>
        </div>

        <div className="space-y-4">
          {notifications.length === 0 ? (
            <div className="text-center py-12">
              <div className="w-24 h-24 mx-auto mb-4 text-gray-400">
                <svg fill="currentColor" viewBox="0 0 20 20">
                  <path
                    fillRule="evenodd"
                    clipRule="evenodd"
                    d="M10 2C6.686 2 4 4.686 4 8v4.586L2.293 14.293A1 1 0 0 0 3 16h14a1 1 0 0 0 .707-1.707L16 12.586V8c0-3.314-2.686-6-6-6zM8 18a2 2 0 1 0 4 0H8z"
                  />
                </svg>
              </div>
              <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">No notifications yet</h3>
              <p className="text-gray-500 dark:text-gray-400">When you receive notifications, they'll appear here.</p>
            </div>
          ) : (
            notifications.map((notification) => (
              <div
                key={notification.id}
                onClick={() => handleNotificationClick(notification)}
                className={`bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 cursor-pointer transition-all hover:shadow-md ${
                  !notification.read_at ? "border-l-4 border-l-blue-500 bg-blue-50 dark:bg-blue-900/20" : ""
                }`}
              >
                <div className="flex items-start space-x-4">
                  <div className="flex-shrink-0">{getNotificationIcon(notification)}</div>

                  <div className="flex-1 min-w-0">
                    <div className="flex items-start justify-between">
                      <div className="flex-1">
                        <h3 className="text-lg font-medium text-gray-900 dark:text-white">{notification.title}</h3>
                        <p className="mt-1 text-gray-600 dark:text-gray-300">{notification.message}</p>
                      </div>

                      {!notification.read_at && (
                        <div className="flex-shrink-0 ml-4">
                          <div className="w-3 h-3 bg-blue-500 rounded-full"></div>
                        </div>
                      )}
                    </div>

                    <div className="mt-3 flex items-center text-sm text-gray-500 dark:text-gray-400">
                      <time dateTime={notification.created_at}>{formatTimeAgo(notification.created_at)}</time>
                      {notification.read_at && (
                        <>
                          <span className="mx-2">•</span>
                          <span>Read</span>
                        </>
                      )}
                    </div>
                  </div>
                </div>
              </div>
            ))
          )}

          {/* Load more trigger */}
          {hasMore && (
            <div ref={loadMoreRef} className="py-8">
              {isLoadingMore && (
                <div className="flex items-center justify-center">
                  <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                  <span className="ml-3 text-gray-600 dark:text-gray-400">Loading more notifications...</span>
                </div>
              )}
            </div>
          )}

          {!hasMore && notifications.length > 0 && (
            <div className="text-center py-8">
              <p className="text-gray-500 dark:text-gray-400">You've reached the end of your notifications</p>
            </div>
          )}
        </div>
      </div>
    </div>
  )
}

export default NotificationsPage
