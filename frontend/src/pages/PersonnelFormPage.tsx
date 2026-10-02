import { useEffect, useMemo, useState } from 'react'
import type { FormEvent } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { ApiError, apiRequest } from '../lib/api'
import { UnitSearchSelect } from '../components/UnitSearchSelect'
import { RegistrationHistories } from '../components/RegistrationHistories'
import { appendRegistrationData, emptyRegistrationRecord, initialRegistrationHistories } from '../lib/registrationDrafts'
import { pdfError } from '../lib/historyForm'
import type { NonUnitReferenceOptions, Personnel } from '../types'

interface FormState {
  jenis_personel: 'polri' | 'pns'; nomor_identitas: string; nama_lengkap: string; pangkat_id: string
  tempat_lahir: string; tanggal_lahir: string; unit_organisasi_id: string
  status: 'aktif' | 'pensiun' | 'nonaktif'; nama_jabatan: string; bidang_fungsi_id: string
  jenis_penugasan_id: string; tanggal_mulai: string; nivelering: string; keterangan: string
}

const initialForm: FormState = { jenis_personel: 'polri', nomor_identitas: '', nama_lengkap: '', pangkat_id: '', tempat_lahir: '', tanggal_lahir: '', unit_organisasi_id: '', status: 'aktif', nama_jabatan: '', bidang_fungsi_id: '', jenis_penugasan_id: '', tanggal_mulai: '', nivelering: '', keterangan: '' }

