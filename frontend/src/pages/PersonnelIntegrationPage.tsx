import { useEffect, useState } from 'react'
import { ApiError, apiRequest, peekApiCache } from '../lib/api'

interface Run { id: number; mode: string; version: number; status: string; created_at: string }
interface Integration { prototype: boolean; connected: boolean; enabled: boolean; checkpoint: number; runs: Run[] }
interface Report { run: Run; counts: Record<string, number>; checkpoint: number; items: { id: number; source_id: string; nama_lengkap: string; nomor_identitas: string; action: string; status: string; reason: string }[]; meta: { current_page: number; last_page: number; total: number } }
const labels: Record<string, string> = { preview: 'Pratinjau', running: 'Sedang diproses', completed: 'Selesai', completed_with_errors: 'Selesai dengan catatan', pending: 'Menunggu', succeeded: 'Berhasil', skipped: 'Dilewati', conflict: 'Konflik', failed: 'Tidak valid/gagal', create: 'Tambah', update: 'Perbarui identitas', unchanged: 'Tidak berubah', ignored: 'Abaikan tombstone', invalid: 'Tidak valid', initial: 'Impor awal', delta: 'Delta' }

export function PersonnelIntegrationPage() {
  const [info, setInfo] = useState<Integration | null>(() => peekApiCache<{ data: Integration }>('/personnel-integration')?.data ?? null)
  const [report, setReport] = useState<Report | null>(null)
  const [mode, setMode] = useState('initial')
  const [version, setVersion] = useState(() => Math.max(1, peekApiCache<{ data: Integration }>('/personnel-integration')?.data.checkpoint ?? 0))
  const [confirmed, setConfirmed] = useState(false)
  const [busy, setBusy] = useState(false)
  const [error, setError] = useState('')
  useEffect(() => {
    let active = true
    document.title = 'Integrasi Personel · Merit SDM POLRI'
    apiRequest<{ data: Integration }>('/personnel-integration').then(response => { if (active) { setInfo(response.data); setVersion(Math.max(1, response.data.checkpoint)) } })
      .catch(() => { if (active) { setInfo(null); setError('Informasi integrasi belum dapat dimuat. Periksa layanan backend.') } })
    return () => { active = false }
  }, [])

  async function perform(path: string, body?: object) {
    if (busy) return
    setBusy(true); setError(''); setConfirmed(false)
    try {
      const result = await apiRequest<{ data: Report }>(path, body ? { method: 'POST', body: JSON.stringify(body) } : {})
      setReport(result.data)
      const metadata = await apiRequest<{ data: Integration }>('/personnel-integration')
      setInfo(metadata.data)
    } catch (cause) {
      if (cause instanceof ApiError && [401, 403].includes(cause.status)) { setReport(null); setInfo(null) }
      setError(cause instanceof Error ? cause.message : 'Permintaan gagal.')
    }
    finally { setBusy(false) }
  }
  const pending = Number(report?.counts.pending ?? 0)
  const processing = report && ['preview', 'running'].includes(report.run.status)

  return <div className="page-wrap">
    <header className="page-header"><div><span className="eyebrow">Admin · Prototype integrasi</span><h1>Integrasi Personel</h1><p>Pratinjau, konfirmasi, proses per batch, dan laporan sinkronisasi.</p></div></header>
    <div className="alert" role="note">Sumber simulasi, bukan koneksi SIPP atau layanan Polri. Versi 1 menyediakan tiga personel fiktif; versi 2 memperbarui satu identitas, menambah satu personel, dan mengirim satu tombstone. Tidak menggunakan data atau kredensial eksternal.</div>
    {error && <div className="alert error" role="alert">{error}</div>}
    <section className="content-shell"><div className="content-core dashboard-panel">
      <h2>Pratinjau sumber</h2><p>Checkpoint terakhir: <strong>{info?.checkpoint ?? '—'}</strong>. Delta hanya membaca perubahan setelah checkpoint. Riwayat lokal dan arsip tidak ditimpa; perubahan jabatan/penempatan memerlukan proses mutasi.</p>
      {info && !info.enabled && <p role="alert">Simulasi dinonaktifkan di lingkungan ini; hanya tersedia pada local/testing.</p>}
      <div className="form-grid">
        <label>Mode <select aria-label="Mode integrasi" value={mode} disabled={busy} onChange={event => setMode(event.target.value)}><option value="initial">Impor awal / ulang penuh</option><option value="delta">Delta sync</option></select></label>
        <label>Versi simulasi <select aria-label="Versi simulasi" value={version} disabled={busy} onChange={event => setVersion(Number(event.target.value))}><option value={1}>Versi 1</option><option value={2}>Versi 2</option></select></label>
      </div><div className="form-actions"><button className="primary-cta compact" disabled={busy || !info?.enabled} onClick={() => void perform('/personnel-integration/preview', { mode, version })}>Buat pratinjau</button></div><p className="dashboard-note">Pratinjau tidak mengubah personel. NRP/NIP yang sudah ada tanpa pemetaan sumber ditandai konflik, bukan otomatis ditimpa.</p>
    </div></section>
    {report && <section className="content-shell"><div className="content-core dashboard-panel">
      <h2>Laporan #{report.run.id} · {labels[report.run.status] ?? report.run.status}</h2>
      <p>{labels[report.run.mode]} · versi {report.run.version} · {report.meta.total} item · checkpoint {report.checkpoint}</p>
      <p>{Object.entries(report.counts).map(([status, count]) => `${labels[status] ?? status}: ${count}`).join(' · ') || 'Tidak ada perubahan sumber.'}</p>
      {processing && <><label className="inline-checkbox"><input type="checkbox" checked={confirmed} disabled={busy} onChange={event => setConfirmed(event.target.checked)} /> Saya sudah memeriksa pratinjau dan menyetujui pemrosesan item valid.</label>
        <p><button className="primary-cta compact" disabled={busy || !confirmed || !info?.enabled} onClick={() => void perform(`/personnel-integration/${report.run.id}/apply`, { confirmed: true })}>{busy ? 'Memproses…' : pending ? 'Proses batch berikutnya (maks. 25)' : 'Finalisasi laporan'}</button></p></>}
      <div className="data-table-wrap"><table className="data-table reference-table"><thead><tr><th>Personel / NRP</th><th>Aksi</th><th>Status</th><th>Keterangan</th></tr></thead><tbody>{report.items.map(item => <tr key={item.id}><td>{item.nama_lengkap}<br/><small>{item.nomor_identitas} · {item.source_id}</small></td><td>{labels[item.action]}</td><td>{labels[item.status]}</td><td>{item.reason}</td></tr>)}</tbody></table></div>
      <div className="pagination"><button disabled={busy || report.meta.current_page <= 1} onClick={() => void perform(`/personnel-integration/${report.run.id}?page=${report.meta.current_page - 1}`)}>Sebelumnya</button><span>Halaman {report.meta.current_page} dari {report.meta.last_page}</span><button disabled={busy || report.meta.current_page >= report.meta.last_page} onClick={() => void perform(`/personnel-integration/${report.run.id}?page=${report.meta.current_page + 1}`)}>Berikutnya</button></div>
      <p className="dashboard-note">Checkpoint maju setelah seluruh item selesai tanpa konflik/gagal. Item berhasil tidak diulang; perbaiki konflik lalu buat pratinjau baru. Tombstone tidak menghapus personel lokal.</p>
    </div></section>}
    <section className="content-shell"><div className="content-core dashboard-panel"><h2>Sepuluh proses terakhir</h2><p>Laporan tersimpan. Pilih proses untuk membaca hasil atau melanjutkan batch yang belum selesai.</p>
      {info?.runs.map(run => <p key={run.id}><button className="text-action" disabled={busy} onClick={() => void perform(`/personnel-integration/${run.id}`)}>Buka laporan #{run.id}</button> · {labels[run.mode]} · versi {run.version} · {labels[run.status]}</p>)}
      {info?.runs.length === 0 && <p>Belum ada proses integrasi.</p>}
    </div></section>
  </div>
}
