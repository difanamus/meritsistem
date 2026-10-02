import { useEffect, useState } from 'react'
import type { KeyboardEvent } from 'react'
import { Link, useLocation, useNavigate, useParams } from 'react-router-dom'
import { Icon } from '../components/Icon'
import { HistoryDelete, PositionSection, QualificationSection } from '../components/PersonnelHistory'
import { MeritSection } from '../components/MeritSection'
import { ApiError, apiRequest, peekApiCache } from '../lib/api'
import type { Personnel, Position } from '../types'

function relationName(value?: Position['unit_organisasi']) {
  return typeof value === 'string' ? value : value?.nama ?? '—'
}

const profileTabs = [
  { id: 'kualifikasi', label: 'Kualifikasi' },
  { id: 'jabatan', label: 'Riwayat jabatan' },
  { id: 'operasi', label: 'Penugasan operasi' },
  { id: 'prestasi', label: 'Prestasi' },
  { id: 'penghargaan', label: 'Penghargaan' },
] as const

type ProfileTab = (typeof profileTabs)[number]['id']

export function PersonnelDetailPage() {
  const { id } = useParams()

  return <PersonnelDetailContent key={id} id={id} />
}

function PersonnelDetailContent({ id }: { id: string | undefined }) {
  const navigate = useNavigate()
  const location = useLocation()
  const cachedProfile = id ? peekApiCache<{ data: Personnel }>(`/personel/${id}`) : null
  const [revision, setRevision] = useState(0)
  const [person, setPerson] = useState<Personnel | null>(() => cachedProfile?.data ?? null)
  const [loadedId, setLoadedId] = useState<string | undefined>(() => cachedProfile ? id : undefined)
  const [error, setError] = useState('')
  const [activeTab, setActiveTab] = useState<ProfileTab>('kualifikasi')
  const [visitedTabs, setVisitedTabs] = useState<ProfileTab[]>(['kualifikasi'])

  const selectTab = (tab: ProfileTab) => {
    setActiveTab(tab)
    setVisitedTabs((visited) => visited.includes(tab) ? visited : [...visited, tab])
  }

  const handleTabKey = (event: KeyboardEvent<HTMLButtonElement>, tab: ProfileTab) => {
    const current = profileTabs.findIndex((item) => item.id === tab)
    const next = event.key === 'ArrowRight' ? (current + 1) % profileTabs.length
      : event.key === 'ArrowLeft' ? (current - 1 + profileTabs.length) % profileTabs.length
        : event.key === 'Home' ? 0 : event.key === 'End' ? profileTabs.length - 1 : null
    if (next === null) return
    event.preventDefault()
    selectTab(profileTabs[next].id)
    event.currentTarget.parentElement?.querySelectorAll<HTMLButtonElement>('[role="tab"]')[next]?.focus()
  }

  useEffect(() => {
    let active = true
    apiRequest<{ data: Personnel }>(`/personel/${id}`)
      .then((response) => {
        if (!active) return
        setError('')
        setPerson(response.data)
        document.title = `${response.data.nama_lengkap} · Merit SDM POLRI`
      })
      .catch((exception) => { if (active) { if (exception instanceof ApiError && [401, 403, 404].includes(exception.status)) setPerson(null); setError(exception instanceof ApiError ? exception.message : 'Profil tidak dapat dimuat.') } })
      .finally(() => { if (active) setLoadedId(id) })
    return () => { active = false }
  }, [id, revision])

  if (loadedId !== id) return <div className="page-loader"><span/><p>Memuat profil personel…</p></div>
  if (!person) return <div className="page-wrap"><div className="alert error">{error || 'Personel tidak ditemukan.'}</div><Link to="/personel">Kembali</Link></div>

  return (
    <div className="page-wrap detail-page">
      {location.state?.message && <div className="alert success" role="status">{location.state.message}</div>}
      <div className="detail-back reveal"><Link to="/personel">← Kembali ke daftar</Link><Link className="secondary-cta" to={`/personel/${person.id}/edit`}>Edit identitas</Link></div>

      <header className="profile-hero reveal delay-one">
        <div className="profile-identity"><span className="profile-monogram">{person.nama_lengkap.split(' ').map((part) => part[0]).slice(0, 2).join('')}</span><div><span className="eyebrow">Profil personel</span><h1>{person.nama_lengkap}</h1><p>{person.pangkat.nama} · {person.jenis_personel_label} · {person.nomor_identitas}</p></div></div>
        <div className="profile-status"><span className={`status-pill ${person.status}`}>{person.status_label}</span><small>Diperbarui {new Date(person.updated_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })}</small></div>
      </header>

      <section className="detail-bento">
        <div className="feature-shell current-position reveal delay-two"><div className="feature-core">
          <span className="feature-icon"><Icon name="briefcase" size={23}/></span><span className="eyebrow">Jabatan utama aktif</span>
          <h2>{person.jabatan_utama_aktif?.nama_jabatan ?? 'Belum ditetapkan'}</h2>
          <p>{relationName(person.jabatan_utama_aktif?.unit_organisasi)}</p>
          {person.jabatan_utama_aktif && <div className="feature-meta"><span>Mulai {new Date(person.jabatan_utama_aktif.tanggal_mulai).toLocaleDateString('id-ID', { month: 'long', year: 'numeric' })}</span><span>{relationName(person.jabatan_utama_aktif.bidang_fungsi)}</span></div>}
        </div></div>
        <div className="feature-shell qualification-stat reveal delay-three"><div className="feature-core"><span className="feature-icon"><Icon name="award" size={23}/></span><span className="eyebrow">Portofolio kualifikasi</span><strong className="large-number">{person.jumlah_kualifikasi ?? person.kualifikasi?.length ?? 0}</strong><p>riwayat pendidikan, pelatihan, sertifikasi, dan kompetensi.</p></div></div>
        <div className="feature-shell identity-facts reveal delay-three"><div className="feature-core"><span className="eyebrow">Data pokok</span><dl><div><dt>Tempat, tanggal lahir</dt><dd>{person.tempat_lahir}, {new Date(person.tanggal_lahir).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })}</dd></div><div><dt>Satker saat ini</dt><dd>{person.unit_organisasi.nama}</dd></div><div><dt>Jenis personel</dt><dd>{person.jenis_personel_label}</dd></div></dl></div></div>
      </section>

      <section className="profile-section reveal" aria-label="Penugasan tambahan aktif">
        <div className="section-heading"><div><span className="eyebrow">Penugasan bersamaan</span><h2>Penugasan tambahan aktif</h2></div></div>
        <div className="qualification-grid">{person.penugasan_tambahan_aktif?.map((item) => <article className="qualification-card" key={item.id}><span>Penugasan tambahan</span><h3>{item.nama_jabatan}</h3><p>Mulai {item.tanggal_mulai}</p><Link className="text-action" to={`/personel/${person.id}/riwayat-jabatan/${item.id}/edit`}>Buka riwayat</Link></article>)}
          {!person.penugasan_tambahan_aktif?.length && <div className="empty-inline">Tidak ada penugasan tambahan aktif.</div>}
        </div>
      </section>
      <div className="profile-tabs" role="tablist" aria-label="Riwayat personel">
        {profileTabs.map((tab) => <button key={tab.id} id={`profile-tab-${tab.id}`} type="button" role="tab" tabIndex={activeTab === tab.id ? 0 : -1} aria-controls={`profile-panel-${tab.id}`} aria-selected={activeTab === tab.id} className={activeTab === tab.id ? 'active' : ''} onClick={() => selectTab(tab.id)} onKeyDown={(event) => handleTabKey(event, tab.id)}>{tab.label}</button>)}
      </div>
      {profileTabs.map((item) => <div key={`${person.id}-${item.id}`} id={`profile-panel-${item.id}`} role="tabpanel" aria-labelledby={`profile-tab-${item.id}`} hidden={activeTab !== item.id}>
        {visitedTabs.includes(item.id) && item.id === 'kualifikasi' && <QualificationSection personnelId={person.id} onChange={() => setRevision((value) => value + 1)} />}
        {visitedTabs.includes(item.id) && item.id === 'jabatan' && <PositionSection personnelId={person.id} onChange={() => setRevision((value) => value + 1)} />}
        {visitedTabs.includes(item.id) && item.id === 'operasi' && <MeritSection personnelId={person.id} kind="penugasan-operasi" />}
        {visitedTabs.includes(item.id) && item.id === 'prestasi' && <MeritSection personnelId={person.id} kind="prestasi" />}
        {visitedTabs.includes(item.id) && item.id === 'penghargaan' && <MeritSection personnelId={person.id} kind="penghargaan" />}
      </div>)}
      <section className="profile-section">
        <div className="content-shell"><div className="content-core dashboard-panel"><h2>Arsipkan data personel</h2><p className="dashboard-note">Hapus mengarsipkan profil dari daftar aktif dan menutup jabatan utama yang masih berjalan. Riwayat serta dokumen tetap tersimpan; pemulihan belum tersedia pada UI.</p>
          <HistoryDelete kind="personel" name={person.nama_lengkap} path={`/personel/${person.id}`} onDeleted={() => navigate('/personel', { state: { message: 'Data personel berhasil diarsipkan.' } })} />
        </div></div>
      </section>
    </div>
  )
}
