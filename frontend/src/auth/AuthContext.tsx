import { useEffect, useMemo, useState } from 'react'
import type { ReactNode } from 'react'
import { apiRequest, getToken, setToken } from '../lib/api'
import type { User } from '../types'
import { AuthContext } from './auth-context'

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null)
  const [loading, setLoading] = useState(Boolean(getToken()))

  useEffect(() => {
    if (!getToken()) return
    void apiRequest<{ data: User }>('/auth/me')
      .then((response) => setUser(response.data))
      .catch(() => {
        setToken(null)
        setUser(null)
      })
      .finally(() => setLoading(false))
  }, [])

  const login = async (email: string, password: string) => {
    const response = await apiRequest<{ data: { user: User; token: string } }>('/auth/login', {
      method: 'POST',
      body: JSON.stringify({ email, password, device_name: 'react-web' }),
    })
    setToken(response.data.token)
    setUser(response.data.user)
  }

  const logout = async () => {
    try {
      await apiRequest('/auth/logout', { method: 'POST' })
    } finally {
      setToken(null)
      setUser(null)
    }
  }

  const value = useMemo(() => ({ user, loading, login, logout }), [user, loading])

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}
