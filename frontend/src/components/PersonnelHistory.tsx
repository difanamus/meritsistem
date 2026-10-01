import { useState } from 'react'
import { Link } from 'react-router-dom'
import { ApiError, apiRequest, downloadDocument } from '../lib/api'
import { isActivePrimary } from '../lib/historyForm'
import { useHistory } from '../lib/useHistory'
import type { PaginationMeta, PositionRecord, PrivateDocument, QualificationRecord } from '../types'

export function DocumentDownload({ document, path }: { document: PrivateDocument | null; path: string }) {
  const [busy, setBusy] = useState(false)
  const [error, setError] = useState('')
  if (!document) return <span className="history-no-document">Tanpa PDF</span>
  const download = async () => {
    setBusy(true); setError('')
    try { await downloadDocument(path, document.nama_asli) }
    catch (exception) { setError(exception instanceof ApiError ? exception.message : 'Unduhan gagal. Periksa koneksi Anda.') }
    finally { setBusy(false) }
  }
  return <div className="history-download"><button className="text-action" type="button" disabled={busy} onClick={download} aria-label={`Unduh ${document.nama_asli}`}>{busy ? 'Mengunduh…' : 'Unduh PDF'}</button><small>{document.nama_asli}</small>{error && <small className="history-error" role="alert">{error}</small>}</div>
}

export function HistoryDelete({ name, path, onDeleted, locked = false, kind = 'riwayat' }: { name: string; path: string; onDeleted: () => void; locked?: boolean; kind?: 'personel' | 'riwayat' }) {
  const [confirm, setConfirm] = useState(false)
  const [busy, setBusy] = useState(false)
  const [error, setError] = useState('')
  const remove = async () => {
    setBusy(true); setError('')
    try { await apiRequest(path, { method: 'DELETE' }); setConfirm(false); onDeleted() }
    catch (exception) { setError(exception instanceof ApiError ? exception.message : 'Penghapusan gagal. Periksa koneksi Anda.') }
    finally { setBusy(false) }
  }
  if (locked) return <span className="history-lock">Utama aktif · gunakan ganti jabatan/mutasi</span>
  return <div className="history-delete">
    {!confirm ? <button type="button" className="danger-action" onClick={() => setConfirm(true)} aria-label={`Hapus ${name}`}>Hapus</button> : <div className="history-confirm" role="group" aria-label={`Konfirmasi hapus ${name}`}>
      <p>Hapus “{name}” dari daftar? Data diarsipkan (soft delete); dokumen tidak dihapus permanen.</p>
      <button type="button" className="danger-action" disabled={busy} onClick={remove}>{busy ? 'Menghapus…' : `Ya, hapus ${kind}`}</button>
      <button type="button" className="text-action" disabled={busy} onClick={() => { setConfirm(false); setError('') }}>Batal hapus</button>
      {error && <p className="history-error" role="alert">{error}</p>}
    </div>}
  </div>
}

export function HistoryPagination({ meta, loading, onPage }: { meta: PaginationMeta | null; loading: boolean; onPage: (page: number) => void }) {
  if (!meta || meta.last_page <= 1) return null
  return <div className="pagination"><span>{meta.total} riwayat · halaman {meta.current_page}/{meta.last_page}</span><button type="button" disabled={loading || meta.current_page <= 1} onClick={() => onPage(meta.current_page - 1)}>Sebelumnya</button><button type="button" disabled={loading || meta.current_page >= meta.last_page} onClick={() => onPage(meta.current_page + 1)}>Berikutnya</button></div>
}

