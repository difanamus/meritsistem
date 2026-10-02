import { UnitSearchSelect } from './UnitSearchSelect'
import { meritConfig, meritValues } from '../lib/meritProfile'
import type { MeritField } from '../lib/meritProfile'
import type { MeritKind, NonUnitReferenceOptions } from '../types'
import { emptyRegistrationRecord } from '../lib/registrationDrafts'
import type { RegistrationDraft, RegistrationHistoriesDraft } from '../lib/registrationDrafts'
const educationFields: MeritField[] = [
  { key: 'nama_kualifikasi', label: 'Nama pendidikan', required: true }, { key: 'jenjang', label: 'Jenjang' }, { key: 'bidang_studi', label: 'Bidang studi' },
  { key: 'institusi_penyelenggara', label: 'Institusi / penyelenggara' }, { key: 'tahun', label: 'Tahun lulus', type: 'number', required: true },
  { key: 'tanggal_mulai', label: 'Tanggal mulai', type: 'date' }, { key: 'tanggal_selesai', label: 'Tanggal selesai', type: 'date' },
  { key: 'nomor_dokumen', label: 'Nomor ijazah / SK' }, { key: 'keterangan', label: 'Keterangan', type: 'textarea' },
]
const jobFields: MeritField[] = [{ key: 'nama_jabatan', label: 'Nama jabatan', required: true }, { key: 'tanggal_mulai', label: 'Tanggal mulai', required: true, type: 'date' }, { key: 'tanggal_selesai', label: 'Tanggal selesai', required: true, type: 'date' }, { key: 'nivelering', label: 'Nivelering' }, { key: 'keterangan', label: 'Keterangan', type: 'textarea' }]
const groups = [['kualifikasi', 'Kualifikasi tambahan'], ['riwayat_jabatan', 'Riwayat jabatan'], ['penugasan_operasi', 'Penugasan operasi'], ['prestasi', 'Prestasi'], ['penghargaan', 'Penghargaan']] as const

