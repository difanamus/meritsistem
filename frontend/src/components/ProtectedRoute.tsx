import { Navigate, Outlet } from 'react-router-dom'
import { useAuth } from '../auth/useAuth'
import type { UserRole } from '../types'

export function ProtectedRoute() {
  const { user, loading } = useAuth()

  if (loading) {
    return <div className="loading-screen"><span className="loading-mark">M</span><p>Menyiapkan ruang kerja…</p></div>
  }

  return user ? <Outlet /> : <Navigate to="/login" replace />
}

export function RoleProtectedRoute({ roles }: { roles: UserRole[] }) {
  const { user } = useAuth()

  return user && roles.includes(user.role) ? <Outlet /> : <Navigate to="/403" replace />
}
