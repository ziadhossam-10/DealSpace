"use client"

import { useEffect } from "react"
import { BASE_URL } from "../../utils/helpers"

const OAuthCallback = () => {
  useEffect(() => {
    const urlParams = new URLSearchParams(window.location.search)
    const code = urlParams.get("code")
    const provider = window.location.pathname.includes("google") ? "google" : "microsoft"

    if (code) {
      // Send code to backend
      fetch(`${BASE_URL}/oauth/${provider}/callback?code=${code}`, {
        headers: {
          Authorization: `Bearer ${localStorage.getItem("token")}`,
          Accept: "application/json",
        },
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            // Close popup and notify parent
            if (window.opener) {
              window.opener.postMessage({ success: true, provider }, "*")
              window.close()
            } else {
              // Redirect to main app
              window.location.href = "/inbox"
            }
          } else {
            console.error("OAuth failed:", data.message)
            if (window.opener) {
              window.opener.postMessage({ success: false, error: data.message }, "*")
              window.close()
            }
          }
        })
        .catch((error) => {
          console.error("OAuth error:", error)
          if (window.opener) {
            window.opener.postMessage({ success: false, error: "Connection failed" }, "*")
            window.close()
          }
        })
    }
  }, [])

  return (
    <div className="flex items-center justify-center min-h-screen">
      <div className="text-center">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto"></div>
        <p className="mt-4 text-gray-600">Processing authentication...</p>
      </div>
    </div>
  )
}

export default OAuthCallback
