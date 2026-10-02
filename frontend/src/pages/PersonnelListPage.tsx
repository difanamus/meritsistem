import { useEffect, useMemo, useState } from 'react'
import type { FormEvent } from 'react'
import { Link, useLocation, useSearchParams } from 'react-router-dom'
import { Icon } from '../components/Icon'
import { useAuth } from '../auth/useAuth'
import { ApiError, apiRequest, peekApiCache, toQueryString } from '../lib/api'
import type { PaginationMeta, Personnel, ReferenceOptions } from '../types'

interface PersonnelResponse {
  data: Personnel[]
  meta: PaginationMeta
}

export function PersonnelListPage() {
  const { user } = useAuth()
  const location = useLocation()
  const [searchParams, setSearchParams] = useSearchParams()
  const [references, setReferences] = useState<Pick<ReferenceOptions, 'bidang_fungsi'> | null>(() => peekApiCache<{ data: Pick<ReferenceOptions, 'bidang_fungsi'> }>('/reference-options?only=bidang_fungsi')?.data ?? null)
  const [error, setError] = useState('')
  const [searchDraft, setSearchDraft] = useState(searchParams.get('search') ?? '')
  const [regionDraft, setRegionDraft] = useState(searchParams.get('operasi_wilayah') ?? '')
  const [revision, setRevision] = useState(0)
  const [restoring, setRestoring] = useState<number | null>(null)
  const [restoreConfirm, setRestoreConfirm] = useState<number | null>(null)
  const [message, setMessage] = useState('')
  const archived = searchParams.get('arsip') === '1'

  const query = useMemo(() => ({
    arsip: searchParams.get('arsip') || undefined,
    search: searchParams.get('search') || undefined,
    bidang_fungsi_id: searchParams.get('bidang_fungsi_id') || undefined,
    operasi_wilayah: searchParams.get('operasi_wilayah') || undefined,
    operasi_tingkat: searchParams.get('operasi_tingkat') || undefined,
    operasi_bidang_fungsi_id: searchParams.get('operasi_bidang_fungsi_id') || undefined,
    operasi_verifikasi: searchParams.get('operasi_verifikasi') || undefined,
    min_jumlah_operasi: searchParams.get('min_jumlah_operasi') || undefined,
    min_durasi_operasi_hari: searchParams.get('min_durasi_operasi_hari') || undefined,
    status: searchParams.get('status') || undefined,
    sort: searchParams.get('sort') || 'nama',
    direction: searchParams.get('direction') || 'asc',
    page: searchParams.get('page') || '1',
    per_page: 10,
  }), [searchParams])
  const queryKey = toQueryString(query)
  const cachedPage = peekApiCache<PersonnelResponse>(`/personel${queryKey}`)
  const [result, setResult] = useState<{ key: string; response: PersonnelResponse } | null>(() => cachedPage ? { key: queryKey, response: cachedPage } : null)
  const [loadedQuery, setLoadedQuery] = useState<string | null>(() => cachedPage ? queryKey : null)
  const visible = result?.key === queryKey ? result.response : cachedPage
  const personnel = visible?.data ?? []
  const meta = visible?.meta ?? null
  const loading = loadedQuery !== queryKey && !visible

  useEffect(() => {
    document.title = 'Data Personel · Merit SDM POLRI'
    apiRequest<{ data: Pick<ReferenceOptions, 'bidang_fungsi'> }>('/reference-options?only=bidang_fungsi')
      .then((response) => setReferences(response.data))
      .catch(() => setReferences(null))
  }, [])

  useEffect(() => {
    let active = true
    apiRequest<PersonnelResponse>(`/personel${queryKey}`)
      .then((response) => {
        if (!active) return
        setError('')
        setResult({ key: queryKey, response })
      })
      .catch((exception) => {
        if (active) { if (exception instanceof ApiError && [401, 403].includes(exception.status)) setResult(null); setError(exception instanceof ApiError ? exception.message : 'Data tidak dapat dimuat.') }
      })
      .finally(() => {
        if (active) setLoadedQuery(queryKey)
      })
    return () => { active = false }
  }, [queryKey, revision])

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

  const years = (days?: number) => days ? (days / 365.25).toFixed(1) : '0.0'

  return (
    <div className="page-wrap">
      {location.state?.message && <div className="alert success" role="status">{location.state.message}</div>}
      {message && <div className="alert success" role="status">{message}</div>}
      <header className="page-header reveal">
        <div><span className="eyebrow">Basis data merit</span><h1>Data Personel</h1><p>Telusuri identitas, kualifikasi, dan rekam jabatan dalam satu pandangan.</p></div>
        {user?.permissions?.create_personnel && <Link className="primary-cta compact" to="/personel/tambah"><span>Tambah personel</span><span className="cta-icon">+</span></Link>}
      </header>
      {user?.role !== 'operator' && <div className="archive-tabs"><button className={!archived ? 'active' : ''} onClick={() => setFilter('arsip', '')}>Data personel</button><button className={archived ? 'active' : ''} onClick={() => setFilter('arsip', '1')}>Arsip personel</button></div>}
      {archived && <p className="dashboard-note">Arsip bukan status dinas. Pemulihan mempertahankan riwayat asli; akun terkait harus diaktifkan kembali secara terpisah.</p>}

      <section className="metric-row reveal delay-one" aria-label="Ringkasan data">
        <div className="metric-shell"><div className="metric-core"><span>Hasil ditemukan</span><strong>{meta?.total ?? '—'}</strong><small>sesuai cakupan akses</small></div></div>
        <div className="metric-shell accent"><div className="metric-core"><span>Halaman aktif</span><strong>{meta?.current_page ?? '—'}<i>/{meta?.last_page ?? '—'}</i></strong><small>{meta?.per_page ?? 10} data per halaman</small></div></div>
        <div className="metric-shell"><div className="metric-core"><span>Mode penilaian</span><strong className="text-metric">Faktual</strong><small>tanpa skor otomatis</small></div></div>
      </section>

      <section className="content-shell reveal delay-two">
        <div className="content-core">
          <div className="filter-bar">
            <form className="search-box" onSubmit={submitSearch}>
              <Icon name="search" size={17}/>
              <input value={searchDraft} onChange={(event) => setSearchDraft(event.target.value)} placeholder="Cari nama atau NRP/NIP…" />
              <button type="submit">Cari</button>
            </form>
            <select value={searchParams.get('bidang_fungsi_id') ?? ''} onChange={(event) => setFilter('bidang_fungsi_id', event.target.value)} aria-label="Filter bidang fungsi">
              <option value="">Semua fungsi</option>
              {references?.bidang_fungsi.map((item) => <option key={item.id} value={item.id}>{item.nama}</option>)}
            </select>
            <select value={searchParams.get('status') ?? ''} onChange={(event) => setFilter('status', event.target.value)} aria-label="Filter status">
              <option value="">Semua status</option><option value="aktif">Aktif</option><option value="pensiun">Pensiun</option><option value="nonaktif">Nonaktif</option>
            </select>
            <select value={searchParams.get('sort') ?? 'nama'} onChange={(event) => setFilter('sort', event.target.value)} aria-label="Urutkan data">
              <option value="nama">Nama</option><option value="pangkat">Pangkat</option><option value="jumlah_kualifikasi">Kualifikasi terbanyak</option><option value="durasi_pengalaman">Pengalaman terlama</option><option value="kualifikasi_terbaru">Kualifikasi terbaru</option><option value="jumlah_operasi">Jumlah operasi</option><option value="durasi_operasi">Durasi operasi</option><option value="terbaru">Baru ditambahkan</option>
            </select>
          </div>

          <div className="filter-bar merit-filter-bar" aria-label="Filter penugasan operasi">
            <form className="search-box" onSubmit={(event) => { event.preventDefault(); setFilter('operasi_wilayah', regionDraft.trim()) }}><input value={regionDraft} onChange={(event) => setRegionDraft(event.target.value)} placeholder="Wilayah operasi, mis. Papua…" aria-label="Wilayah operasi" /><button type="submit">Terapkan</button></form>
            <select value={searchParams.get('operasi_tingkat') ?? ''} onChange={(event) => setFilter('operasi_tingkat', event.target.value)} aria-label="Tingkat operasi"><option value="">Semua tingkat operasi</option><option value="satker">Satker</option><option value="kabupaten_kota">Kabupaten/Kota</option><option value="provinsi">Provinsi</option><option value="nasional">Nasional</option><option value="internasional">Internasional</option></select>
            <select value={searchParams.get('operasi_bidang_fungsi_id') ?? ''} onChange={(event) => setFilter('operasi_bidang_fungsi_id', event.target.value)} aria-label="Fungsi operasi"><option value="">Semua fungsi operasi</option>{references?.bidang_fungsi.map((item) => <option key={item.id} value={item.id}>{item.nama}</option>)}</select>
            <select value={searchParams.get('operasi_verifikasi') ?? ''} onChange={(event) => setFilter('operasi_verifikasi', event.target.value)} aria-label="Verifikasi operasi"><option value="">Semua status verifikasi</option><option value="terverifikasi">Terverifikasi</option><option value="belum_diverifikasi">Belum diverifikasi</option></select>
            <select value={searchParams.get('min_jumlah_operasi') ?? ''} onChange={(event) => setFilter('min_jumlah_operasi', event.target.value)} aria-label="Minimal jumlah operasi"><option value="">Semua jumlah operasi</option><option value="1">Minimal 1 operasi</option><option value="2">Minimal 2 operasi</option><option value="3">Minimal 3 operasi</option></select>
            <select value={searchParams.get('min_durasi_operasi_hari') ?? ''} onChange={(event) => setFilter('min_durasi_operasi_hari', event.target.value)} aria-label="Minimal durasi operasi"><option value="">Semua durasi operasi</option><option value="30">Minimal 30 hari</option><option value="90">Minimal 90 hari</option><option value="365">Minimal 365 hari</option></select>
            <select value={searchParams.get('direction') ?? 'asc'} onChange={(event) => setFilter('direction', event.target.value)} aria-label="Arah urutan"><option value="asc">Terkecil → terbesar</option><option value="desc">Terbesar → terkecil</option></select>
          </div>

          {error && <div className="alert error">{error}</div>}
          {searchParams.get('sort') === 'pangkat' && <p className="dashboard-note">Dikelompokkan menurut jenis personel, lalu urutan pangkat. Urutan POLRI dan PNS tidak dibandingkan sebagai nilai merit.</p>}
          <div className={`data-table-wrap ${loading ? 'is-loading' : ''}`}>
            <table className="data-table">
              <thead><tr><th>Personel</th><th>Jabatan saat ini</th><th>Satker</th><th>Kualifikasi relevan</th><th>Pengalaman</th><th>Operasi</th><th aria-label="Aksi"/></tr></thead>
              <tbody>
                {!loading && personnel.map((person) => (
                  <tr key={person.id}>
                    <td><div className="person-cell"><span className="person-monogram">{person.nama_lengkap.split(' ').map((part) => part[0]).slice(0, 2).join('')}</span><div><strong>{person.nama_lengkap}</strong><small>{person.pangkat?.nama} · {person.nomor_identitas}</small></div></div></td>
                    <td><strong className="cell-primary">{person.jabatan_utama_aktif?.nama_jabatan ?? '—'}</strong><span className={`status-pill ${person.status}`}>{person.status_label}</span></td>
                    <td><span className="muted-cell">{person.unit_organisasi?.nama}</span></td>
                    <td><strong className="numeric-cell">{person.ringkasan_relevan?.jumlah_kualifikasi ?? person.jumlah_kualifikasi ?? 0}</strong><small>kegiatan</small></td>
                    <td><strong className="numeric-cell">{years(person.ringkasan_relevan?.durasi_pengalaman_hari)}</strong><small>tahun</small></td>
                    <td><strong className="numeric-cell">{person.ringkasan_operasi?.jumlah ?? 0}</strong><small>{person.ringkasan_operasi?.total_durasi_hari ?? 0} hari kumulatif</small></td>
                    <td>{!archived ? <Link className="row-action" to={`/personel/${person.id}`} aria-label={`Lihat ${person.nama_lengkap}`}><Icon name="chevron" size={17}/></Link> : <div><small>Alasan: {person.alasan_arsip ?? 'Arsip lama'}</small>{restoreConfirm !== person.id ? <button className="text-action" onClick={() => setRestoreConfirm(person.id)}>Pulihkan</button> : <div className="table-actions"><button className="text-action" disabled={restoring !== null} onClick={async () => {
                      setRestoring(person.id); setError(''); setMessage('')
                      try { await apiRequest(`/personel/${person.id}/restore`, { method: 'POST' }); setRestoreConfirm(null); setResult(null); setMessage('Personel dipulihkan. Akun terkait tidak otomatis aktif.'); setRevision((value) => value + 1) }
                      catch (exception) { setError(exception instanceof ApiError ? exception.message : 'Pemulihan gagal.') }
                      finally { setRestoring(null) }
                    }}>{restoring === person.id ? 'Memulihkan…' : 'Ya, pulihkan'}</button><button className="muted-action" disabled={restoring !== null} onClick={() => setRestoreConfirm(null)}>Batal</button></div>}</div>}</td>
                  </tr>
                ))}
                {loading && Array.from({ length: 5 }).map((_, index) => <tr className="skeleton-row" key={index}><td colSpan={7}><span/></td></tr>)}
              </tbody>
            </table>
            {!loading && personnel.length === 0 && <div className="empty-state"><Icon name="search" size={28}/><strong>Belum ada hasil</strong><p>Ubah kata pencarian atau filter yang digunakan.</p></div>}
          </div>

          {meta && meta.last_page > 1 && <div className="pagination"><button disabled={meta.current_page === 1} onClick={() => setFilter('page', String(meta.current_page - 1))}>Sebelumnya</button><span>Halaman {meta.current_page} dari {meta.last_page}</span><button disabled={meta.current_page === meta.last_page} onClick={() => setFilter('page', String(meta.current_page + 1))}>Berikutnya</button></div>}
        </div>
      </section>
    </div>
  )
}