export function QualificationSection({ personnelId, onChange }: { personnelId: number; onChange: () => void }) {
  const history = useHistory<QualificationRecord>(personnelId, 'kualifikasi')
  return <section className="profile-section reveal" aria-label="Kualifikasi personel">
    <div className="section-heading"><div><span className="eyebrow">Kompetensi</span><h2>Kualifikasi personel</h2></div><Link className="secondary-cta" to={`/personel/${personnelId}/kualifikasi/tambah`}>+ Tambah kualifikasi</Link></div>
    {history.error && <div className="alert error" role="alert">{history.error} <button className="text-action" onClick={history.reload}>Coba lagi</button></div>}
    {history.loading ? <div className="empty-inline" role="status">Memuat kualifikasi…</div> : !history.error && <div className="qualification-grid">
      {history.items.map((item) => <article className="qualification-card history-card" key={item.id}><span>{item.jenis_kualifikasi.nama}</span><h3>{item.nama_kualifikasi}</h3><p>{item.bidang_fungsi?.nama ?? 'Kualifikasi umum'} · {item.jenjang ?? 'Jenjang tidak dicatat'}</p><strong>{item.tahun ?? '—'}</strong>
        <dl className="history-facts"><div><dt>Penyelenggara</dt><dd>{item.institusi_penyelenggara ?? '—'}</dd></div><div><dt>Periode</dt><dd>{item.tanggal_mulai ?? '—'} s.d. {item.tanggal_selesai ?? '—'}</dd></div><div><dt>Bidang studi</dt><dd>{item.bidang_studi ?? '—'}</dd></div><div><dt>Nomor dokumen</dt><dd>{item.nomor_dokumen ?? '—'}</dd></div></dl>
        {item.keterangan && <p className="history-note">{item.keterangan}</p>}
        <DocumentDownload document={item.dokumen} path={`/kualifikasi/${item.id}/dokumen`} />
        <div className="history-actions"><Link className="text-action" to={`/personel/${personnelId}/kualifikasi/${item.id}/edit`} aria-label={`Edit ${item.nama_kualifikasi}`}>Edit</Link><HistoryDelete name={item.nama_kualifikasi} path={`/kualifikasi/${item.id}`} onDeleted={() => { history.reload(); onChange() }} /></div>
      </article>)}
      {history.items.length === 0 && <div className="empty-inline">Belum ada data kualifikasi.</div>}
    </div>}
    <HistoryPagination meta={history.meta} loading={history.loading} onPage={history.changePage}/>
  </section>
}

export function PositionSection({ personnelId, onChange }: { personnelId: number; onChange: () => void }) {
  const history = useHistory<PositionRecord>(personnelId, 'riwayat-jabatan')
  return <section className="profile-section reveal" aria-label="Riwayat jabatan">
    <div className="section-heading"><div><span className="eyebrow">Perjalanan karier</span><h2>Riwayat jabatan</h2></div><div className="history-actions"><Link className="secondary-cta" to={`/personel/${personnelId}/riwayat-jabatan/tambah`}>+ Tambah riwayat</Link><Link className="secondary-cta" to={`/personel/${personnelId}/mutasi`}>Ganti jabatan / mutasi</Link></div></div>
    {history.error && <div className="alert error" role="alert">{history.error} <button className="text-action" onClick={history.reload}>Coba lagi</button></div>}
    {history.loading ? <div className="empty-inline" role="status">Memuat riwayat jabatan…</div> : !history.error && <div className="timeline">
      {history.items.map((item, index) => <article className="timeline-item" key={item.id}><div className="timeline-marker"><span>{(history.page - 1) * 15 + index + 1}</span></div><div className="timeline-content history-timeline-content">
        <div><span className="timeline-date">{item.tanggal_mulai} — {item.tanggal_selesai ?? 'Masih berjalan'}</span><h3>{item.nama_jabatan}</h3><p>{item.unit_organisasi.nama} · {item.bidang_fungsi.nama}</p><p>{item.jenis_penugasan.nama}{item.nivelering ? ` · Nivelering ${item.nivelering}` : ''}</p>{item.keterangan && <p className="history-note">{item.keterangan}</p>}
          <DocumentDownload document={item.dokumen} path={`/riwayat-jabatan/${item.id}/dokumen`} />
          <div className="history-actions"><Link className="text-action" to={`/personel/${personnelId}/riwayat-jabatan/${item.id}/edit`} aria-label={`Edit ${item.nama_jabatan}`}>Edit</Link><HistoryDelete name={item.nama_jabatan} locked={isActivePrimary(item)} path={`/riwayat-jabatan/${item.id}`} onDeleted={() => { history.reload(); onChange() }} /></div>
        </div><span className={item.is_jabatan_utama ? 'position-kind main' : 'position-kind'}>{item.is_jabatan_utama ? 'Utama' : 'Tambahan'}</span>
      </div></article>)}
      {history.items.length === 0 && <div className="empty-inline">Belum ada riwayat jabatan.</div>}
    </div>}
    <HistoryPagination meta={history.meta} loading={history.loading} onPage={history.changePage}/>
  </section>
}
