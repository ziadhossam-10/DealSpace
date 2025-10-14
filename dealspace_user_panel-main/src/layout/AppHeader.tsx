"use client"

import type React from "react"
import { useEffect, useRef, useState } from "react"
import { BarChart2, Briefcase, Calendar, Mail, Settings, Users, Workflow } from "lucide-react"
import { Link, useLocation } from "react-router-dom"
import NotificationDropdown from "../features/notifications/NotificationDropdown"
import UserDropdown from "../components/header/UserDropdown"
import { useSidebar } from "../context/SidebarContext"
import { Toaster } from 'react-hot-toast';
const AppHeader: React.FC = () => {
  const [isApplicationMenuOpen, setApplicationMenuOpen] = useState(false)
  const { isMobileOpen, toggleSidebar, toggleMobileSidebar } = useSidebar()
  const inputRef = useRef<HTMLInputElement>(null)
  const location = useLocation()
  const isActive = (route: string) => location.pathname.startsWith(route)

  const handleToggle = () => {
    if (window.innerWidth >= 991) {
      toggleSidebar()
    } else {
      toggleMobileSidebar()
    }
  }

  const toggleApplicationMenu = () => {
    setApplicationMenuOpen(!isApplicationMenuOpen)
  }

  useEffect(() => {
    const handleKeyDown = (event: KeyboardEvent) => {
      if ((event.metaKey || event.ctrlKey) && event.key === "k") {
        event.preventDefault()
        inputRef.current?.focus()
      }
    }

    document.addEventListener("keydown", handleKeyDown)

    return () => {
      document.removeEventListener("keydown", handleKeyDown)
    }
  }, [])

  return (
    <header className="sticky top-0 flex w-full bg-black text-white z-99999">
      <div className="flex flex-col items-center justify-between flex-grow lg:flex-row lg:px-6">
        <div className="flex items-center justify-between w-full gap-2 px-3 py-3 border-b border-slate-600 sm:gap-4 lg:justify-normal lg:border-b-0 lg:px-0 lg:py-2">
          <div className="hidden lg:flex lg:items-center lg:space-x-6">
            <button className="p-2">
              <img src="/images/logo.jpeg" className="w-[40px]" alt="logo" />
            </button>
            <Link
              to="/people"
              className={`flex items-center space-x-1 ${isActive("/people") ? "text-white font-semibold" : "text-gray-300"}`}
            >
              <Users size={18} />
              <span>People</span>
            </Link>
            <Link
              to="/inbox"
              className={`flex items-center space-x-1 ${isActive("/inbox") ? "text-white font-semibold" : "text-gray-300"}`}
            >
              <Mail size={18} />
              <span>Inbox</span>
            </Link>
            <Link
              to={'/calendar'}
              className={`flex items-center space-x-1 ${isActive("/calendar") ? "text-white font-semibold" : "text-gray-300"}`}
            >
              <Calendar size={18} />
              <span>Calendar</span>
            </Link>
            <Link
              to={"/deals"}
              className={`flex items-center space-x-1 ${isActive("/deals") ? "text-white font-semibold" : "text-gray-300"}`}
            >
              <Briefcase size={18} />
              <span>Deals</span>
            </Link>
            <Link
              to={"/reports"}
              className={`flex items-center space-x-1 ${isActive("/reports") ? "text-white font-semibold" : "text-gray-300"}`}
            >
              <BarChart2 size={18} />
              <span>Reporting</span>
            </Link>
            <Link
              to={"/admin/overview"}
              className={`flex items-center space-x-1 ${isActive("/admin") ? "text-white font-semibold" : "text-gray-300"}`}
            >
              <Settings size={18} />
              <span>Admin</span>
            </Link>
            <Link
              to={"/integrations"}
              className={`flex items-center space-x-1 ${isActive("/integrations") ? "text-white font-semibold" : "text-gray-300"}`}
            >
              <Workflow size={18} />
              <span>Integrations</span>
            </Link>
          </div>

          <button
            onClick={toggleApplicationMenu}
            className="flex items-center justify-center w-10 h-10 text-gray-300 rounded-lg z-99999 hover:bg-slate-600 lg:hidden"
          >
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path
                fillRule="evenodd"
                clipRule="evenodd"
                d="M5.99902 10.4951C6.82745 10.4951 7.49902 11.1667 7.49902 11.9951V12.0051C7.49902 12.8335 6.82745 13.5051 5.99902 13.5051C5.1706 13.5051 4.49902 12.8335 4.49902 12.0051V11.9951C4.49902 11.1667 5.1706 10.4951 5.99902 10.4951ZM17.999 10.4951C18.8275 10.4951 19.499 11.1667 19.499 11.9951V12.0051C19.499 12.8335 18.8275 13.5051 17.999 13.5051C17.1706 13.5051 16.499 12.8335 16.499 12.0051V11.9951C16.499 11.1667 17.1706 10.4951 17.999 10.4951ZM13.499 11.9951C13.499 11.1667 12.8275 10.4951 11.999 10.4951C11.1706 10.4951 10.499 11.1667 10.499 11.9951V12.0051C10.499 12.8335 11.1706 13.5051 11.999 13.5051C12.8275 13.5051 13.499 12.8335 13.499 12.0051V11.9951Z"
                fill="currentColor"
              />
            </svg>
          </button>
        </div>

        <div
          className={`${
            isApplicationMenuOpen ? "flex" : "hidden"
          } lg:flex items-center justify-between w-full gap-4 px-5 py-4 lg:justify-end lg:px-0`}
        >
          <div className="flex items-center gap-4">
            <NotificationDropdown />
            <UserDropdown />
          </div>
        </div>
      </div>
      <Toaster position="top-right" />
    </header>
  )
}

export default AppHeader
