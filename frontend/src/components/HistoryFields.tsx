import type { ReactNode } from 'react'
import type { PrivateDocument, ReferenceItem } from '../types'

export function HistoryField({ label, error, wide, children }: { label: string; error?: string; wide?: boolean; children: ReactNode }) {
  return <label className={wide ? 'wide' : undefined}><span>{label}</span>{children}{error && <small role="alert">{error}</small>}</label>
}

export function HistoryReference({ label, value, options, current, required = true, disabled = false, error, onChange }: {
  label: string; value: string; options: ReferenceItem[]; current?: ReferenceItem | null; required?: boolean; disabled?: boolean; error?: string; onChange: (value: string) => void
}) {
  return <HistoryField label={label} error={error}><select value={value} required={required} disabled={disabled} onChange={(event) => onChange(event.target.value)}>
    <option value="">{required ? 'Pilih referensi' : 'Pilih bidang / fungsi (opsional)'}</option>
    {current && !options.some((item) => item.id === current.id) && <option value={current.id}>{current.nama} (nilai riwayat)</option>}
    {options.map((item) => <option key={item.id} value={item.id}>{item.nama}</option>)}
  </select></HistoryField>
}

export function HistoryDocumentField({ label, existing, file, remove, error, onFile, onRemove }: {
  label: string; existing?: PrivateDocument | null; file: File | null; remove: boolean; error?: string; onFile: (file: File | null) => void; onRemove: (remove: boolean) => void
}) {
  return <div className="wide history-document-field">
    {existing && <div className="document-summary"><strong>Dokumen tersimpan: {existing.nama_asli}</strong><p>{Math.ceil(existing.ukuran / 1024)} KB · kosongkan pilihan file untuk mempertahankannya.</p>
      <label className="checkbox-field"><input type="checkbox" checked={remove} disabled={!!file} onChange={(event) => onRemove(event.target.checked)} />Hapus dokumen tersimpan saat menyimpan</label>
    </div>}
    <HistoryField label={`${label} (opsional, maks. 5 MB)`} error={error}><input className="file-input" type="file" accept="application/pdf,.pdf" onChange={(event) => { onFile(event.target.files?.[0] ?? null); onRemove(false) }} /></HistoryField>
    {file && existing && <p className="dashboard-note">PDF baru akan menggantikan dokumen lama setelah berhasil disimpan.</p>}
  </div>
}