export function RegistrationHistories({ value, onChange, references, jenisPersonel, errors }: { value: RegistrationHistoriesDraft; onChange: (value: RegistrationHistoriesDraft) => void; references: NonUnitReferenceOptions | null; jenisPersonel: 'polri' | 'pns'; errors: Record<string, string[]> }) {
  const options = (key: string): MeritField => ({ key, label: key === 'bidang_fungsi_id' ? 'Bidang / fungsi' : key === 'jenis_penugasan_id' ? 'Status jabatan / jenis penugasan' : 'Jenis kualifikasi', type: 'select', required: key !== 'bidang_fungsi_id', options: (key === 'bidang_fungsi_id' ? references?.bidang_fungsi : key === 'jenis_penugasan_id' ? references?.jenis_penugasan : references?.jenis_kualifikasi.filter((item) => !['PENDIDIKAN_UMUM', 'PENDIDIKAN_POLRI'].includes(item.kode ?? '')))?.map((item) => [String(item.id), item.nama]) ?? [] })
  const renderRecord = (prefix: string, draft: RegistrationDraft, fields: MeritField[], fileKey: string, update: (draft: RegistrationDraft) => void, job = false) => <div className="form-grid">
    {job && <><UnitSearchSelect label="Satker jabatan terdahulu" value={draft.values.unit_organisasi_id ?? ''} current={draft.unit} onChange={(id, unit) => update({ ...draft, values: { ...draft.values, unit_organisasi_id: id }, unit })} error={errors[`${prefix}.unit_organisasi_id`]?.[0]} />
      <label><span>Jenis riwayat</span><select value={draft.values.is_jabatan_utama ?? '1'} onChange={(event) => update({ ...draft, values: { ...draft.values, is_jabatan_utama: event.target.value } })}><option value="1">Jabatan utama terdahulu</option><option value="0">Penugasan tambahan terdahulu</option></select></label></>}
    {fields.map((field) => <label key={field.key} className={field.type === 'textarea' ? 'wide' : ''}><span>{field.label}{field.required ? ' *' : ''}</span>
      {field.type === 'select' ? <select required={field.required} value={draft.values[field.key] ?? ''} onChange={(event) => update({ ...draft, values: { ...draft.values, [field.key]: event.target.value } })}><option value="">Pilih…</option>{field.options?.map(([id, label]) => <option key={id} value={id}>{label}</option>)}</select>
        : field.type === 'textarea' ? <textarea required={field.required} value={draft.values[field.key] ?? ''} onChange={(event) => update({ ...draft, values: { ...draft.values, [field.key]: event.target.value } })} />
          : <input type={field.type ?? 'text'} required={field.required} min={field.type === 'number' ? 1900 : undefined} max={field.type === 'number' ? new Date().getFullYear() + 1 : undefined} value={draft.values[field.key] ?? ''} onChange={(event) => update({ ...draft, values: { ...draft.values, [field.key]: event.target.value } })} />}
      {errors[`${prefix}.${field.key}`]?.[0] && <small role="alert">{errors[`${prefix}.${field.key}`][0]}</small>}
    </label>)}
    <label className="wide"><span>PDF bukti / SK (opsional, maksimal 5 MB)</span><input type="file" accept=".pdf,application/pdf" onChange={(event) => update({ ...draft, file: event.target.files?.[0] ?? null })} />{errors[`${prefix}.${fileKey}`]?.[0] && <small role="alert">{errors[`${prefix}.${fileKey}`][0]}</small>}</label>
    {errors[prefix]?.[0] && <p className="inline-field-error wide">{errors[prefix][0]}</p>}
  </div>
  return <>
    <section className="form-section-shell"><div className="form-section-core"><div className="form-section-heading"><span>03</span><div><h2>Pendidikan umum · wajib</h2><p>Masukkan satu pendidikan yang telah diselesaikan. Riwayat lain dapat ditambahkan dari profil.</p></div></div>
      {renderRecord('pendidikan_umum', value.pendidikan_umum, educationFields, 'dokumen_pendukung', (record) => onChange({ ...value, pendidikan_umum: record }))}
    </div></section>
    <section className="form-section-shell"><div className="form-section-core"><div className="form-section-heading"><span>04</span><div><h2>Pendidikan Polri · {jenisPersonel === 'polri' ? 'wajib' : 'opsional untuk PNS'}</h2><p>Dicatat sebagai riwayat pendidikan, tanpa penilaian otomatis.</p></div></div>
      {jenisPersonel === 'pns' && <label className="inline-checkbox"><input type="checkbox" checked={value.pendidikan_polri !== null} onChange={(event) => onChange({ ...value, pendidikan_polri: event.target.checked ? emptyRegistrationRecord() : null })} />Personel PNS memiliki pendidikan Polri</label>}
      {value.pendidikan_polri && renderRecord('pendidikan_polri', value.pendidikan_polri, educationFields, 'dokumen_pendukung', (record) => onChange({ ...value, pendidikan_polri: record }))}
    </div></section>
    {groups.map(([key, title], groupIndex) => <section className="form-section-shell" key={key}><div className="form-section-core"><div className="form-section-heading scope-heading"><span>{String(groupIndex + 5).padStart(2, '0')}</span><div><h2>{title}</h2><p>Opsional. Jika tidak ditambah, riwayat tetap kosong dan dapat dilengkapi nanti di profil.</p></div><button type="button" className="scope-add" disabled={value[key].length >= 20} onClick={() => {
      const kind = key === 'penugasan_operasi' ? 'penugasan-operasi' : key
      const record = emptyRegistrationRecord()
      if (key === 'riwayat_jabatan') record.values.is_jabatan_utama = '1'
      else if (key !== 'kualifikasi') record.values = meritValues(kind as MeritKind)
      onChange({ ...value, [key]: [...value[key], record] })
    }}>+ Tambah {title.toLowerCase()}</button></div>
      {errors[key]?.[0] && <p className="inline-field-error">{errors[key][0]}</p>}
      {value[key].map((record, index) => {
        const kind = key === 'penugasan_operasi' ? 'penugasan-operasi' : key
        const fields = key === 'kualifikasi' ? [options('jenis_kualifikasi_id'), options('bidang_fungsi_id'), ...educationFields.map((field) => ({ ...field, required: field.key === 'nama_kualifikasi', label: field.key === 'nama_kualifikasi' ? 'Nama kualifikasi' : field.label }))] : key === 'riwayat_jabatan' ? [{ ...options('bidang_fungsi_id'), required: true }, options('jenis_penugasan_id'), ...jobFields] : [...meritConfig[kind as MeritKind].fields, options('bidang_fungsi_id')]
        const fileKey = key === 'kualifikasi' ? 'dokumen_pendukung' : key === 'riwayat_jabatan' ? 'dokumen_sk' : 'dokumen'
        return <div className="registration-record" key={record.id}><div className="scope-row-title"><strong>{title} {index + 1}</strong><button type="button" onClick={() => onChange({ ...value, [key]: value[key].filter((_, itemIndex) => itemIndex !== index) })}>Batalkan riwayat ini</button></div>
          {renderRecord(`${key}.${index}`, record, fields, fileKey, (updated) => onChange({ ...value, [key]: value[key].map((item, itemIndex) => itemIndex === index ? updated : item) }), key === 'riwayat_jabatan')}
        </div>
      })}
    </div></section>)}
  </>
}