export function PersonnelFormPage() {
  const { id } = useParams(); const isEdit = Boolean(id); const navigate = useNavigate()
  const [form, setForm] = useState(initialForm); const [references, setReferences] = useState<NonUnitReferenceOptions | null>(null)
  const [currentUnitName, setCurrentUnitName] = useState('')
  const [errors, setErrors] = useState<Record<string, string[]>>({}); const [error, setError] = useState('')
  const [loading, setLoading] = useState(isEdit); const [submitting, setSubmitting] = useState(false)
  const [histories, setHistories] = useState(initialRegistrationHistories)
  const [positionFile, setPositionFile] = useState<File | null>(null)

  useEffect(() => {
    document.title = `${isEdit ? 'Edit' : 'Tambah'} Personel · Merit SDM POLRI`
    void apiRequest<{ data: NonUnitReferenceOptions }>('/reference-options?only=non_unit').then((response) => setReferences(response.data)).catch((exception) => setError(exception instanceof ApiError ? exception.message : 'Referensi tidak dapat dimuat. Muat ulang halaman untuk mencoba lagi.'))
    if (id) void apiRequest<{ data: Personnel }>(`/personel/${id}`).then(({ data: person }) => { setCurrentUnitName(person.unit_organisasi.nama); setForm({ ...initialForm, jenis_personel: person.jenis_personel, nomor_identitas: person.nomor_identitas, nama_lengkap: person.nama_lengkap, pangkat_id: String(person.pangkat.id), tempat_lahir: person.tempat_lahir, tanggal_lahir: person.tanggal_lahir, unit_organisasi_id: String(person.unit_organisasi.id), status: person.status, nama_jabatan: person.jabatan_utama_aktif?.nama_jabatan ?? '' }) }).catch((exception) => setError(exception instanceof ApiError ? exception.message : 'Data tidak dapat dimuat.')).finally(() => setLoading(false))
  }, [id, isEdit])

  const ranks = useMemo(() => references?.pangkat.filter((rank) => rank.jenis_personel === form.jenis_personel) ?? [], [references, form.jenis_personel])
  const setField = (field: keyof FormState, value: string) => setForm((current) => ({ ...current, [field]: value }))
  const fieldError = (field: string) => errors[field]?.[0]

  const handleSubmit = async (event: FormEvent) => {
    event.preventDefault(); setError(''); setErrors({})
    if (!isEdit) {
      const invalidFiles: Record<string, string[]> = {}
      const validateFile = (key: string, file: File | null) => { const message = pdfError(file); if (message) invalidFiles[key] = [message] }
      if (form.status === 'aktif') validateFile('jabatan_utama.dokumen_sk', positionFile)
      validateFile('pendidikan_umum.dokumen_pendukung', histories.pendidikan_umum.file)
      if (histories.pendidikan_polri) validateFile('pendidikan_polri.dokumen_pendukung', histories.pendidikan_polri.file)
      for (const key of ['kualifikasi', 'riwayat_jabatan', 'penugasan_operasi', 'prestasi', 'penghargaan'] as const) histories[key].forEach((record, index) => validateFile(`${key}.${index}.${key === 'kualifikasi' ? 'dokumen_pendukung' : key === 'riwayat_jabatan' ? 'dokumen_sk' : 'dokumen'}`, record.file))
      if (Object.keys(invalidFiles).length) { setErrors(invalidFiles); setError('Periksa PDF yang ditandai sebelum menyimpan.'); return }
    }
    setSubmitting(true)
    const common = { jenis_personel: form.jenis_personel, nomor_identitas: form.nomor_identitas, nama_lengkap: form.nama_lengkap, pangkat_id: Number(form.pangkat_id), tempat_lahir: form.tempat_lahir, tanggal_lahir: form.tanggal_lahir, status: form.status }
    const body = new FormData()
    if (!isEdit) {
      for (const [key, value] of Object.entries(common)) body.append(key, String(value))
      body.append('unit_organisasi_id', form.unit_organisasi_id)
      if (form.status === 'aktif') appendRegistrationData(body, 'jabatan_utama', { values: { nama_jabatan: form.nama_jabatan, bidang_fungsi_id: form.bidang_fungsi_id, jenis_penugasan_id: form.jenis_penugasan_id, tanggal_mulai: form.tanggal_mulai, nivelering: form.nivelering, keterangan: form.keterangan }, file: positionFile }, 'dokumen_sk')
      appendRegistrationData(body, 'pendidikan_umum', histories.pendidikan_umum, 'dokumen_pendukung')
      if (histories.pendidikan_polri) appendRegistrationData(body, 'pendidikan_polri', histories.pendidikan_polri, 'dokumen_pendukung')
      for (const key of ['kualifikasi', 'riwayat_jabatan', 'penugasan_operasi', 'prestasi', 'penghargaan'] as const) histories[key].forEach((record, index) => appendRegistrationData(body, `${key}[${index}]`, record, key === 'kualifikasi' ? 'dokumen_pendukung' : key === 'riwayat_jabatan' ? 'dokumen_sk' : 'dokumen'))
    }
    try {
      const response = await apiRequest<{ data: Personnel }>(isEdit ? `/personel/${id}` : '/personel', { method: isEdit ? 'PUT' : 'POST', body: isEdit ? JSON.stringify(common) : body })
      navigate(`/personel/${response.data.id}`)
    } catch (exception) {
      if (exception instanceof ApiError) { setError(exception.message); setErrors(exception.errors) } else setError('Tidak dapat menyimpan data.')
    } finally { setSubmitting(false) }
  }

  if (loading) return <div className="page-loader"><span/><p>Memuat formulir…</p></div>

  return <div className="page-wrap form-page">
    <div className="detail-back reveal"><Link to={isEdit ? `/personel/${id}` : '/personel'}>← Batal dan kembali</Link></div>
    <header className="page-header reveal"><div><span className="eyebrow">{isEdit ? 'Pemutakhiran data' : 'Registrasi personel'}</span><h1>{isEdit ? 'Edit identitas personel' : 'Tambah personel baru'}</h1><p>{isEdit ? 'Perubahan Satker dilakukan melalui proses mutasi, bukan dari formulir ini.' : 'Identitas, pendidikan, jabatan, dan riwayat awal disimpan bersama dalam satu transaksi.'}</p></div></header>
    <form onSubmit={handleSubmit} className="person-form">
      {error && <div className="alert error full-span">{error}</div>}
      <section className="form-section-shell reveal delay-one"><div className="form-section-core"><div className="form-section-heading"><span>01</span><div><h2>Identitas dasar</h2><p>Data pengenal utama personel.</p></div></div><div className="form-grid">
        <label><span>Jenis personel</span><select value={form.jenis_personel} onChange={(e) => { setField('jenis_personel', e.target.value); setField('pangkat_id', ''); setHistories((current) => ({ ...current, pendidikan_polri: e.target.value === 'polri' ? current.pendidikan_polri ?? emptyRegistrationRecord() : null })) }}><option value="polri">POLRI</option><option value="pns">PNS</option></select></label>
        <label><span>NRP / NIP</span><input value={form.nomor_identitas} onChange={(e) => setField('nomor_identitas', e.target.value)} inputMode="numeric" required />{fieldError('nomor_identitas') && <small>{fieldError('nomor_identitas')}</small>}</label>
        <label className="wide"><span>Nama lengkap</span><input value={form.nama_lengkap} onChange={(e) => setField('nama_lengkap', e.target.value)} required />{fieldError('nama_lengkap') && <small>{fieldError('nama_lengkap')}</small>}</label>
        <label><span>Pangkat</span><select value={form.pangkat_id} onChange={(e) => setField('pangkat_id', e.target.value)} required><option value="">Pilih pangkat</option>{ranks.map((rank) => <option key={rank.id} value={rank.id}>{rank.nama}</option>)}</select>{fieldError('pangkat_id') && <small>{fieldError('pangkat_id')}</small>}</label>
        <label><span>Status personel</span><select value={form.status} onChange={(e) => setField('status', e.target.value)}><option value="aktif">Aktif</option><option value="pensiun">Pensiun</option><option value="nonaktif">Nonaktif</option></select>{fieldError('status') && <small>{fieldError('status')}</small>}</label>
        <label><span>Tempat lahir</span><input value={form.tempat_lahir} onChange={(e) => setField('tempat_lahir', e.target.value)} required /></label>
        <label><span>Tanggal lahir</span><input type="date" value={form.tanggal_lahir} onChange={(e) => setField('tanggal_lahir', e.target.value)} required />{fieldError('tanggal_lahir') && <small>{fieldError('tanggal_lahir')}</small>}</label>
        <label className="wide"><span>Foto personel</span><input type="file" accept="image/*" disabled /><small>Belum tersedia pada versi ujian ini.</small></label>
      </div></div></section>
      <section className="form-section-shell reveal delay-two"><div className="form-section-core"><div className="form-section-heading"><span>02</span><div><h2>{isEdit ? 'Penempatan saat ini' : 'Jabatan utama awal'}</h2><p>{isEdit ? 'Informasi unit ditampilkan sebagai referensi.' : 'Setiap personel aktif wajib memiliki jabatan.'}</p></div></div><div className="form-grid">
        {isEdit ? <label className="wide"><span>Unit organisasi / Satker</span><input value={currentUnitName} disabled /></label> : <UnitSearchSelect label="Unit organisasi / Satker" value={form.unit_organisasi_id} onChange={(value) => setField('unit_organisasi_id', value)} error={fieldError('unit_organisasi_id')} />}
        {!isEdit && form.status === 'aktif' && <>
          <label className="wide"><span>Nama jabatan</span><input value={form.nama_jabatan} onChange={(e) => setField('nama_jabatan', e.target.value)} placeholder="Contoh: Banit Sat Intelkam" required />{fieldError('jabatan_utama.nama_jabatan') && <small>{fieldError('jabatan_utama.nama_jabatan')}</small>}</label>
          <label><span>Bidang / fungsi</span><select value={form.bidang_fungsi_id} onChange={(e) => setField('bidang_fungsi_id', e.target.value)} required><option value="">Pilih fungsi</option>{references?.bidang_fungsi.map((item) => <option key={item.id} value={item.id}>{item.nama}</option>)}</select></label>
          <label><span>Status jabatan / jenis penugasan</span><select value={form.jenis_penugasan_id} onChange={(e) => setField('jenis_penugasan_id', e.target.value)} required><option value="">Pilih status jabatan</option>{references?.jenis_penugasan.map((item) => <option key={item.id} value={item.id}>{item.nama}</option>)}</select><small>Definitif, PS, PLT, atau PLH; berbeda dari status personel.</small>{fieldError('jabatan_utama.jenis_penugasan_id') && <small>{fieldError('jabatan_utama.jenis_penugasan_id')}</small>}</label>
          <label><span>Tanggal mulai</span><input type="date" value={form.tanggal_mulai} onChange={(e) => setField('tanggal_mulai', e.target.value)} required />{fieldError('jabatan_utama.tanggal_mulai') && <small>{fieldError('jabatan_utama.tanggal_mulai')}</small>}</label>
          <label><span>Nivelering jabatan (opsional)</span><input maxLength={50} value={form.nivelering} onChange={(e) => setField('nivelering', e.target.value)} />{fieldError('jabatan_utama.nivelering') && <small>{fieldError('jabatan_utama.nivelering')}</small>}</label>
          <label className="wide"><span>Keterangan jabatan (opsional)</span><textarea rows={3} maxLength={1000} value={form.keterangan} onChange={(e) => setField('keterangan', e.target.value)} />{fieldError('jabatan_utama.keterangan') && <small>{fieldError('jabatan_utama.keterangan')}</small>}</label>
          <label className="wide"><span>PDF SK jabatan utama (opsional, maksimal 5 MB)</span><input type="file" accept=".pdf,application/pdf" onChange={(event) => setPositionFile(event.target.files?.[0] ?? null)} />{fieldError('jabatan_utama.dokumen_sk') && <small>{fieldError('jabatan_utama.dokumen_sk')}</small>}</label>
        </>}
        {!isEdit && form.status !== 'aktif' && <p className="wide dashboard-note">Tidak membuat jabatan aktif. Jabatan terdahulu dapat diisi di bagian riwayat jabatan.</p>}
      </div></div></section>
      {!isEdit && <RegistrationHistories value={histories} onChange={setHistories} references={references} jenisPersonel={form.jenis_personel} errors={errors} />}
      <div className="form-actions"><Link to={isEdit ? `/personel/${id}` : '/personel'} className="secondary-cta">Batal</Link><button className="primary-cta" disabled={submitting || !references}><span>{submitting ? 'Menyimpan…' : 'Simpan personel'}</span><span className="cta-icon">✓</span></button></div>
    </form>
  </div>
}
