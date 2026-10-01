import { useEffect, useMemo, useState } from 'react'
import type { FormEvent } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { useAuth } from '../auth/useAuth'
import { Icon } from '../components/Icon'
import { ApiError, apiRequest, toQueryString } from '../lib/api'
import type { PaginationMeta, User } from '../types'

interface UserResponse {
  data: User[]
  meta: PaginationMeta
}

export function UserListPage() {
  const { user: actor } = useAuth()
  const [searchParams, setSearchParams] = useSearchParams()
  const [users, setUsers] = useState<User[]>([])
  const [meta, setMeta] = useState<PaginationMeta | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [notice, setNotice] = useState('')
  const [searchDraft, setSearchDraft] = useState(searchParams.get('search') ?? '')
  const [deactivating, setDeactivating] = useState<number | null>(null)

  const query = useMemo(() => ({
    search: searchParams.get('search') || undefined,
    role: searchParams.get('role') || undefined,
    status: searchParams.get('status') || undefined,
    sort: searchParams.get('sort') || 'name',
    direction: searchParams.get('direction') || 'asc',
    page: searchParams.get('page') || '1',
    per_page: 10,
  }), [searchParams])

  const loadUsers = async () => {
    setLoading(true)
    try {
      const response = await apiRequest<UserResponse>(`/users${toQueryString(query)}`)
      setUsers(response.data)
      setMeta(response.meta)
      setError('')
    } catch (exception) {
      setError(exception instanceof ApiError ? exception.message : 'Daftar pengguna tidak dapat dimuat.')
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    document.title = 'Pengguna & Scope · Merit SDM POLRI'
    void loadUsers()
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [query])

  const setFilter = (key: string, value: string) => {
    const next = new URLSearchParams(searchParams)
    if (value) next.set(key, value)
    else next.delete(key)
    if (key !== 'page') next.delete('page')
    setSearchParams(next)
  }

  const submitSearch = (event: FormEvent) => {
    event.preventDefault()
    setFilter('search', searchDraft.trim())
  }

  const deactivate = async (managedUser: User) => {
    if (!window.confirm(`Nonaktifkan akun ${managedUser.name}? Semua sesi login akun ini akan dicabut.`)) return
    setDeactivating(managedUser.id)
    setNotice('')
    setError('')
    try {
      const response = await apiRequest<{ message: string }>(`/users/${managedUser.id}`, { method: 'DELETE' })
      setNotice(response.message)
      await loadUsers()
    } catch (exception) {
      setError(exception instanceof ApiError ? exception.message : 'Akun tidak dapat dinonaktifkan.')
    } finally {
      setDeactivating(null)
    }
  }

  const activeCount = users.filter((item) => item.is_active).length

  return <div className="page-wrap">
    <header className="page-header reveal">
      <div><span className="eyebrow">Kontrol akses</span><h1>Pengguna & Scope</h1><p>Kelola akun, role, dan batas kewenangan unit organisasi secara terpusat.</p></div>
      <Link className="primary-cta compact" to="/pengguna/tambah"><span>Tambah pengguna</span><span className="cta-icon">+</span></Link>
    </header>

    <section className="metric-row reveal delay-one" aria-label="Ringkasan pengguna">
      <div className="metric-shell"><div className="metric-core"><span>Total ditemukan</span><strong>{meta?.total ?? '—'}</strong><small>akun sesuai filter</small></div></div>
      <div className="metric-shell accent"><div className="metric-core"><span>Aktif di halaman ini</span><strong>{activeCount}</strong><small>dapat menggunakan sistem</small></div></div>
      <div className="metric-shell"><div className="metric-core"><span>Kewenangan Anda</span><strong className="text-metric">{actor?.role_label}</strong><small>{actor?.role === 'system_admin' ? 'seluruh role dan scope' : 'khusus akun operator'}</small></div></div>
    </section>

    <section className="content-shell reveal delay-two"><div className="content-core">
      <div className="filter-bar user-filter-bar">
        <form className="search-box" onSubmit={submitSearch}><Icon name="search" size={17}/><input value={searchDraft} onChange={(event) => setSearchDraft(event.target.value)} placeholder="Cari nama atau email…"/><button type="submit">Cari</button></form>
        {actor?.role === 'system_admin' && <select value={searchParams.get('role') ?? ''} onChange={(event) => setFilter('role', event.target.value)}><option value="">Semua role</option><option value="system_admin">System Admin</option><option value="admin_ssdm">Admin SSDM</option><option value="operator">Operator</option></select>}
        <select value={searchParams.get('status') ?? ''} onChange={(event) => setFilter('status', event.target.value)}><option value="">Semua status</option><option value="active">Aktif</option><option value="inactive">Nonaktif</option></select>
        <select value={searchParams.get('sort') ?? 'name'} onChange={(event) => setFilter('sort', event.target.value)}><option value="name">Nama</option><option value="email">Email</option><option value="role">Role</option><option value="created_at">Terbaru</option></select>
      </div>
      {notice && <div className="alert success">{notice}</div>}
      {error && <div className="alert error">{error}</div>}
      <div className={`data-table-wrap ${loading ? 'is-loading' : ''}`}><table className="data-table user-table">
        <thead><tr><th>Pengguna</th><th>Role</th><th>Cakupan organisasi</th><th>Status</th><th>Aksi</th></tr></thead>
        <tbody>
          {!loading && users.map((managedUser) => {
            const isSelf = managedUser.id === actor?.id
            return <tr key={managedUser.id}>
              <td><div className="person-cell"><span className="person-monogram">{managedUser.name.split(' ').map((part) => part[0]).slice(0, 2).join('')}</span><div><strong>{managedUser.name}</strong><small>{managedUser.email}{isSelf ? ' · akun Anda' : ''}</small></div></div></td>
              <td><span className={`role-pill ${managedUser.role}`}>{managedUser.role_label}</span></td>
              <td><div className="scope-summary">{managedUser.role === 'operator' ? managedUser.scopes.map((scope) => <span key={scope.id}>{scope.unit_organisasi.nama}<small>{scope.scope_type_label}</small></span>) : <span>Seluruh organisasi<small>Akses global</small></span>}</div></td>
              <td><span className={`status-pill ${managedUser.is_active ? 'aktif' : 'nonaktif'}`}>{managedUser.is_active ? 'Aktif' : 'Nonaktif'}</span></td>
              <td><div className="table-actions">{!isSelf && <Link className="text-action" to={`/pengguna/${managedUser.id}/edit`}>{managedUser.is_active ? 'Edit' : 'Aktifkan'}</Link>}{!isSelf && managedUser.is_active && <button type="button" className="danger-action" disabled={deactivating === managedUser.id} onClick={() => void deactivate(managedUser)}>{deactivating === managedUser.id ? 'Memproses…' : 'Nonaktifkan'}</button>}{isSelf && <span className="muted-action">Terkunci</span>}</div></td>
            </tr>
          })}
          {loading && Array.from({ length: 5 }).map((_, index) => <tr className="skeleton-row" key={index}><td colSpan={5}><span/></td></tr>)}
        </tbody>
      </table>{!loading && users.length === 0 && <div className="empty-state"><Icon name="shield" size={28}/><strong>Belum ada pengguna</strong><p>Ubah filter atau tambahkan akun baru.</p></div>}</div>
      {meta && meta.last_page > 1 && <div className="pagination"><button disabled={meta.current_page === 1} onClick={() => setFilter('page', String(meta.current_page - 1))}>Sebelumnya</button><span>Halaman {meta.current_page} dari {meta.last_page}</span><button disabled={meta.current_page === meta.last_page} onClick={() => setFilter('page', String(meta.current_page + 1))}>Berikutnya</button></div>}
    </div></section>
  </div>
}
