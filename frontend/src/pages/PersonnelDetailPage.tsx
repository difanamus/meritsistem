import { useEffect, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { Icon } from '../components/Icon'
import { ApiError, apiRequest } from '../lib/api'
import type { Personnel, Position } from '../types'

function relationName(value?: Position['unit_organisasi']) {
  return typeof value === 'string' ? value : value?.nama ?? '—'
}

export function PersonnelDetailPage() {
  const { id } = useParams()
  const [person, setPerson] = useState<Personnel | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  useEffect(() => {
    apiRequest<{ data: Personnel }>(`/personel/${id}`)
      .then((response) => {
        setPerson(response.data)
        document.title = `${response.data.nama_lengkap} · Merit SDM POLRI`
      })
      .catch((exception) => setError(exception instanceof ApiError ? exception.message : 'Profil tidak dapat dimuat.'))
      .finally(() => setLoading(false))
  }, [id])

  if (loading) return <div className="page-loader"><span/><p>Memuat profil personel…</p></div>
  if (!person) return <div className="page-wrap"><div className="alert error">{error || 'Personel tidak ditemukan.'}</div><Link to="/personel">Kembali</Link></div>

  return (
    <div className="page-wrap detail-page">
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

      <section className="profile-section reveal">
        <div className="section-heading"><div><span className="eyebrow">Kompetensi</span><h2>Kualifikasi personel</h2></div><button className="secondary-cta" type="button">+ Tambah kualifikasi</button></div>
        <div className="qualification-grid">
          {person.kualifikasi?.map((item) => <article className="qualification-card" key={item.id}><span>{item.jenis_kualifikasi}</span><h3>{item.nama_kualifikasi}</h3><p>{item.bidang_fungsi ?? 'Kualifikasi umum'}</p><strong>{item.tahun ?? '—'}</strong></article>)}
          {!person.kualifikasi?.length && <div className="empty-inline">Belum ada data kualifikasi.</div>}
        </div>
      </section>

      <section className="profile-section reveal">
        <div className="section-heading"><div><span className="eyebrow">Perjalanan karier</span><h2>Riwayat jabatan</h2></div><button className="secondary-cta" type="button">Proses mutasi</button></div>
        <div className="timeline">
          {person.riwayat_jabatan?.map((item, index) => <article className="timeline-item" key={item.id}><div className="timeline-marker"><span>{String(index + 1).padStart(2, '0')}</span></div><div className="timeline-content"><div><span className="timeline-date">{new Date(item.tanggal_mulai).toLocaleDateString('id-ID', { month: 'short', year: 'numeric' })} — {item.tanggal_selesai ? new Date(item.tanggal_selesai).toLocaleDateString('id-ID', { month: 'short', year: 'numeric' }) : 'Sekarang'}</span><h3>{item.nama_jabatan}</h3><p>{relationName(item.unit_organisasi)} · {relationName(item.bidang_fungsi)}</p></div><span className={item.is_jabatan_utama ? 'position-kind main' : 'position-kind'}>{item.is_jabatan_utama ? 'Utama' : 'Tambahan'}</span></div></article>)}
        </div>
      </section>
    </div>
  )
}
