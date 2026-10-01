import { useEffect, useState } from 'react'
import type { FormEvent } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { HistoryDocumentField, HistoryField, HistoryReference } from '../components/HistoryFields'
import { ApiError, apiRequest } from '../lib/api'
import { historyBody, pdfError } from '../lib/historyForm'
import { meritConfig, meritValues } from '../lib/meritProfile'
import type { MeritKind, MeritRecord, Personnel, ReferenceOptions } from '../types'

const maximumYear = new Date().getFullYear() + 1

export function MeritFormPage() {
  const { id, kind, recordId } = useParams()
  const navigate = useNavigate()
  const validKind = kind && Object.hasOwn(meritConfig, kind) ? kind as MeritKind : null
  const [person, setPerson] = useState<Personnel | null>(null)
  const [references, setReferences] = useState<ReferenceOptions | null>(null)
  const [record, setRecord] = useState<MeritRecord | null>(null)
  const [values, setValues] = useState<Record<string, string>>({})
  const [initial, setInitial] = useState<Record<string, string> | null>(null)
  const [file, setFile] = useState<File | null>(null)
  const [removeDocument, setRemoveDocument] = useState(false)
  const [errors, setErrors] = useState<Record<string, string[]>>({})
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(true)
  const [submitting, setSubmitting] = useState(false)

  useEffect(() => {
    if (!validKind) return
    let active = true
    document.title = `${recordId ? 'Edit' : 'Tambah'} ${meritConfig[validKind].title} · Merit SDM POLRI`
    Promise.all([
      apiRequest<{ data: Personnel }>(`/personel/${id}`),
      apiRequest<{ data: ReferenceOptions }>('/reference-options'),
      recordId ? apiRequest<{ data: MeritRecord }>(`/merit/${validKind}/${recordId}`) : Promise.resolve(null),
    ]).then(([personResult, optionsResult, recordResult]) => {
      if (!active) return
      if (recordResult && recordResult.data.personel_id !== Number(id)) throw new Error('Riwayat tidak sesuai profil personel.')
      const loaded = meritValues(validKind, recordResult?.data as unknown as Record<string, unknown> | undefined)
      setPerson(personResult.data); setReferences(optionsResult.data); setRecord(recordResult?.data ?? null)
      setValues(loaded); setInitial(recordResult ? loaded : null)
    }).catch((exception) => { if (active) setError(exception instanceof Error ? exception.message : 'Form tidak dapat dimuat.') })
      .finally(() => { if (active) setLoading(false) })
    return () => { active = false }
  }, [id, validKind, recordId])

  if (!validKind) return <div className="page-wrap"><div className="alert error">Jenis merit tidak ditemukan.</div></div>
  const config = meritConfig[validKind]
  const setField = (key: string, value: string) => setValues((current) => ({ ...current, [key]: value }))
  const submit = async (event: FormEvent) => {
    event.preventDefault()
    const invalidFile = pdfError(file)
    if (invalidFile) { setErrors({ dokumen: [invalidFile] }); return }
    setSubmitting(true); setError(''); setErrors({})
    try {
      await apiRequest(recordId ? `/merit/${validKind}/${recordId}` : `/personel/${id}/merit/${validKind}`, {
        method: 'POST', body: historyBody(values, initial, 'dokumen', file, removeDocument),
      })
      navigate(`/personel/${id}`, { state: { message: `${config.title} berhasil disimpan.` } })
    } catch (exception) {
      if (exception instanceof ApiError) { setError(exception.message); setErrors(exception.errors) }
      else setError('Riwayat merit tidak dapat disimpan.')
    } finally { setSubmitting(false) }
  }

  if (loading) return <div className="page-loader"><span/><p>Memuat formulir merit…</p></div>
  if (!person || !references) return <div className="page-wrap"><div className="alert error" role="alert">{error || 'Form tidak tersedia.'}</div><Link to={`/personel/${id}`}>Kembali ke profil</Link></div>
  return <div className="page-wrap form-page">
    <div className="detail-back"><Link to={`/personel/${id}`}>← Kembali ke profil</Link></div>
    <header className="page-header"><div><span className="eyebrow">{config.eyebrow}</span><h1>{recordId ? 'Edit' : 'Tambah'} {config.title.toLowerCase()}</h1><p>Catat fakta untuk {person.nama_lengkap}. Verifikasi dilakukan oleh Admin SSDM.</p></div></header>
    <form className="person-form" onSubmit={submit}>
      {error && <div className="alert error" role="alert">{error}</div>}
      <section className="form-section-shell reveal"><div className="form-section-core">
        <div className="form-section-heading"><span>01</span><div><h2>Data riwayat</h2><p>PDF opsional. Perubahan data membatalkan verifikasi sebelumnya.</p></div></div>
        <fieldset className="form-grid history-fieldset" disabled={submitting}>
          {config.fields.map((field) => <HistoryField key={field.key} label={field.label} error={errors[field.key]?.[0]} wide={field.type === 'textarea'}>
            {field.type === 'select' ? <select value={values[field.key] ?? ''} required={field.required} onChange={(event) => setField(field.key, event.target.value)}><option value="">Pilih {field.label.toLowerCase()}</option>{field.options?.map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select>
              : field.type === 'textarea' ? <textarea value={values[field.key] ?? ''} required={field.required} maxLength={field.key === 'alasan' || field.key === 'keterangan' ? 1000 : undefined} rows={3} onChange={(event) => setField(field.key, event.target.value)} />
                : <input type={field.type ?? 'text'} value={values[field.key] ?? ''} required={field.required} min={field.type === 'number' ? 1900 : undefined} max={field.type === 'number' ? maximumYear : undefined} maxLength={field.type ? undefined : field.key === 'kode_operasi' || field.key === 'nomor_surat_perintah' || field.key === 'nomor_keputusan' ? 100 : 255} onChange={(event) => setField(field.key, event.target.value)} />}
          </HistoryField>)}
          {validKind !== 'penghargaan' && <HistoryReference label="Bidang / fungsi (opsional)" value={values.bidang_fungsi_id ?? ''} current={record?.bidang_fungsi} options={references.bidang_fungsi} required={false} error={errors.bidang_fungsi_id?.[0]} onChange={(value) => setField('bidang_fungsi_id', value)} />}
          <HistoryDocumentField label="PDF bukti" existing={record?.dokumen} file={file} remove={removeDocument} error={errors.dokumen?.[0]} onFile={setFile} onRemove={setRemoveDocument} />
        </fieldset>
      </div></section>
      <div className="form-actions"><Link className="secondary-cta" to={`/personel/${id}`}>Batal</Link><button className="primary-cta" disabled={submitting}><span>{submitting ? 'Menyimpan…' : `Simpan ${config.title.toLowerCase()}`}</span><span className="cta-icon">✓</span></button></div>
    </form>
  </div>
}
