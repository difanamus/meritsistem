import { useEffect, useState } from 'react'
import type { FormEvent } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { ApiError, apiRequest } from '../lib/api'
import type { Personnel, ReferenceOptions } from '../types'

export function QualificationFormPage() {
  const { id } = useParams(); const navigate = useNavigate()
  const [person, setPerson] = useState<Personnel | null>(null); const [references, setReferences] = useState<ReferenceOptions | null>(null)
  const [values, setValues] = useState({ jenis_kualifikasi_id: '', bidang_fungsi_id: '', nama_kualifikasi: '', jenjang: '', institusi_penyelenggara: '', tanggal_mulai: '', tanggal_selesai: '', tahun: '' })
  const [supportingDocument, setSupportingDocument] = useState<File | null>(null); const [errors, setErrors] = useState<Record<string, string[]>>({}); const [error, setError] = useState(''); const [submitting, setSubmitting] = useState(false)

  useEffect(() => {
    document.title = 'Tambah Kualifikasi · Merit SDM POLRI'
    void Promise.all([apiRequest<{ data: Personnel }>(`/personel/${id}`), apiRequest<{ data: ReferenceOptions }>('/reference-options')]).then(([personResponse, referenceResponse]) => { setPerson(personResponse.data); setReferences(referenceResponse.data) }).catch((exception) => setError(exception instanceof ApiError ? exception.message : 'Formulir tidak dapat dimuat.'))
  }, [id])

  const setField = (field: keyof typeof values, value: string) => setValues((current) => ({ ...current, [field]: value }))
  const fieldError = (field: string) => errors[field]?.[0]
  const submit = async (event: FormEvent) => {
    event.preventDefault(); setSubmitting(true); setError(''); setErrors({})
    const body = new FormData()
    Object.entries(values).forEach(([key, value]) => { if (value) body.append(key, value) })
    if (supportingDocument) body.append('dokumen_pendukung', supportingDocument)
    try { await apiRequest(`/personel/${id}/kualifikasi`, { method: 'POST', body }); navigate(`/personel/${id}`) }
    catch (exception) { if (exception instanceof ApiError) { setError(exception.message); setErrors(exception.errors) } else setError('Kualifikasi tidak dapat disimpan.') }
    finally { setSubmitting(false) }
  }

  return <div className="page-wrap form-page"><div className="detail-back reveal"><Link to={`/personel/${id}`}>← Kembali ke profil</Link></div><header className="page-header reveal"><div><span className="eyebrow">Portofolio kompetensi</span><h1>Tambah kualifikasi</h1><p>Catat pendidikan, pelatihan, sertifikasi, atau kompetensi untuk {person?.nama_lengkap ?? 'personel'}.</p></div></header>
    <form className="person-form" onSubmit={submit}>{error && <div className="alert error">{error}</div>}<section className="form-section-shell reveal delay-one"><div className="form-section-core"><div className="form-section-heading"><span>01</span><div><h2>Informasi kegiatan</h2><p>Satu kegiatan disimpan sebagai satu riwayat tersendiri.</p></div></div><div className="form-grid">
      <label><span>Jenis kualifikasi</span><select value={values.jenis_kualifikasi_id} onChange={(e) => setField('jenis_kualifikasi_id', e.target.value)} required><option value="">Pilih jenis</option>{references?.jenis_kualifikasi.map((item) => <option key={item.id} value={item.id}>{item.nama}</option>)}</select>{fieldError('jenis_kualifikasi_id') && <small>{fieldError('jenis_kualifikasi_id')}</small>}</label>
      <label><span>Bidang / fungsi (opsional)</span><select value={values.bidang_fungsi_id} onChange={(e) => setField('bidang_fungsi_id', e.target.value)}><option value="">Kualifikasi umum</option>{references?.bidang_fungsi.map((item) => <option key={item.id} value={item.id}>{item.nama}</option>)}</select></label>
      <label className="wide"><span>Nama kualifikasi</span><input value={values.nama_kualifikasi} onChange={(e) => setField('nama_kualifikasi', e.target.value)} placeholder="Contoh: Kejuruan Intelijen Lanjutan" required />{fieldError('nama_kualifikasi') && <small>{fieldError('nama_kualifikasi')}</small>}</label>
      <label><span>Jenjang</span><input value={values.jenjang} onChange={(e) => setField('jenjang', e.target.value)} placeholder="Contoh: Lanjutan / S2" /></label>
      <label><span>Penyelenggara</span><input value={values.institusi_penyelenggara} onChange={(e) => setField('institusi_penyelenggara', e.target.value)} /></label>
      <label><span>Tanggal mulai</span><input type="date" value={values.tanggal_mulai} onChange={(e) => setField('tanggal_mulai', e.target.value)} /></label>
      <label><span>Tanggal selesai</span><input type="date" value={values.tanggal_selesai} onChange={(e) => setField('tanggal_selesai', e.target.value)} />{fieldError('tanggal_selesai') && <small>{fieldError('tanggal_selesai')}</small>}</label>
      <label><span>Tahun</span><input type="number" min="1900" max="2100" value={values.tahun} onChange={(e) => setField('tahun', e.target.value)} /></label>
      <label><span>PDF pendukung (opsional, maks. 5 MB)</span><input className="file-input" type="file" accept="application/pdf,.pdf" onChange={(e) => setSupportingDocument(e.target.files?.[0] ?? null)} />{fieldError('dokumen_pendukung') && <small>{fieldError('dokumen_pendukung')}</small>}</label>
    </div></div></section><div className="form-actions"><Link className="secondary-cta" to={`/personel/${id}`}>Batal</Link><button className="primary-cta" disabled={submitting}><span>{submitting ? 'Menyimpan…' : 'Simpan kualifikasi'}</span><span className="cta-icon">✓</span></button></div></form>
  </div>
}
