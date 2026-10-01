import { useEffect, useState } from 'react'
import type { FormEvent } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { HistoryDocumentField, HistoryField, HistoryReference } from '../components/HistoryFields'
import { ApiError, apiRequest } from '../lib/api'
import { historyBody, pdfError } from '../lib/historyForm'
import type { Personnel, QualificationRecord, ReferenceOptions } from '../types'

const emptyValues = { jenis_kualifikasi_id: '', bidang_fungsi_id: '', nama_kualifikasi: '', jenjang: '', bidang_studi: '', institusi_penyelenggara: '', tanggal_mulai: '', tanggal_selesai: '', tahun: '', nomor_dokumen: '', keterangan: '' }
const maximumYear = new Date().getFullYear() + 1

export function QualificationFormPage() {
  const { id, qualificationId } = useParams()
  const navigate = useNavigate()
  const editing = !!qualificationId
  const [person, setPerson] = useState<Personnel | null>(null)
  const [references, setReferences] = useState<ReferenceOptions | null>(null)
  const [record, setRecord] = useState<QualificationRecord | null>(null)
  const [values, setValues] = useState(emptyValues)
  const [initial, setInitial] = useState<typeof emptyValues | null>(null)
  const [file, setFile] = useState<File | null>(null)
  const [removeDocument, setRemoveDocument] = useState(false)
  const [errors, setErrors] = useState<Record<string, string[]>>({})
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(true)
  const [submitting, setSubmitting] = useState(false)

  useEffect(() => {
    let active = true
    document.title = `${editing ? 'Edit' : 'Tambah'} Kualifikasi · Merit SDM POLRI`
    Promise.all([
      apiRequest<{ data: Personnel }>(`/personel/${id}`),
      apiRequest<{ data: ReferenceOptions }>('/reference-options'),
      editing ? apiRequest<{ data: QualificationRecord }>(`/kualifikasi/${qualificationId}`) : Promise.resolve(null),
    ]).then(([personResponse, referenceResponse, recordResponse]) => {
      if (!active) return
      if (recordResponse && recordResponse.data.personel_id !== Number(id)) throw new Error('Riwayat tidak sesuai dengan profil personel.')
      setPerson(personResponse.data)
      setReferences(referenceResponse.data)
      if (recordResponse) {
        const item = recordResponse.data
        const loaded = { jenis_kualifikasi_id: String(item.jenis_kualifikasi.id), bidang_fungsi_id: item.bidang_fungsi ? String(item.bidang_fungsi.id) : '',
          nama_kualifikasi: item.nama_kualifikasi, jenjang: item.jenjang ?? '', bidang_studi: item.bidang_studi ?? '', institusi_penyelenggara: item.institusi_penyelenggara ?? '',
          tanggal_mulai: item.tanggal_mulai ?? '', tanggal_selesai: item.tanggal_selesai ?? '', tahun: item.tahun == null ? '' : String(item.tahun), nomor_dokumen: item.nomor_dokumen ?? '', keterangan: item.keterangan ?? '' }
        setRecord(item); setValues(loaded); setInitial(loaded)
      }
    }).catch((exception) => { if (active) { setPerson(null); setError(exception instanceof Error ? exception.message : 'Formulir tidak dapat dimuat.') } })
      .finally(() => { if (active) setLoading(false) })
    return () => { active = false }
  }, [id, qualificationId, editing])

  const setField = (field: keyof typeof values, value: string) => setValues((current) => ({ ...current, [field]: value }))
  const submit = async (event: FormEvent) => {
    event.preventDefault()
    const fileError = pdfError(file)
    if (fileError) { setErrors({ dokumen_pendukung: [fileError] }); return }
    setSubmitting(true); setError(''); setErrors({})
    try {
      await apiRequest(editing ? `/kualifikasi/${qualificationId}` : `/personel/${id}/kualifikasi`, {
        method: 'POST', body: historyBody(values, editing ? initial : null, 'dokumen_pendukung', file, removeDocument),
      })
      navigate(`/personel/${id}`, { state: { message: editing ? 'Kualifikasi berhasil diperbarui.' : 'Kualifikasi berhasil ditambahkan.' } })
    } catch (exception) {
      if (exception instanceof ApiError) { setError(exception.message); setErrors(exception.errors) }
      else setError('Kualifikasi tidak dapat disimpan.')
    } finally { setSubmitting(false) }
  }

  if (loading) return <div className="page-loader"><span/><p>Memuat formulir kualifikasi…</p></div>
  if (!person || !references) return <div className="page-wrap"><div className="alert error" role="alert">{error || 'Formulir tidak tersedia.'}</div><Link to={`/personel/${id}`}>Kembali ke profil</Link></div>
  const textFields = [
    ['nama_kualifikasi', 'Nama kualifikasi', true, 255], ['jenjang', 'Jenjang (opsional)', false, 100], ['bidang_studi', 'Bidang studi (opsional)', false, 255],
    ['institusi_penyelenggara', 'Penyelenggara (opsional)', false, 255], ['nomor_dokumen', 'Nomor dokumen (opsional)', false, 100],
  ] as const
  return <div className="page-wrap form-page">
    <div className="detail-back"><Link to={`/personel/${id}`}>← Kembali ke profil</Link></div>
    <header className="page-header"><div><span className="eyebrow">Portofolio kompetensi</span><h1>{editing ? 'Edit' : 'Tambah'} kualifikasi</h1><p>Riwayat pendidikan, pelatihan, sertifikasi, atau kompetensi {person.nama_lengkap}.</p></div></header>
    <form className="person-form" onSubmit={submit}>
      {error && <div className="alert error" role="alert">{error}</div>}
      <section className="form-section-shell reveal"><div className="form-section-core">
        <div className="form-section-heading"><span>01</span><div><h2>Informasi kegiatan</h2><p>Satu kegiatan menjadi satu catatan faktual; tidak dihitung sebagai skor merit.</p></div></div>
        <fieldset className="form-grid history-fieldset" disabled={submitting}>
          <HistoryReference label="Jenis kualifikasi" value={values.jenis_kualifikasi_id} current={record?.jenis_kualifikasi} options={references.jenis_kualifikasi} error={errors.jenis_kualifikasi_id?.[0]} onChange={(value) => setField('jenis_kualifikasi_id', value)} />
          <HistoryReference label="Bidang / fungsi (opsional)" value={values.bidang_fungsi_id} current={record?.bidang_fungsi} options={references.bidang_fungsi} required={false} error={errors.bidang_fungsi_id?.[0]} onChange={(value) => setField('bidang_fungsi_id', value)} />
          {textFields.map(([key, label, wide, max]) => <HistoryField key={key} label={label} error={errors[key]?.[0]} wide={wide}><input value={values[key]} required={key === 'nama_kualifikasi'} maxLength={max} onChange={(event) => setField(key, event.target.value)} /></HistoryField>)}
          <HistoryField label="Tanggal mulai (opsional)" error={errors.tanggal_mulai?.[0]}><input type="date" value={values.tanggal_mulai} onChange={(event) => setField('tanggal_mulai', event.target.value)} /></HistoryField>
          <HistoryField label="Tanggal selesai (opsional)" error={errors.tanggal_selesai?.[0]}><input type="date" value={values.tanggal_selesai} onChange={(event) => setField('tanggal_selesai', event.target.value)} /></HistoryField>
          <HistoryField label="Tahun (opsional)" error={errors.tahun?.[0]}><input type="number" min="1900" max={maximumYear} value={values.tahun} onChange={(event) => setField('tahun', event.target.value)} /></HistoryField>
          <HistoryField label="Keterangan (opsional)" error={errors.keterangan?.[0]} wide><textarea rows={3} maxLength={1000} value={values.keterangan} onChange={(event) => setField('keterangan', event.target.value)} /></HistoryField>
          <HistoryDocumentField label="PDF pendukung" existing={record?.dokumen} file={file} remove={removeDocument} onFile={setFile} onRemove={setRemoveDocument} error={errors.dokumen_pendukung?.[0] ?? errors.hapus_dokumen?.[0]} />
        </fieldset>
      </div></section>
      <div className="form-actions"><Link className="secondary-cta" to={`/personel/${id}`}>Batal</Link><button className="primary-cta" disabled={submitting}><span>{submitting ? 'Menyimpan…' : 'Simpan kualifikasi'}</span><span className="cta-icon">✓</span></button></div>
    </form>
  </div>
}
