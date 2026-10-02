import { useEffect, useState } from 'react'
import type { FormEvent } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { ApiError, apiRequest } from '../lib/api'
import { UnitSearchSelect } from '../components/UnitSearchSelect'
import type { NonUnitReferenceOptions, Personnel, ReferenceItem } from '../types'

export function MutationFormPage() {
  const { id } = useParams(); const navigate = useNavigate(); const [mode, setMode] = useState<'ganti-jabatan' | 'mutasi'>('mutasi')
  const [person, setPerson] = useState<Personnel | null>(null); const [references, setReferences] = useState<NonUnitReferenceOptions | null>(null)
  const [selectedUnit, setSelectedUnit] = useState<ReferenceItem | null>(null)
  const [values, setValues] = useState({ unit_organisasi_id: '', nama_jabatan: '', bidang_fungsi_id: '', jenis_penugasan_id: '', tanggal_mulai: '', nivelering: '', keterangan: '' })
  const [skDocument, setSkDocument] = useState<File | null>(null); const [errors, setErrors] = useState<Record<string, string[]>>({}); const [error, setError] = useState(''); const [submitting, setSubmitting] = useState(false)

  useEffect(() => { document.title = 'Proses Mutasi · Merit SDM POLRI'; void Promise.all([apiRequest<{ data: Personnel }>(`/personel/${id}`), apiRequest<{ data: NonUnitReferenceOptions }>('/reference-options?only=non_unit')]).then(([a, b]) => { setPerson(a.data); setReferences(b.data) }).catch((exception) => setError(exception instanceof ApiError ? exception.message : 'Formulir tidak dapat dimuat.')) }, [id])
  const setField = (field: keyof typeof values, value: string) => setValues((current) => ({ ...current, [field]: value }))
  const submit = async (event: FormEvent) => { event.preventDefault(); setSubmitting(true); setError(''); setErrors({}); const body = new FormData(); Object.entries(values).forEach(([key, value]) => { if (value && (mode === 'mutasi' || key !== 'unit_organisasi_id')) body.append(key, value) }); if (skDocument) body.append('dokumen_sk', skDocument); try { await apiRequest(`/personel/${id}/${mode}`, { method: 'POST', body }); navigate(`/personel/${id}`) } catch (exception) { if (exception instanceof ApiError) { setError(exception.message); setErrors(exception.errors) } else setError('Proses tidak dapat disimpan.') } finally { setSubmitting(false) } }
  const fieldError = (field: string) => errors[field]?.[0]

  return <div className="page-wrap form-page"><div className="detail-back reveal"><Link to={`/personel/${id}`}>← Kembali ke profil</Link></div><header className="page-header reveal"><div><span className="eyebrow">Proses jabatan atomik</span><h1>{mode === 'mutasi' ? 'Mutasi personel' : 'Ganti jabatan'}</h1><p>Jabatan lama {person?.nama_lengkap ? `milik ${person.nama_lengkap}` : ''} ditutup dan jabatan baru dibuat dalam satu transaksi.</p></div></header>
    <div className="mode-switch reveal delay-one"><button type="button" className={mode === 'mutasi' ? 'active' : ''} onClick={() => setMode('mutasi')}>Mutasi lintas-unit</button><button type="button" className={mode === 'ganti-jabatan' ? 'active' : ''} onClick={() => setMode('ganti-jabatan')}>Ganti jabatan dalam unit</button></div>
    <form className="person-form" onSubmit={submit}>{error && <div className="alert error">{error}</div>}<section className="form-section-shell reveal delay-two"><div className="form-section-core"><div className="form-section-heading"><span>01</span><div><h2>Penetapan jabatan baru</h2><p>Unit asal: {person?.unit_organisasi.nama ?? '—'}.</p></div></div><div className="form-grid">
      {mode === 'mutasi' && <UnitSearchSelect label="Unit tujuan" value={values.unit_organisasi_id} current={selectedUnit} excludeId={person?.unit_organisasi.id} onChange={(value, unit) => { setField('unit_organisasi_id', value); setSelectedUnit(unit ?? null) }} error={fieldError('unit_organisasi_id')} />}
      <label className="wide"><span>Nama jabatan baru</span><input value={values.nama_jabatan} onChange={(e) => setField('nama_jabatan', e.target.value)} required /></label>
      <label><span>Bidang / fungsi</span><select value={values.bidang_fungsi_id} onChange={(e) => setField('bidang_fungsi_id', e.target.value)} required><option value="">Pilih fungsi</option>{references?.bidang_fungsi.map((item) => <option key={item.id} value={item.id}>{item.nama}</option>)}</select></label>
      <label><span>Status jabatan / jenis penugasan</span><select value={values.jenis_penugasan_id} onChange={(e) => setField('jenis_penugasan_id', e.target.value)} required><option value="">Pilih status jabatan</option>{references?.jenis_penugasan.map((item) => <option key={item.id} value={item.id}>{item.nama}</option>)}</select></label>
      <label><span>Tanggal mulai jabatan baru</span><input type="date" value={values.tanggal_mulai} onChange={(e) => setField('tanggal_mulai', e.target.value)} required />{fieldError('tanggal_mulai') && <small>{fieldError('tanggal_mulai')}</small>}</label>
      <label><span>Nivelering (opsional)</span><input value={values.nivelering} onChange={(e) => setField('nivelering', e.target.value)} /></label>
      <label className="wide"><span>Keterangan</span><input value={values.keterangan} onChange={(e) => setField('keterangan', e.target.value)} /></label>
      <label className="wide"><span>SK PDF (opsional, maks. 5 MB)</span><input className="file-input" type="file" accept="application/pdf,.pdf" onChange={(e) => setSkDocument(e.target.files?.[0] ?? null)} />{fieldError('dokumen_sk') && <small>{fieldError('dokumen_sk')}</small>}</label>
    </div></div></section><div className="transaction-note"><strong>Satu transaksi aman</strong><span>Jabatan lama ditutup → Satker diperbarui bila mutasi → jabatan baru dibuat. Kegagalan satu langkah membatalkan semuanya.</span></div><div className="form-actions"><Link className="secondary-cta" to={`/personel/${id}`}>Batal</Link><button className="primary-cta" disabled={submitting}><span>{submitting ? 'Memproses…' : mode === 'mutasi' ? 'Proses mutasi' : 'Ganti jabatan'}</span><span className="cta-icon">✓</span></button></div></form>
  </div>
}
