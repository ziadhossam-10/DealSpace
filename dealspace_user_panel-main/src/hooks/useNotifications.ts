"use client"

import { useEffect, useState, useCallback, useRef } from "react"
import {
  useGetNotificationsQuery,
  useGetUnreadCountQuery,
  useMarkAllAsReadMutation,
} from "../features/notifications/notificationsApi"
import type { Notification } from "../features/notifications/notificationsApi"
import echo from "../app/echo"
import { useAppSelector } from "../app/hooks"

interface UseNotificationsProps {
  enabled?: boolean
  callback: (newNotification: any) => void
}

export const useNotifications = ({ enabled = true, callback }: UseNotificationsProps) => {
  const [notifications, setNotifications] = useState<Notification[]>([])
  const [hasNewNotification, setHasNewNotification] = useState(false)

  // Use ref to store the latest callback to avoid re-subscriptions
  const callbackRef = useRef(callback)

  // Update the ref when callback changes
  useEffect(() => {
    callbackRef.current = callback
  }, [callback])

  const {
    data: notificationsData,
    isLoading,
    error,
    refetch,
  } = useGetNotificationsQuery({ page: 1, per_page: 10 }, { skip: !enabled })

  const { data: unreadCountData, refetch: refetchUnreadCount } = useGetUnreadCountQuery()

  const [markAllAsRead] = useMarkAllAsReadMutation()

  const userId = useAppSelector((state) => state.auth.user?.id)

  // Helper function to deduplicate notifications
  const deduplicateNotifications = useCallback(
    (newNotifications: Notification[], existingNotifications: Notification[]) => {
      const existingIds = new Set(existingNotifications.map((n) => n.id))
      const uniqueNew = newNotifications.filter((n) => !existingIds.has(n.id))
      return [...uniqueNew, ...existingNotifications]
    },
    [],
  )

  // Update local notifications when API data changes
  useEffect(() => {
    if (notificationsData?.data?.items) {
      setNotifications((prev) => {
        // If this is the initial load (prev is empty), just set the data
        if (prev.length === 0) {
          return notificationsData.data.items
        }

        // Otherwise, merge with existing notifications and deduplicate
        return deduplicateNotifications(notificationsData.data.items, prev)
      })
    }
  }, [notificationsData, deduplicateNotifications])

  // Handle real-time notifications with proper cleanup
  useEffect(() => {
    if (!userId || !enabled) return

    const channelName = `notifications.${userId}`
    const channel = echo.channel(channelName)

    const handleNewNotification = (data: any) => {
      console.log("🔔 New notification received:", data)

      // Extract the notification from the data
      const newNotification = data.notification || data

      if (newNotification) {
        // Use the ref to call the latest callback
        callbackRef.current(newNotification)
      }

      // Add new notification to the beginning of the list with deduplication
      setNotifications((prev) => {
        // Check if notification already exists
        const exists = prev.some((n) => n.id === newNotification.id)
        if (exists) {
          console.log("Notification already exists, skipping duplicate")
          return prev
        }

        return [newNotification, ...prev]
      })

      setHasNewNotification(true)

      // Refetch unread count but don't refetch notifications to avoid duplicates
      refetchUnreadCount()
    }

    channel.listen(".notification", handleNewNotification)

    // Proper cleanup function
    return () => {
      console.log("Cleaning up notification channel:", channelName)
      channel.stopListening(".notification", handleNewNotification)
      echo.leave(channelName)
    }
  }, [userId, enabled, refetchUnreadCount]) // Removed callback from dependencies

  const handleMarkAllAsRead = useCallback(async () => {
    try {
      await markAllAsRead().unwrap()
      setHasNewNotification(false)
      // Update local notifications to mark them as read
      setNotifications((prev) =>
        prev.map((notification) => ({
          ...notification,
          read_at: new Date().toISOString(),
        })),
      )
    } catch (error) {
      console.error("Failed to mark all notifications as read:", error)
    }
  }, [markAllAsRead])

  const clearNewNotificationFlag = useCallback(() => {
    setHasNewNotification(false)
  }, [])

  return {
    notifications,
    unreadCount: unreadCountData?.data?.count || 0,
    hasNewNotification,
    isLoading,
    error,
    refetch,
    markAllAsRead: handleMarkAllAsRead,
    clearNewNotificationFlag,
  }
}
