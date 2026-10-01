import { useEffect, useMemo, useState } from 'react'
import type { FormEvent } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { ApiError, apiRequest } from '../lib/api'
import type { Personnel, ReferenceOptions } from '../types'

interface FormState {
  jenis_personel: 'polri' | 'pns'; nomor_identitas: string; nama_lengkap: string; pangkat_id: string
  tempat_lahir: string; tanggal_lahir: string; unit_organisasi_id: string
  status: 'aktif' | 'pensiun' | 'nonaktif'; nama_jabatan: string; bidang_fungsi_id: string
  jenis_penugasan_id: string; tanggal_mulai: string
}

const initialForm: FormState = { jenis_personel: 'polri', nomor_identitas: '', nama_lengkap: '', pangkat_id: '', tempat_lahir: '', tanggal_lahir: '', unit_organisasi_id: '', status: 'aktif', nama_jabatan: '', bidang_fungsi_id: '', jenis_penugasan_id: '', tanggal_mulai: '' }

export function PersonnelFormPage() {
  const { id } = useParams(); const isEdit = Boolean(id); const navigate = useNavigate()
  const [form, setForm] = useState(initialForm); const [references, setReferences] = useState<ReferenceOptions | null>(null)
  const [errors, setErrors] = useState<Record<string, string[]>>({}); const [error, setError] = useState('')
  const [loading, setLoading] = useState(isEdit); const [submitting, setSubmitting] = useState(false)

  useEffect(() => {
    document.title = `${isEdit ? 'Edit' : 'Tambah'} Personel · Merit SDM POLRI`
    void apiRequest<{ data: ReferenceOptions }>('/reference-options').then((response) => setReferences(response.data))
    if (id) void apiRequest<{ data: Personnel }>(`/personel/${id}`).then(({ data: person }) => setForm({ jenis_personel: person.jenis_personel, nomor_identitas: person.nomor_identitas, nama_lengkap: person.nama_lengkap, pangkat_id: String(person.pangkat.id), tempat_lahir: person.tempat_lahir, tanggal_lahir: person.tanggal_lahir, unit_organisasi_id: String(person.unit_organisasi.id), status: person.status, nama_jabatan: person.jabatan_utama_aktif?.nama_jabatan ?? '', bidang_fungsi_id: '', jenis_penugasan_id: '', tanggal_mulai: '' })).catch((exception) => setError(exception instanceof ApiError ? exception.message : 'Data tidak dapat dimuat.')).finally(() => setLoading(false))
  }, [id, isEdit])

  const ranks = useMemo(() => references?.pangkat.filter((rank) => rank.jenis_personel === form.jenis_personel) ?? [], [references, form.jenis_personel])
  const setField = (field: keyof FormState, value: string) => setForm((current) => ({ ...current, [field]: value }))
  const fieldError = (field: string) => errors[field]?.[0]

  const handleSubmit = async (event: FormEvent) => {
    event.preventDefault(); setSubmitting(true); setError(''); setErrors({})
    const common = { jenis_personel: form.jenis_personel, nomor_identitas: form.nomor_identitas, nama_lengkap: form.nama_lengkap, pangkat_id: Number(form.pangkat_id), tempat_lahir: form.tempat_lahir, tanggal_lahir: form.tanggal_lahir, status: form.status }
    const payload = isEdit ? common : { ...common, unit_organisasi_id: Number(form.unit_organisasi_id), jabatan_utama: { nama_jabatan: form.nama_jabatan, bidang_fungsi_id: Number(form.bidang_fungsi_id), jenis_penugasan_id: Number(form.jenis_penugasan_id), tanggal_mulai: form.tanggal_mulai } }
    try {
      const response = await apiRequest<{ data: Personnel }>(isEdit ? `/personel/${id}` : '/personel', { method: isEdit ? 'PUT' : 'POST', body: JSON.stringify(payload) })
      navigate(`/personel/${response.data.id}`)
    } catch (exception) {
      if (exception instanceof ApiError) { setError(exception.message); setErrors(exception.errors) } else setError('Tidak dapat menyimpan data.')
    } finally { setSubmitting(false) }
  }

  if (loading) return <div className="page-loader"><span/><p>Memuat formulir…</p></div>

  return <div className="page-wrap form-page">
    <div className="detail-back reveal"><Link to={isEdit ? `/personel/${id}` : '/personel'}>← Batal dan kembali</Link></div>
    <header className="page-header reveal"><div><span className="eyebrow">{isEdit ? 'Pemutakhiran data' : 'Registrasi personel'}</span><h1>{isEdit ? 'Edit identitas personel' : 'Tambah personel baru'}</h1><p>{isEdit ? 'Perubahan Satker dilakukan melalui proses mutasi, bukan dari formulir ini.' : 'Identitas dan jabatan utama awal disimpan bersama dalam satu transaksi.'}</p></div></header>
    <form onSubmit={handleSubmit} className="person-form">
      {error && <div className="alert error full-span">{error}</div>}
      <section className="form-section-shell reveal delay-one"><div className="form-section-core"><div className="form-section-heading"><span>01</span><div><h2>Identitas dasar</h2><p>Data pengenal utama personel.</p></div></div><div className="form-grid">
        <label><span>Jenis personel</span><select value={form.jenis_personel} onChange={(e) => { setField('jenis_personel', e.target.value); setField('pangkat_id', '') }}><option value="polri">POLRI</option><option value="pns">PNS</option></select></label>
        <label><span>NRP / NIP</span><input value={form.nomor_identitas} onChange={(e) => setField('nomor_identitas', e.target.value)} inputMode="numeric" required />{fieldError('nomor_identitas') && <small>{fieldError('nomor_identitas')}</small>}</label>
        <label className="wide"><span>Nama lengkap</span><input value={form.nama_lengkap} onChange={(e) => setField('nama_lengkap', e.target.value)} required />{fieldError('nama_lengkap') && <small>{fieldError('nama_lengkap')}</small>}</label>
        <label><span>Pangkat</span><select value={form.pangkat_id} onChange={(e) => setField('pangkat_id', e.target.value)} required><option value="">Pilih pangkat</option>{ranks.map((rank) => <option key={rank.id} value={rank.id}>{rank.nama}</option>)}</select>{fieldError('pangkat_id') && <small>{fieldError('pangkat_id')}</small>}</label>
        <label><span>Status</span><select value={form.status} onChange={(e) => setField('status', e.target.value)}><option value="aktif">Aktif</option><option value="pensiun">Pensiun</option><option value="nonaktif">Nonaktif</option></select>{fieldError('status') && <small>{fieldError('status')}</small>}</label>
        <label><span>Tempat lahir</span><input value={form.tempat_lahir} onChange={(e) => setField('tempat_lahir', e.target.value)} required /></label>
        <label><span>Tanggal lahir</span><input type="date" value={form.tanggal_lahir} onChange={(e) => setField('tanggal_lahir', e.target.value)} required />{fieldError('tanggal_lahir') && <small>{fieldError('tanggal_lahir')}</small>}</label>
      </div></div></section>
      <section className="form-section-shell reveal delay-two"><div className="form-section-core"><div className="form-section-heading"><span>02</span><div><h2>{isEdit ? 'Penempatan saat ini' : 'Jabatan utama awal'}</h2><p>{isEdit ? 'Informasi unit ditampilkan sebagai referensi.' : 'Setiap personel aktif wajib memiliki jabatan.'}</p></div></div><div className="form-grid">
        <label className="wide"><span>Unit organisasi / Satker</span><select disabled={isEdit} value={form.unit_organisasi_id} onChange={(e) => setField('unit_organisasi_id', e.target.value)} required={!isEdit}><option value="">Pilih unit</option>{references?.unit_organisasi.map((unit) => <option key={unit.id} value={unit.id}>{unit.nama}</option>)}</select>{fieldError('unit_organisasi_id') && <small>{fieldError('unit_organisasi_id')}</small>}</label>
        {!isEdit && <><label className="wide"><span>Nama jabatan</span><input value={form.nama_jabatan} onChange={(e) => setField('nama_jabatan', e.target.value)} placeholder="Contoh: Banit Sat Intelkam" required /></label><label><span>Bidang / fungsi</span><select value={form.bidang_fungsi_id} onChange={(e) => setField('bidang_fungsi_id', e.target.value)} required><option value="">Pilih fungsi</option>{references?.bidang_fungsi.map((item) => <option key={item.id} value={item.id}>{item.nama}</option>)}</select></label><label><span>Jenis penugasan</span><select value={form.jenis_penugasan_id} onChange={(e) => setField('jenis_penugasan_id', e.target.value)} required><option value="">Pilih jenis</option>{references?.jenis_penugasan.map((item) => <option key={item.id} value={item.id}>{item.nama}</option>)}</select></label><label><span>Tanggal mulai</span><input type="date" value={form.tanggal_mulai} onChange={(e) => setField('tanggal_mulai', e.target.value)} required /></label></>}
      </div></div></section>
      <div className="form-actions"><Link to={isEdit ? `/personel/${id}` : '/personel'} className="secondary-cta">Batal</Link><button className="primary-cta" disabled={submitting}><span>{submitting ? 'Menyimpan…' : 'Simpan personel'}</span><span className="cta-icon">✓</span></button></div>
    </form>
  </div>
}
