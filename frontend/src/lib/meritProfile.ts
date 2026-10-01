import type { MeritKind } from '../types'

export interface MeritField {
  key: string
  label: string
  required?: boolean
  type?: 'date' | 'number' | 'select' | 'textarea'
  options?: [string, string][]
}

export const levels: [string, string][] = [['satker', 'Satker'], ['kabupaten_kota', 'Kabupaten/Kota'], ['provinsi', 'Provinsi'], ['nasional', 'Nasional'], ['internasional', 'Internasional']]

export const meritConfig: Record<MeritKind, { title: string; eyebrow: string; fields: MeritField[] }> = {
  'penugasan-operasi': { title: 'Penugasan operasi', eyebrow: 'Pengalaman lapangan', fields: [
    { key: 'nama', label: 'Nama operasi', required: true },
    { key: 'kode_operasi', label: 'Kode operasi' },
    { key: 'jenis_operasi', label: 'Jenis operasi', required: true },
    { key: 'tingkat', label: 'Tingkat operasi', required: true, type: 'select', options: levels },
    { key: 'wilayah', label: 'Wilayah', required: true },
    { key: 'peran', label: 'Peran/jabatan', required: true },
    { key: 'satgas_unit', label: 'Satgas/unit pelaksana', required: true },
    { key: 'tanggal_mulai', label: 'Tanggal mulai', required: true, type: 'date' },
    { key: 'tanggal_selesai', label: 'Tanggal selesai', type: 'date' },
    { key: 'nomor_surat_perintah', label: 'Nomor surat perintah' },
    { key: 'keterangan', label: 'Hasil/keterangan', type: 'textarea' },
  ] },
  prestasi: { title: 'Prestasi personel', eyebrow: 'Capaian faktual', fields: [
    { key: 'nama', label: 'Nama prestasi', required: true },
    { key: 'kategori', label: 'Kategori', required: true, type: 'select', options: [['olahraga', 'Olahraga'], ['akademik', 'Akademik'], ['operasional', 'Operasional'], ['inovasi', 'Inovasi'], ['pelayanan', 'Pelayanan'], ['lainnya', 'Lainnya']] },
    { key: 'tingkat', label: 'Tingkat', required: true, type: 'select', options: levels },
    { key: 'hasil', label: 'Peringkat/hasil', required: true },
    { key: 'penyelenggara', label: 'Penyelenggara', required: true },
    { key: 'tanggal', label: 'Tanggal prestasi', type: 'date' },
    { key: 'tahun', label: 'Tahun', required: true, type: 'number' },
    { key: 'peran', label: 'Peran', required: true, type: 'select', options: [['individu', 'Individu'], ['tim', 'Tim']] },
    { key: 'keterangan', label: 'Keterangan', type: 'textarea' },
  ] },
  penghargaan: { title: 'Penghargaan resmi', eyebrow: 'Pengakuan institusi', fields: [
    { key: 'nama', label: 'Nama penghargaan', required: true },
    { key: 'tingkat', label: 'Tingkat', required: true, type: 'select', options: levels },
    { key: 'pemberi', label: 'Pemberi penghargaan', required: true },
    { key: 'nomor_keputusan', label: 'Nomor keputusan' },
    { key: 'tanggal_keputusan', label: 'Tanggal keputusan', required: true, type: 'date' },
    { key: 'alasan', label: 'Alasan pemberian', required: true, type: 'textarea' },
    { key: 'keterangan', label: 'Keterangan', type: 'textarea' },
  ] },
}

export function meritValues(kind: MeritKind, record?: Record<string, unknown>): Record<string, string> {
  const result: Record<string, string> = { bidang_fungsi_id: record?.bidang_fungsi && typeof record.bidang_fungsi === 'object' && 'id' in record.bidang_fungsi ? String(record.bidang_fungsi.id) : '' }
  for (const field of meritConfig[kind].fields) result[field.key] = record?.[field.key] == null ? '' : String(record[field.key])
  return result
}
