import { useEffect, useState } from 'react'
import type { FormEvent } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { HistoryDocumentField, HistoryField, HistoryReference } from '../components/HistoryFields'
import { ApiError, apiRequest } from '../lib/api'
import { historyBody, isActivePrimary, pdfError } from '../lib/historyForm'
import type { Personnel, PositionRecord, ReferenceOptions } from '../types'

const emptyValues = { nama_jabatan: '', unit_organisasi_id: '', bidang_fungsi_id: '', jenis_penugasan_id: '', tanggal_mulai: '', tanggal_selesai: '', nivelering: '', keterangan: '' }

export function PositionFormPage() {
  const { id, positionId } = useParams()
  const navigate = useNavigate()
  const editing = !!positionId
  const [person, setPerson] = useState<Personnel | null>(null)
  const [references, setReferences] = useState<ReferenceOptions | null>(null)
  const [record, setRecord] = useState<PositionRecord | null>(null)
  const [values, setValues] = useState(emptyValues)
  const [initial, setInitial] = useState<typeof emptyValues | null>(null)
  const [primary, setPrimary] = useState(false)
  const [file, setFile] = useState<File | null>(null)
  const [removeDocument, setRemoveDocument] = useState(false)
  const [errors, setErrors] = useState<Record<string, string[]>>({})
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(true)
  const [submitting, setSubmitting] = useState(false)

  useEffect(() => {
    let active = true
    document.title = `${editing ? 'Edit' : 'Tambah'} Riwayat Jabatan · Merit SDM POLRI`
    Promise.all([
      apiRequest<{ data: Personnel }>(`/personel/${id}`),
      apiRequest<{ data: ReferenceOptions }>('/reference-options'),
      editing ? apiRequest<{ data: PositionRecord }>(`/riwayat-jabatan/${positionId}`) : Promise.resolve(null),
    ]).then(([personResponse, referenceResponse, recordResponse]) => {
      if (!active) return
      if (recordResponse && recordResponse.data.personel_id !== Number(id)) throw new Error('Riwayat tidak sesuai dengan profil personel.')
      setPerson(personResponse.data); setReferences(referenceResponse.data)
      if (recordResponse) {
        const item = recordResponse.data
        const loaded = { nama_jabatan: item.nama_jabatan, unit_organisasi_id: String(item.unit_organisasi.id), bidang_fungsi_id: String(item.bidang_fungsi.id), jenis_penugasan_id: String(item.jenis_penugasan.id), tanggal_mulai: item.tanggal_mulai, tanggal_selesai: item.tanggal_selesai ?? '', nivelering: item.nivelering ?? '', keterangan: item.keterangan ?? '' }
        setRecord(item); setPrimary(item.is_jabatan_utama); setValues(loaded); setInitial(loaded)
      } else setValues({ ...emptyValues, unit_organisasi_id: String(personResponse.data.unit_organisasi.id) })
    }).catch((exception) => { if (active) { setPerson(null); setError(exception instanceof Error ? exception.message : 'Formulir tidak dapat dimuat.') } })
      .finally(() => { if (active) setLoading(false) })
    return () => { active = false }
  }, [id, positionId, editing])

  const activePrimary = record ? isActivePrimary(record) : false
  const setField = (field: keyof typeof values, value: string) => setValues((current) => ({ ...current, [field]: value }))
  const submit = async (event: FormEvent) => {
    event.preventDefault()
    const fileError = pdfError(file)
    if (fileError) { setErrors({ dokumen_sk: [fileError] }); return }
    setSubmitting(true); setError(''); setErrors({})
    const body = historyBody(values, editing ? initial : null, 'dokumen_sk', file, removeDocument)
    if (!editing) body.append('is_jabatan_utama', primary ? '1' : '0')
    try {
      await apiRequest(editing ? `/riwayat-jabatan/${positionId}` : `/personel/${id}/riwayat-jabatan`, { method: 'POST', body })
      navigate(`/personel/${id}`, { state: { message: editing ? 'Riwayat jabatan berhasil diperbarui.' : 'Riwayat jabatan berhasil ditambahkan.' } })
    } catch (exception) {
      if (exception instanceof ApiError) { setError(exception.message); setErrors(exception.errors) }
      else setError('Riwayat jabatan tidak dapat disimpan.')
    } finally { setSubmitting(false) }
  }

  if (loading) return <div className="page-loader"><span/><p>Memuat formulir jabatan…</p></div>
  if (!person || !references) return <div className="page-wrap"><div className="alert error" role="alert">{error || 'Formulir tidak tersedia.'}</div><Link to={`/personel/${id}`}>Kembali ke profil</Link></div>
  return <div className="page-wrap form-page">
    <div className="detail-back"><Link to={`/personel/${id}`}>← Kembali ke profil</Link></div>
    <header className="page-header"><div><span className="eyebrow">Perjalanan karier</span><h1>{editing ? 'Edit' : 'Tambah'} riwayat jabatan</h1><p>Catatan jabatan {person.nama_lengkap}; setiap personel tetap mempunyai jabatan organisasi.</p></div></header>
    <div className="transaction-note"><strong>{activePrimary ? 'Jabatan utama aktif' : 'Aturan periode'}</strong><span>{activePrimary ? 'Edit hanya untuk koreksi data atau dokumen. Unit dan tanggal selesai dikunci; gunakan ganti jabatan/mutasi untuk perpindahan.' : 'Riwayat utama tidak boleh bertumpang tindih. Penugasan tambahan boleh berjalan bersama jabatan utama.'} <Link to={`/personel/${id}/mutasi`}>Buka proses ganti jabatan/mutasi →</Link></span></div>
    <form className="person-form" onSubmit={submit}>
      {error && <div className="alert error" role="alert">{error}</div>}
      <section className="form-section-shell reveal"><div className="form-section-core">
        <div className="form-section-heading"><span>01</span><div><h2>Informasi jabatan</h2><p>PDF SK tidak wajib. Jenis penugasan mengikuti referensi yang ditetapkan Admin.</p></div></div>
        <fieldset className="form-grid history-fieldset" disabled={submitting}>
          <HistoryField label="Kategori riwayat" error={errors.is_jabatan_utama?.[0]}><select value={primary ? '1' : '0'} disabled={editing} onChange={(event) => setPrimary(event.target.value === '1')}><option value="1">Jabatan utama</option><option value="0">Penugasan tambahan</option></select></HistoryField>
          <HistoryReference label="Unit jabatan" value={values.unit_organisasi_id} current={record?.unit_organisasi} options={references.unit_organisasi} disabled={activePrimary} error={errors.unit_organisasi_id?.[0]} onChange={(value) => setField('unit_organisasi_id', value)} />
          <HistoryField label="Nama jabatan" error={errors.nama_jabatan?.[0]} wide><input required maxLength={255} value={values.nama_jabatan} onChange={(event) => setField('nama_jabatan', event.target.value)} /></HistoryField>
          <HistoryReference label="Bidang / fungsi" value={values.bidang_fungsi_id} current={record?.bidang_fungsi} options={references.bidang_fungsi} error={errors.bidang_fungsi_id?.[0]} onChange={(value) => setField('bidang_fungsi_id', value)} />
          <HistoryReference label="Jenis penugasan" value={values.jenis_penugasan_id} current={record?.jenis_penugasan} options={references.jenis_penugasan} error={errors.jenis_penugasan_id?.[0]} onChange={(value) => setField('jenis_penugasan_id', value)} />
          <HistoryField label="Tanggal mulai" error={errors.tanggal_mulai?.[0]}><input type="date" required value={values.tanggal_mulai} onChange={(event) => setField('tanggal_mulai', event.target.value)} /></HistoryField>
          <HistoryField label="Tanggal selesai (kosong = masih berjalan)" error={errors.tanggal_selesai?.[0]}><input type="date" disabled={activePrimary} value={values.tanggal_selesai} onChange={(event) => setField('tanggal_selesai', event.target.value)} /></HistoryField>
          <HistoryField label="Nivelering (opsional)" error={errors.nivelering?.[0]}><input maxLength={50} value={values.nivelering} onChange={(event) => setField('nivelering', event.target.value)} /></HistoryField>
          <HistoryField label="Keterangan (opsional)" error={errors.keterangan?.[0]} wide><textarea rows={3} maxLength={1000} value={values.keterangan} onChange={(event) => setField('keterangan', event.target.value)} /></HistoryField>
          <HistoryDocumentField label="SK PDF" existing={record?.dokumen} file={file} remove={removeDocument} onFile={setFile} onRemove={setRemoveDocument} error={errors.dokumen_sk?.[0] ?? errors.hapus_dokumen?.[0]} />
        </fieldset>
      </div></section>
      <div className="form-actions"><Link className="secondary-cta" to={`/personel/${id}`}>Batal</Link><button className="primary-cta" disabled={submitting}><span>{submitting ? 'Menyimpan…' : 'Simpan riwayat jabatan'}</span><span className="cta-icon">✓</span></button></div>
    </form>
  </div>
}
