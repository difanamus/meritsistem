import { NavLink, Outlet, useLocation, useNavigate } from 'react-router-dom'
import { useAuth } from '../auth/useAuth'
import { Icon } from './Icon'

export function AppShell() {
  const { user, logout } = useAuth()
  const navigate = useNavigate()
  const location = useLocation()
  const canManageUsers = user?.role === 'system_admin' || user?.role === 'admin_ssdm'

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
            <span className="nav-group-label">Ruang kerja</span>
            <NavLink to="/dashboard" aria-label="Dashboard">
              <span className="nav-icon"><Icon name="dashboard" /></span><span>Dashboard</span>
            </NavLink>
            <NavLink to="/personel" aria-label="Data Personel" className={() => location.pathname.startsWith('/personel') ? 'active' : ''}>
              <span className="nav-icon"><Icon name="people" /></span>
              <span>Data Personel</span>
            </NavLink>
            {canManageUsers && <span className="nav-group-label">Administrasi</span>}
            {canManageUsers && <NavLink to="/pengguna" aria-label="Pengguna dan Scope" className={() => location.pathname.startsWith('/pengguna') ? 'active' : ''}>
              <span className="nav-icon"><Icon name="shield" /></span>
              <span>Pengguna & Scope</span>
            </NavLink>}
            <NavLink to="/referensi" aria-label="Data Referensi" className={() => location.pathname.startsWith('/referensi') ? 'active' : ''}>
              <span className="nav-icon"><Icon name="briefcase" /></span>
              <span>Data Referensi</span>
            </NavLink>
            {user?.role === 'system_admin' && <NavLink to="/sistem" aria-label="Monitoring Sistem">
              <span className="nav-icon"><Icon name="server" /></span><span>Monitoring Sistem</span>
            </NavLink>}
          </nav>

          <div className="scope-card">
            <span className="eyebrow">Cakupan akses</span>
            <strong>{user?.role_label}</strong>
            <p>{user?.role === 'operator' ? (user.permissions?.create_personnel ? 'Unit sesuai scope aktif · lihat dashboard' : 'Belum memiliki scope aktif') : 'Seluruh organisasi POLRI'}</p>
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
