import { useEffect, useRef, useState } from 'react'
import type { FormEvent } from 'react'
import { useSearchParams } from 'react-router-dom'
import { useAuth } from '../auth/useAuth'
import { Icon } from '../components/Icon'
import { ApiError, apiRequest, toQueryString } from '../lib/api'
import type { PaginationMeta } from '../types'

const categories = [
  { key: 'unit-organisasi', label: 'Unit organisasi' },
  { key: 'pangkat', label: 'Pangkat' },
  { key: 'bidang-fungsi', label: 'Bidang / fungsi' },
  { key: 'jenis-kualifikasi', label: 'Jenis kualifikasi' },
  { key: 'jenis-penugasan', label: 'Jenis penugasan' },
] as const

type Category = typeof categories[number]['key']
interface ReferenceRecord {
  id: number
  kode: string
  nama: string
  is_active: boolean
  parent_id?: number | null
  parent?: { id: number; nama: string } | null
  jenis_unit?: string
  jenis_unit_label?: string
  jenis_personel?: string
  urutan?: number
  deskripsi?: string | null
}
interface ReferencePageResponse {
  data: ReferenceRecord[]
  meta: PaginationMeta
}
interface Draft {
  kode: string
  nama: string
  is_active: boolean
  parent_id: string
  jenis_unit: string
  jenis_personel: string
  urutan: string
  deskripsi: string
}
const unitTypes = [
  ['root', 'POLRI'], ['mabes', 'Mabes Polri'], ['satker_mabes', 'Satker Mabes'],
  ['polda', 'Polda'], ['satker_polda', 'Satker Polda'], ['polres', 'Polres'],
  ['satker_polres', 'Satker Polres'], ['polsek', 'Polsek'],
]
const blankDraft = (): Draft => ({
  kode: '', nama: '', is_active: true, parent_id: '', jenis_unit: 'satker_polres',
  jenis_personel: 'polri', urutan: '0', deskripsi: '',
})

