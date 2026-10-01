import { useEffect, useMemo, useState } from 'react'
import type { FormEvent } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { Icon } from '../components/Icon'
import { ApiError, apiRequest, toQueryString } from '../lib/api'
import type { PaginationMeta, Personnel, ReferenceOptions } from '../types'

interface PersonnelResponse {
  data: Personnel[]
  meta: PaginationMeta
}

export function PersonnelListPage() {
  const [searchParams, setSearchParams] = useSearchParams()
  const [personnel, setPersonnel] = useState<Personnel[]>([])
  const [meta, setMeta] = useState<PaginationMeta | null>(null)
  const [references, setReferences] = useState<ReferenceOptions | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [searchDraft, setSearchDraft] = useState(searchParams.get('search') ?? '')

  const query = useMemo(() => ({
    search: searchParams.get('search') || undefined,
    bidang_fungsi_id: searchParams.get('bidang_fungsi_id') || undefined,
    status: searchParams.get('status') || undefined,
    sort: searchParams.get('sort') || 'nama',
    direction: searchParams.get('direction') || 'asc',
    page: searchParams.get('page') || '1',
    per_page: 10,
  }), [searchParams])

  useEffect(() => {
    document.title = 'Data Personel · Merit SDM POLRI'
    apiRequest<{ data: ReferenceOptions }>('/reference-options')
      .then((response) => setReferences(response.data))
      .catch(() => setReferences(null))
  }, [])

  useEffect(() => {
    let active = true
    apiRequest<PersonnelResponse>(`/personel${toQueryString(query)}`)
      .then((response) => {
        if (!active) return
        setError('')
        setPersonnel(response.data)
        setMeta(response.meta)
      })
      .catch((exception) => {
        if (active) setError(exception instanceof ApiError ? exception.message : 'Data tidak dapat dimuat.')
      })
      .finally(() => {
        if (active) setLoading(false)
      })
    return () => { active = false }
  }, [query])

  const setFilter = (key: string, value: string) => {
    const next = new URLSearchParams(searchParams)
    if (value) next.set(key, value)
    else next.delete(key)
    next.delete('page')
    setSearchParams(next)
  }

  const submitSearch = (event: FormEvent) => {
    event.preventDefault()
    setFilter('search', searchDraft.trim())
  }

  const years = (days?: number) => days ? (days / 365.25).toFixed(1) : '0.0'

  return (
    <div className="page-wrap">
      <header className="page-header reveal">
        <div><span className="eyebrow">Basis data merit</span><h1>Data Personel</h1><p>Telusuri identitas, kualifikasi, dan rekam jabatan dalam satu pandangan.</p></div>
        <Link className="primary-cta compact" to="/personel/tambah"><span>Tambah personel</span><span className="cta-icon">+</span></Link>
      </header>

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
              <option value="nama">Nama</option><option value="pangkat">Pangkat</option><option value="jumlah_kualifikasi">Kualifikasi terbanyak</option><option value="durasi_pengalaman">Pengalaman terlama</option><option value="kualifikasi_terbaru">Kualifikasi terbaru</option><option value="terbaru">Baru ditambahkan</option>
            </select>
          </div>

          {error && <div className="alert error">{error}</div>}
          <div className={`data-table-wrap ${loading ? 'is-loading' : ''}`}>
            <table className="data-table">
              <thead><tr><th>Personel</th><th>Jabatan saat ini</th><th>Satker</th><th>Kualifikasi relevan</th><th>Pengalaman</th><th aria-label="Aksi"/></tr></thead>
              <tbody>
                {!loading && personnel.map((person) => (
                  <tr key={person.id}>
                    <td><div className="person-cell"><span className="person-monogram">{person.nama_lengkap.split(' ').map((part) => part[0]).slice(0, 2).join('')}</span><div><strong>{person.nama_lengkap}</strong><small>{person.pangkat?.nama} · {person.nomor_identitas}</small></div></div></td>
                    <td><strong className="cell-primary">{person.jabatan_utama_aktif?.nama_jabatan ?? '—'}</strong><span className={`status-pill ${person.status}`}>{person.status_label}</span></td>
                    <td><span className="muted-cell">{person.unit_organisasi?.nama}</span></td>
                    <td><strong className="numeric-cell">{person.ringkasan_relevan?.jumlah_kualifikasi ?? person.jumlah_kualifikasi ?? 0}</strong><small>kegiatan</small></td>
                    <td><strong className="numeric-cell">{years(person.ringkasan_relevan?.durasi_pengalaman_hari)}</strong><small>tahun</small></td>
                    <td><Link className="row-action" to={`/personel/${person.id}`} aria-label={`Lihat ${person.nama_lengkap}`}><Icon name="chevron" size={17}/></Link></td>
                  </tr>
                ))}
                {loading && Array.from({ length: 5 }).map((_, index) => <tr className="skeleton-row" key={index}><td colSpan={6}><span/></td></tr>)}
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
