"use client"

import type React from "react"
import { createContext, useState, useContext, useEffect } from "react"

type Theme = "light" | "dark"

type ThemeContextType = {
  theme: Theme
  toggleTheme: () => void
}

const ThemeContext = createContext<ThemeContextType | undefined>(undefined)

export const ThemeProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  // Always set theme to light
  const [theme] = useState<Theme>("light")
  const [isInitialized, setIsInitialized] = useState(false)

  useEffect(() => {
    // Force light mode on initialization
    document.documentElement.classList.remove("dark")
    localStorage.setItem("theme", "light")
    setIsInitialized(true)
  }, [])

  // Toggle function is a no-op since we're forcing light mode
  const toggleTheme = () => {
    // This function is kept for API compatibility but does nothing
    console.log("Theme toggling is disabled - always using light mode")
  }

  return <ThemeContext.Provider value={{ theme, toggleTheme }}>{children}</ThemeContext.Provider>
}

export const useTheme = () => {
  const context = useContext(ThemeContext)
  if (context === undefined) {
    throw new Error("useTheme must be used within a ThemeProvider")
  }
  return context
}
