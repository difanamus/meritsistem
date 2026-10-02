import type { ReferenceItem } from '../types'

export interface RegistrationDraft { id: string; values: Record<string, string>; file: File | null; unit?: ReferenceItem }
export interface RegistrationHistoriesDraft {
  pendidikan_umum: RegistrationDraft
  pendidikan_polri: RegistrationDraft | null
  kualifikasi: RegistrationDraft[]
  riwayat_jabatan: RegistrationDraft[]
  penugasan_operasi: RegistrationDraft[]
  prestasi: RegistrationDraft[]
  penghargaan: RegistrationDraft[]
}
export const emptyRegistrationRecord = (): RegistrationDraft => ({ id: crypto.randomUUID(), values: {}, file: null })
export const initialRegistrationHistories = (): RegistrationHistoriesDraft => ({ pendidikan_umum: emptyRegistrationRecord(), pendidikan_polri: emptyRegistrationRecord(), kualifikasi: [], riwayat_jabatan: [], penugasan_operasi: [], prestasi: [], penghargaan: [] })

export function appendRegistrationData(body: FormData, key: string, record: Pick<RegistrationDraft, 'values' | 'file'>, fileKey: string) {
  for (const [field, value] of Object.entries(record.values)) if (value !== '') body.append(`${key}[${field}]`, value)
  if (record.file) body.append(`${key}[${fileKey}]`, record.file)
}
