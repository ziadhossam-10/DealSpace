import { Navigate, Outlet } from "react-router-dom"
import { useAuth } from "../hooks/useAuth"

interface RoleBasedRouteProps {
  allowedRoles: number[]
  redirectTo?: string
}

const RoleBasedRoute = ({ allowedRoles, redirectTo = "/404" }: RoleBasedRouteProps) => {
  const { role, isAuthenticated } = useAuth()

  if (!isAuthenticated) {
    return <Navigate to="/login" replace />
  }

  if (role === null || role === undefined) {
    return <Navigate to={redirectTo} replace />
  }

  const hasAccess = allowedRoles.includes(role)

  return hasAccess ? <Outlet /> : <Navigate to={redirectTo} replace />
}

export default RoleBasedRoute