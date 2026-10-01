import { Link } from 'react-router-dom'
import { useState } from 'react'
import { useAuth } from '../auth/useAuth'
import { ApiError, apiRequest } from '../lib/api'
import { meritConfig } from '../lib/meritProfile'
import { DocumentDownload, HistoryDelete, HistoryPagination } from './PersonnelHistory'
import { useHistory } from '../lib/useHistory'
import type { MeritKind, MeritRecord } from '../types'

function MeritVerification({ kind, item, onChanged }: { kind: MeritKind; item: MeritRecord; onChanged: () => void }) {
  const { user } = useAuth()
  const [busy, setBusy] = useState(false)
  const [error, setError] = useState('')
  if (user?.role === 'operator') return null
  const verified = item.status_verifikasi === 'terverifikasi'
  const change = async () => {
    setBusy(true); setError('')
    try {
      await apiRequest(`/merit/${kind}/${item.id}/verifikasi`, { method: 'POST', body: JSON.stringify({ status_verifikasi: verified ? 'belum_diverifikasi' : 'terverifikasi' }) })
      onChanged()
    } catch (exception) { setError(exception instanceof ApiError ? exception.message : 'Verifikasi gagal.') }
    finally { setBusy(false) }
  }
  return <div className="merit-verification"><button type="button" className="text-action" disabled={busy} onClick={change}>{busy ? 'Menyimpan…' : verified ? 'Batalkan verifikasi' : 'Verifikasi riwayat'}</button>{error && <small className="history-error" role="alert">{error}</small>}</div>
}

export function MeritSection({ personnelId, kind }: { personnelId: number; kind: MeritKind }) {
  const config = meritConfig[kind]
  const history = useHistory<MeritRecord>(personnelId, `merit/${kind}`)
  return <section className="profile-section reveal" aria-label={config.title}>
    <div className="section-heading"><div><span className="eyebrow">{config.eyebrow}</span><h2>{config.title}</h2></div><Link className="secondary-cta" to={`/personel/${personnelId}/merit/${kind}/tambah`}>+ Tambah {config.title.toLowerCase()}</Link></div>
    {history.error && <div className="alert error" role="alert">{history.error} <button className="text-action" onClick={history.reload}>Coba lagi</button></div>}
    {history.loading ? <div className="empty-inline" role="status">Memuat {config.title.toLowerCase()}…</div> : !history.error && <div className="qualification-grid">
      {history.items.map((item) => <article className="qualification-card history-card merit-card" key={item.id}>
        <span>{item.tingkat.replaceAll('_', ' ')}</span><h3>{item.nama}</h3>
        <p>{item.bidang_fungsi?.nama ?? 'Lintas fungsi'} · {item.status_verifikasi === 'terverifikasi' ? 'Terverifikasi' : 'Belum diverifikasi'}</p>
        <dl className="history-facts">{config.fields.filter((field) => field.key !== 'nama' && field.key !== 'tingkat' && field.key !== 'keterangan').map((field) => {
          const value = item[field.key as keyof MeritRecord]
          return <div key={field.key}><dt>{field.label}</dt><dd>{value == null || value === '' ? '—' : String(value)}</dd></div>
        })}</dl>
        {item.keterangan && <p className="history-note">{item.keterangan}</p>}
        {item.verified_by && <small>Diverifikasi oleh {item.verified_by.nama}</small>}
        <DocumentDownload document={item.dokumen} path={`/merit/${kind}/${item.id}/dokumen`} />
        <MeritVerification kind={kind} item={item} onChanged={history.reload} />
        <div className="history-actions"><Link className="text-action" to={`/personel/${personnelId}/merit/${kind}/${item.id}/edit`} aria-label={`Edit ${item.nama}`}>Edit</Link><HistoryDelete name={item.nama} path={`/merit/${kind}/${item.id}`} onDeleted={history.reload} /></div>
      </article>)}
      {history.items.length === 0 && <div className="empty-inline">Belum ada {config.title.toLowerCase()}.</div>}
    </div>}
    <HistoryPagination meta={history.meta} loading={history.loading} onPage={history.changePage}/>
  </section>
}