export function ReferencePage() {
  const { user } = useAuth()
  const [params, setParams] = useSearchParams()
  const type = (categories.find((item) => item.key === params.get('type'))?.key ?? 'unit-organisasi') as Category
  const canManage = user?.role === 'system_admin' || (type !== 'pangkat' && user?.role === 'admin_ssdm')
  const label = categories.find((item) => item.key === type)!.label
  const [records, setRecords] = useState<ReferenceRecord[]>([])
  const [meta, setMeta] = useState<PaginationMeta | null>(null)
  const [parents, setParents] = useState<ReferenceRecord[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [notice, setNotice] = useState('')
  const [search, setSearch] = useState(params.get('search') ?? '')
  const [refresh, setRefresh] = useState(0)
  const [editing, setEditing] = useState<number | 'new' | null>(null)
  const [draft, setDraft] = useState<Draft>(blankDraft)
  const [errors, setErrors] = useState<Record<string, string[]>>({})
  const [saving, setSaving] = useState(false)
  const [deleteTarget, setDeleteTarget] = useState<ReferenceRecord | null>(null)
  const formSection = useRef<HTMLElement>(null)

  useEffect(() => {
    if (editing !== null) {
      formSection.current?.scrollIntoView({ behavior: 'smooth', block: 'start' })
    }
  }, [editing])

  useEffect(() => {
    document.title = 'Data Referensi · Merit SDM POLRI'
    let active = true
    const fetchRecords = async () => {
      setLoading(true)
      try {
        const response = await apiRequest<ReferencePageResponse>(`/references/${type}${toQueryString({
          search: params.get('search') ?? '', status: params.get('status') ?? '',
          page: params.get('page') ?? '1', per_page: 10,
        })}`)
        if (active) {
          setRecords(response.data)
          setMeta(response.meta)
          setError('')
        }
      } catch (exception) {
        if (active) setError(exception instanceof ApiError ? exception.message : 'Referensi tidak dapat dimuat.')
      } finally {
        if (active) setLoading(false)
      }
    }
    void fetchRecords()
    return () => { active = false }
  }, [type, params, refresh])

  useEffect(() => {
    if (type !== 'unit-organisasi' || !canManage) return
    let active = true
    const fetchParents = async () => {
      try {
        const all: ReferenceRecord[] = []
        let page = 1
        let lastPage = 1
        do {
          const response = await apiRequest<ReferencePageResponse>(`/references/unit-organisasi?per_page=100&page=${page}`)
          all.push(...response.data)
          lastPage = response.meta.last_page
          page += 1
        } while (page <= lastPage)
        if (active) setParents(all)
      } catch (exception) {
        if (active) setError(exception instanceof ApiError ? exception.message : 'Pilihan induk unit tidak dapat dimuat.')
      }
    }
    void fetchParents()
    return () => { active = false }
  }, [type, canManage, refresh])

  const setFilter = (key: string, value: string) => {
    const next = new URLSearchParams(params)
    if (value) next.set(key, value)
    else next.delete(key)
    if (key !== 'page') next.delete('page')
    setParams(next)
  }

  const switchCategory = (category: Category) => {
    setEditing(null)
    setDeleteTarget(null)
    setNotice('')
    setError('')
    setSearch('')
    setParams({ type: category })
  }

  const openForm = (record?: ReferenceRecord) => {
    setErrors({})
    setError('')
    setDeleteTarget(null)
    setEditing(record?.id ?? 'new')
    setDraft(record ? {
      kode: record.kode, nama: record.nama, is_active: record.is_active,
      parent_id: record.parent_id ? String(record.parent_id) : '',
      jenis_unit: record.jenis_unit ?? 'satker_polres', jenis_personel: record.jenis_personel ?? 'polri',
      urutan: String(record.urutan ?? 0), deskripsi: record.deskripsi ?? '',
    } : blankDraft())
  }

  const field = <K extends keyof Draft>(key: K, value: Draft[K]) => setDraft((current) => ({ ...current, [key]: value }))
  const fieldError = (key: string) => errors[key]?.[0]

  const isDescendant = (candidate: ReferenceRecord): boolean => {
    let current: ReferenceRecord | undefined = candidate
    const visited = new Set<number>()
    while (current) {
      if (current.id === editing) return true
      if (visited.has(current.id)) return true
      visited.add(current.id)
      current = parents.find((unit) => unit.id === current?.parent_id)
    }
    return false
  }

  const submit = async (event: FormEvent) => {
    event.preventDefault()
    if (editing === null) return
    setSaving(true)
    setError('')
    setErrors({})
    const payload = {
      kode: draft.kode.trim(), nama: draft.nama.trim(), is_active: draft.is_active,
      ...(type === 'unit-organisasi' ? { jenis_unit: draft.jenis_unit, parent_id: draft.parent_id ? Number(draft.parent_id) : null } : {}),
      ...(type === 'pangkat' ? { jenis_personel: draft.jenis_personel, urutan: Number(draft.urutan) } : {}),
      ...(type === 'bidang-fungsi' ? { deskripsi: draft.deskripsi || null } : {}),
    }
    try {
      const response = await apiRequest<{ message: string }>(`/references/${type}${editing === 'new' ? '' : `/${editing}`}`, {
        method: editing === 'new' ? 'POST' : 'PUT', body: JSON.stringify(payload),
      })
      setNotice(response.message)
      setEditing(null)
      setRefresh((current) => current + 1)
    } catch (exception) {
      if (exception instanceof ApiError) {
        setError(exception.message)
        setErrors(exception.errors)
      } else setError('Referensi tidak dapat disimpan.')
    } finally {
      setSaving(false)
    }
  }

  const deleteRecord = async () => {
    if (!deleteTarget) return
    setSaving(true)
    setError('')
    try {
      const response = await apiRequest<{ message: string }>(`/references/${type}/${deleteTarget.id}`, { method: 'DELETE' })
      setNotice(response.message)
      setDeleteTarget(null)
      setRefresh((current) => current + 1)
    } catch (exception) {
      setError(exception instanceof ApiError ? exception.message : 'Referensi tidak dapat dihapus.')
      setDeleteTarget(null)
    } finally {
      setSaving(false)
    }
  }

  return <div className="page-wrap">
    <header className="page-header reveal">
      <div><span className="eyebrow">Standar data organisasi</span><h1>Data Referensi</h1><p>Kelola pilihan baku agar data personel dan riwayatnya konsisten.</p></div>
      {canManage && <button className="primary-cta compact" onClick={() => openForm()}><span>Tambah {label.toLowerCase()}</span><span className="cta-icon">+</span></button>}
    </header>
    <nav className="reference-tabs" aria-label="Kategori referensi">
      {categories.map((item) => <button key={item.key} type="button" aria-pressed={type === item.key} className={type === item.key ? 'active' : ''} onClick={() => switchCategory(item.key)}>{item.label}</button>)}
    </nav>
    {type === 'pangkat' ? <div className="transaction-note"><strong>Daftar pangkat baku</strong><span>Tamtama, Bintara, Perwira, dan pangkat PNS disediakan oleh sistem. Pemeliharaan daftar hanya oleh System Admin, bukan administrasi harian. Urutan bukan skor merit.</span></div> : !canManage && <div className="transaction-note"><strong>Akses baca</strong><span>Perubahan referensi dikelola oleh System Admin atau Admin SSDM.</span></div>}
    {notice && <div className="alert success" role="status">{notice}</div>}
    {error && <div className="alert error" role="alert">{error}</div>}

    {editing !== null && canManage && <section ref={formSection} className="form-section-shell reference-form-shell"><div className="form-section-core">
      <div className="form-section-heading"><span>+</span><div><h2>{editing === 'new' ? 'Tambah' : 'Edit'} {label.toLowerCase()}</h2><p>Referensi nonaktif tetap tersimpan pada riwayat, tetapi tidak tersedia untuk input baru.</p></div></div>
      <form className="person-form" onSubmit={submit}><div className="form-grid">
        <label><span>Kode</span><input required value={draft.kode} onChange={(event) => field('kode', event.target.value)} maxLength={type === 'jenis-penugasan' ? 20 : type === 'pangkat' || type === 'bidang-fungsi' ? 30 : 50}/>{fieldError('kode') && <small>{fieldError('kode')}</small>}</label>
        <label><span>Nama</span><input required maxLength={255} value={draft.nama} onChange={(event) => field('nama', event.target.value)}/>{fieldError('nama') && <small>{fieldError('nama')}</small>}</label>
        <label><span>Status referensi</span><select value={draft.is_active ? 'active' : 'inactive'} onChange={(event) => field('is_active', event.target.value === 'active')}><option value="active">Aktif</option><option value="inactive">Nonaktif</option></select>{fieldError('is_active') && <small>{fieldError('is_active')}</small>}</label>
        {type === 'unit-organisasi' && <>
          <label><span>Jenis unit</span><select value={draft.jenis_unit} onChange={(event) => { field('jenis_unit', event.target.value); if (event.target.value === 'root') field('parent_id', '') }}>{unitTypes.map(([key, text]) => <option value={key} key={key}>{text}</option>)}</select>{fieldError('jenis_unit') && <small>{fieldError('jenis_unit')}</small>}</label>
          <label className="wide"><span>Induk organisasi</span><select disabled={draft.jenis_unit === 'root'} required={draft.jenis_unit !== 'root'} value={draft.parent_id} onChange={(event) => field('parent_id', event.target.value)}><option value="">Tanpa induk (khusus POLRI)</option>{parents.filter((unit) => !isDescendant(unit) && (unit.is_active || String(unit.id) === draft.parent_id)).map((unit) => <option key={unit.id} value={unit.id}>{unit.nama} · {unit.kode}{unit.is_active ? '' : ' (nonaktif)'}</option>)}</select>{fieldError('parent_id') && <small>{fieldError('parent_id')}</small>}</label>
        </>}
        {type === 'pangkat' && <>
          <label><span>Jenis personel</span><select value={draft.jenis_personel} onChange={(event) => field('jenis_personel', event.target.value)}><option value="polri">POLRI</option><option value="pns">PNS</option></select>{fieldError('jenis_personel') && <small>{fieldError('jenis_personel')}</small>}</label>
          <label><span>Urutan pangkat</span><input required type="number" min="0" max="65535" value={draft.urutan} onChange={(event) => field('urutan', event.target.value)}/><small>Untuk pengurutan dalam jenis personel yang sama, bukan skor merit.</small>{fieldError('urutan') && <small>{fieldError('urutan')}</small>}</label>
        </>}
        {type === 'bidang-fungsi' && <label className="wide"><span>Deskripsi (opsional)</span><textarea maxLength={2000} rows={3} value={draft.deskripsi} onChange={(event) => field('deskripsi', event.target.value)}/>{fieldError('deskripsi') && <small>{fieldError('deskripsi')}</small>}</label>}
      </div><div className="form-actions"><button type="button" className="secondary-cta" disabled={saving} onClick={() => setEditing(null)}>Batal</button><button className="primary-cta" disabled={saving}><span>{saving ? 'Menyimpan…' : 'Simpan referensi'}</span><span className="cta-icon">✓</span></button></div></form>
    </div></section>}

    {deleteTarget && canManage && <div className="reference-confirm" role="alert"><div><strong>Hapus {deleteTarget.nama}?</strong><p>Penghapusan ditolak bila referensi masih dipakai. Gunakan status nonaktif untuk menghentikan penggunaan baru.</p></div><button type="button" className="secondary-cta" disabled={saving} onClick={() => setDeleteTarget(null)}>Batal</button><button type="button" className="danger-action" disabled={saving} onClick={() => void deleteRecord()}>{saving ? 'Memproses…' : 'Ya, hapus'}</button></div>}

    <section className="content-shell"><div className="content-core">
      <div className="reference-list-heading"><h2>{label}</h2><span>{meta?.total ?? '—'} data</span></div>
      <div className="filter-bar reference-filter-bar">
        <form className="search-box" onSubmit={(event) => { event.preventDefault(); setFilter('search', search.trim()) }}><Icon name="search" size={17}/><input aria-label="Cari referensi" placeholder="Cari kode atau nama…" value={search} onChange={(event) => setSearch(event.target.value)}/><button type="submit">Cari</button></form>
        <select aria-label="Filter status referensi" value={params.get('status') ?? ''} onChange={(event) => setFilter('status', event.target.value)}><option value="">Semua status</option><option value="active">Aktif</option><option value="inactive">Nonaktif</option></select>
      </div>
      <div className="data-table-wrap"><table className="data-table reference-table">
        <thead><tr><th>Kode</th><th>Nama</th><th>{type === 'unit-organisasi' ? 'Jenis / Induk' : type === 'pangkat' ? 'Jenis personel / Urutan pangkat' : 'Keterangan'}</th><th>Status</th>{canManage && <th>Aksi</th>}</tr></thead>
        <tbody>
          {loading && <tr className="skeleton-row"><td colSpan={canManage ? 5 : 4}><span/></td></tr>}
          {!loading && records.map((record) => <tr key={record.id}>
            <td><strong>{record.kode}</strong></td><td><strong className="reference-name">{record.nama}</strong></td>
            <td>{type === 'unit-organisasi' ? <div className="scope-summary"><span>{record.jenis_unit_label}<small>{record.parent?.nama ?? 'Induk organisasi'}</small></span></div> : type === 'pangkat' ? <span>{record.jenis_personel?.toUpperCase()} · Urutan {record.urutan}</span> : <span className="muted-cell">{record.deskripsi ?? '—'}</span>}</td>
            <td><span className={`status-pill ${record.is_active ? 'aktif' : 'nonaktif'}`}>{record.is_active ? 'Aktif' : 'Nonaktif'}</span></td>
            {canManage && <td><div className="table-actions"><button type="button" className="text-action" onClick={() => openForm(record)}>Edit</button><button type="button" className="danger-action" onClick={() => { setDeleteTarget(record); setEditing(null); setError('') }}>Hapus</button></div></td>}
          </tr>)}
        </tbody>
      </table></div>
      {!loading && records.length === 0 && <div className="empty-state"><Icon name="briefcase" size={28}/><strong>Belum ada hasil</strong><p>Ubah pencarian atau filter referensi.</p></div>}
      {meta && meta.last_page > 1 && <div className="pagination"><button disabled={meta.current_page === 1} onClick={() => setFilter('page', String(meta.current_page - 1))}>Sebelumnya</button><span>Halaman {meta.current_page} dari {meta.last_page}</span><button disabled={meta.current_page === meta.last_page} onClick={() => setFilter('page', String(meta.current_page + 1))}>Berikutnya</button></div>}
    </div></section>
  </div>
}
