import { useEffect, useState } from 'react'
import { apiRequest } from '../lib/api'

interface SystemStatus { database: string; database_driver: string; php_version: string; laravel_version: string; checked_at: string }

export function SystemStatusPage() {
  const [status, setStatus] = useState<SystemStatus | null>(null)
  const [error, setError] = useState('')
  const [attempt, setAttempt] = useState(0)
  const [loading, setLoading] = useState(true)
  useEffect(() => {
    let active = true
    document.title = 'Monitoring Sistem · Merit SDM POLRI'
    apiRequest<{ data: SystemStatus }>('/system-status').then((response) => {
      if (active) { setStatus(response.data); setError('') }
    }).catch(() => { if (active) { setStatus(null); setError('Pemeriksaan layanan gagal. Periksa backend dan database lokal.') } })
      .finally(() => { if (active) setLoading(false) })
    return () => { active = false }
  }, [attempt])
  return <div className="page-wrap"><header className="page-header"><div><span className="eyebrow">System Admin · Teknis</span><h1>Monitoring Sistem</h1><p>Pemeriksaan koneksi saat diminta, bukan pemantauan historis atau uptime.</p></div>
    <button className="primary-cta compact" disabled={loading} onClick={() => { setLoading(true); setAttempt(attempt + 1) }}>{loading ? 'Memeriksa…' : 'Periksa ulang'}</button></header>
    {error && <div className="alert error" role="alert">{error}</div>}
    {status && <section className="content-shell"><div className="content-core dashboard-panel"><span className="eyebrow">Pemeriksaan terakhir</span><h2>Backend terhubung</h2><dl className="dashboard-facts">
      <div><dt>Koneksi database</dt><dd>Terhubung</dd></div><div><dt>Driver database</dt><dd>{status.database_driver}</dd></div>
      <div><dt>PHP</dt><dd>{status.php_version}</dd></div><div><dt>Laravel</dt><dd>{status.laravel_version}</dd></div><div><dt>Waktu pemeriksaan</dt><dd>{new Date(status.checked_at).toLocaleString('id-ID')}</dd></div>
    </dl><p className="dashboard-note">Halaman read-only. Tidak menampilkan kredensial atau menjalankan perubahan konfigurasi, migration, maupun reset data.</p></div></section>}
  </div>
}
