export function historyBody(values: Record<string, string>, initial: Record<string, string> | null, fileField: string, file: File | null, removeDocument: boolean) {
  const body = new FormData()
  if (initial) body.append('_method', 'PUT')
  const datesChanged = initial && (values.tanggal_mulai !== initial.tanggal_mulai || values.tanggal_selesai !== initial.tanggal_selesai)
  Object.entries(values).forEach(([key, value]) => {
    if (!initial || value !== initial[key] || (datesChanged && (key === 'tanggal_mulai' || key === 'tanggal_selesai'))) {
      body.append(key, value)
    }
  })
  if (file) body.append(fileField, file)
  if (initial && removeDocument && !file) body.append('hapus_dokumen', '1')
  return body
}

export function pdfError(file: Pick<File, 'name' | 'size' | 'type'> | null) {
  if (!file) return ''
  if (!file.name.toLowerCase().endsWith('.pdf') || (file.type && !['application/pdf', 'application/x-pdf'].includes(file.type))) return 'Dokumen harus berupa PDF.'
  if (file.size > 5 * 1024 * 1024) return 'Ukuran PDF maksimal 5 MB.'
  return ''
}

export function isActivePrimary(position: { is_jabatan_utama: boolean; tanggal_selesai: string | null }) {
  return position.is_jabatan_utama && position.tanggal_selesai === null
}
