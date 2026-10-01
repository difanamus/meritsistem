import { NavLink, Outlet, useLocation, useNavigate } from 'react-router-dom'
import { useAuth } from '../auth/useAuth'
import { Icon } from './Icon'

export function AppShell() {
  const { user, logout } = useAuth()
  const navigate = useNavigate()
  const location = useLocation()
  const isDetail = location.pathname.split('/').length > 2

  const handleLogout = async () => {
    await logout()
    navigate('/login', { replace: true })
  }

  return (
    <div className="app-frame">
      <aside className="sidebar-shell">
        <div className="sidebar-core">
          <div className="brand-lockup">
            <span className="brand-mark">MS</span>
            <div><strong>Merit SDM</strong><small>POLRI · Prototype</small></div>
          </div>

          <nav className="primary-nav" aria-label="Navigasi utama">
            <NavLink to="/personel" className={({ isActive }) => isActive && !isDetail ? 'active' : ''}>
              <span className="nav-icon"><Icon name="people" /></span>
              <span>Data Personel</span>
            </NavLink>
          </nav>

          <div className="scope-card">
            <span className="eyebrow">Cakupan akses</span>
            <strong>{user?.role_label}</strong>
            <p>{user?.role === 'operator' ? user.scopes.map((scope) => scope.unit_organisasi.nama).join(', ') : 'Seluruh organisasi POLRI'}</p>
          </div>

          <div className="user-card">
            <span className="avatar">{user?.name.split(' ').map((part) => part[0]).slice(0, 2).join('')}</span>
            <div><strong>{user?.name}</strong><small>{user?.email}</small></div>
            <button type="button" onClick={handleLogout} aria-label="Keluar"><Icon name="logout" size={17} /></button>
          </div>
        </div>
      </aside>
      <main className="main-stage"><Outlet /></main>
    </div>
  )
}
