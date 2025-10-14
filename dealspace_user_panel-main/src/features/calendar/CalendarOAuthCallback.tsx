"use client"

import { useEffect } from "react"
import { Calendar } from "lucide-react"
import { BASE_URL } from "../../utils/helpers"

const CalendarOAuthCallback = () => {
  useEffect(() => {
    const urlParams = new URLSearchParams(window.location.search)
    const code = urlParams.get("code")
    const error = urlParams.get("error")
    const state = urlParams.get("state")
    
    // Determine provider based on URL path
    const provider = window.location.pathname.includes("google") ? "google" : "outlook"
    
    // Handle OAuth error (user denied access)
    if (error) {
      console.error("OAuth error:", error)
      if (window.opener) {
        window.opener.postMessage({ 
          success: false, 
          error: error === "access_denied" ? "Access denied by user" : "Authentication failed",
          provider 
        }, "*")
        window.close()
      }
      return
    }

    if (code) {
      // Send code to backend for token exchange
      fetch(`${BASE_URL}/calendars/${provider}/callback?code=${code}${state ? `&state=${state}` : ""}`, {
        method: "GET",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${localStorage.getItem("token")}`,
          Accept: "application/json",
        },
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            // Close popup and notify parent window
            if (window.opener) {
              window.opener.postMessage({ 
                success: true, 
                provider,
                account: data.account,
                message: data.message || `${provider === "google" ? "Google" : "Outlook"} Calendar connected successfully`
              }, "*")
              window.close()
            } else {
              // Redirect to calendar page if not in popup
              window.location.href = "/calendar"
            }
          } else {
            console.error("Calendar OAuth failed:", data.message)
            if (window.opener) {
              window.opener.postMessage({ 
                success: false, 
                error: data.message || "Failed to connect calendar",
                provider 
              }, "*")
              window.close()
            }
          }
        })
        .catch((error) => {
          console.error("Calendar OAuth error:", error)
          if (window.opener) {
            window.opener.postMessage({ 
              success: false, 
              error: "Network error occurred while connecting calendar",
              provider 
            }, "*")
            window.close()
          }
        })
    } else {
      // No code parameter found
      if (window.opener) {
        window.opener.postMessage({ 
          success: false, 
          error: "No authorization code received",
          provider 
        }, "*")
        window.close()
      }
    }
  }, [])

  return (
    <div className="flex items-center justify-center min-h-screen bg-gray-50">
      <div className="text-center p-8 bg-white rounded-lg shadow-lg max-w-md">
        <div className="flex justify-center mb-6">
          <Calendar className="w-16 h-16 text-blue-600" />
        </div>
        
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto mb-4"></div>
        
        <h2 className="text-xl font-semibold text-gray-800 mb-2">
          Connecting Calendar
        </h2>
        
        <p className="text-gray-600 mb-4">
          Processing authentication...
        </p>
        
        <div className="text-sm text-gray-500">
          This window will close automatically once the connection is complete.
        </div>
      </div>
    </div>
  )
}

export default CalendarOAuthCallback