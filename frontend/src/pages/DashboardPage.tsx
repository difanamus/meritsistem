import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { useAuth } from '../auth/useAuth'
import { Icon } from '../components/Icon'
import { ApiError, apiRequest, peekApiCache } from '../lib/api'

interface Dashboard {
  scope: { global: boolean; unit_count: number }
  personnel: { total: number; aktif: number; pensiun: number; nonaktif: number }
  qualifications: number
  active_positions: number
  accounts: { total: number; active: number; label: string } | null
  recent_personnel: { id: number; nama_lengkap: string; unit: string; status: string; updated_at: string }[]
}

export function DashboardPage() {
  const { user } = useAuth()
  const [data, setData] = useState<Dashboard | null>(() => peekApiCache<{ data: Dashboard }>('/dashboard')?.data ?? null)
  const [error, setError] = useState('')
  const [attempt, setAttempt] = useState(0)
  useEffect(() => {
    let active = true
    document.title = 'Dashboard · Merit SDM POLRI'
    apiRequest<{ data: Dashboard }>('/dashboard').then((response) => {
      if (active) { setData(response.data); setError('') }
    }).catch((exception) => { if (active) { if (exception instanceof ApiError && [401, 403].includes(exception.status)) setData(null); setError('Ringkasan belum dapat dimuat. Periksa koneksi lalu coba kembali.') } })
    return () => { active = false }
  }, [attempt])
  const operator = user?.role === 'operator'
  const system = user?.role === 'system_admin'
  return <div className="page-wrap">
    <header className="page-header reveal"><div><span className="eyebrow">{user?.role_label} · Ruang kerja</span>
      <h1>{operator ? 'Ringkasan Unit' : system ? 'Kendali Sistem' : 'Ringkasan Nasional'}</h1>
      <p>{operator ? 'Data personel dalam kewenangan scope aktif Anda.' : system ? 'Administrasi akses, data organisasi, dan kesehatan layanan.' : 'Informasi faktual untuk mendukung pembinaan karier personel Polri.'}</p>
    </div>{user?.permissions?.create_personnel && <Link to="/personel/tambah" className="primary-cta compact">Tambah personel<span className="cta-icon">+</span></Link>}</header>
    {error && <div className="alert error" role="alert">{error} <button className="text-action" onClick={() => setAttempt(attempt + 1)}>Coba lagi</button></div>}
    {!data && !error && <div className="empty-inline" role="status">Memuat ringkasan sesuai cakupan akses…</div>}
    {data && <>
      {operator && data.scope.unit_count === 0 && <div className="alert">Belum ada scope aktif. Hubungi Admin SSDM atau System Admin untuk penetapan cakupan unit.</div>}
      <section className="metric-row" aria-label="Ringkasan cakupan akses">
        <div className="metric-shell accent"><div className="metric-core"><span>Personel dalam cakupan</span><strong>{data.personnel.total}</strong><small>{data.scope.unit_count} unit organisasi · {data.scope.global ? 'akses nasional' : 'scope aktif'}</small></div></div>
        <div className="metric-shell"><div className="metric-core"><span>Total catatan kualifikasi</span><strong>{data.qualifications}</strong><small>jumlah catatan kualifikasi, bukan jumlah personel</small></div></div>
        <div className="metric-shell"><div className="metric-core"><span>Penugasan jabatan aktif</span><strong>{data.active_positions}</strong><small>jabatan utama dan penugasan tambahan</small></div></div>
      </section>
      <div className="dashboard-grid">
        <section className="content-shell"><div className="content-core dashboard-panel"><span className="eyebrow">Status personel</span><h2>Komposisi data</h2>
          <dl className="dashboard-facts">{(['aktif', 'pensiun', 'nonaktif'] as const).map((status) => <div key={status}><dt><Link to={`/personel?status=${status}`}>{status === 'aktif' ? 'Aktif' : status === 'pensiun' ? 'Pensiun' : 'Nonaktif'}</Link></dt><dd>{data.personnel[status]}</dd></div>)}</dl>
          <p className="dashboard-note">Jumlah catatan bukan penilaian kualitas atau rekomendasi promosi.</p>
        </div></section>
        <section className="content-shell"><div className="content-core dashboard-panel"><span className="eyebrow">Akses cepat</span><h2>{operator ? 'Kelola data unit' : 'Administrasi terarah'}</h2>
          <div className="dashboard-links">
            <Link to="/personel"><Icon name="people"/>Data Personel<Icon name="chevron"/></Link>
            {data.accounts && <Link to="/pengguna"><Icon name="shield"/>{data.accounts.label}: {data.accounts.active} aktif / {data.accounts.total}<Icon name="chevron"/></Link>}
            <Link to="/referensi"><Icon name="briefcase"/>Referensi {operator ? '(baca saja)' : 'organisasi & kualifikasi'}<Icon name="chevron"/></Link>
            {system && <Link to="/sistem"><Icon name="server"/>Monitoring teknis<Icon name="chevron"/></Link>}
          </div>
        </div></section>
      </div>
      <section className="content-shell"><div className="content-core dashboard-panel"><div className="section-heading"><div><span className="eyebrow">Pembaruan profil</span><h2>Personel terakhir diperbarui</h2></div><Link className="text-action" to="/personel">Lihat daftar</Link></div>
        {data.recent_personnel.length === 0 ? <div className="empty-inline">Belum ada data personel dalam cakupan akses.</div> : <div className="dashboard-recent">{data.recent_personnel.map((person) => <Link key={person.id} to={`/personel/${person.id}`}><div><strong>{person.nama_lengkap}</strong><small>{person.unit} · {new Date(person.updated_at).toLocaleDateString('id-ID')}</small></div><span className={`status-pill ${person.status}`}>{person.status}</span><Icon name="chevron"/></Link>)}</div>}
      </div></section>
    </>}
  </div>
}
