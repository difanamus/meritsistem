export type UserRole = 'system_admin' | 'admin_ssdm' | 'operator'

export interface UserScope {
  id: number
  scope_type: 'own_unit' | 'unit_and_descendants'
  scope_type_label: string
  unit_organisasi: ReferenceItem
}

export interface User {
  id: number
  name: string
  email: string
  role: UserRole
  role_label: string
  is_active: boolean
  scopes: UserScope[]
}

export interface ReferenceItem {
  id: number
  kode?: string
  nama: string
  parent_id?: number | null
  jenis_unit?: string
  jenis_personel?: string
  urutan?: number
}

export interface Position {
  id: number
  nama_jabatan: string
  is_jabatan_utama?: boolean
  tanggal_mulai: string
  tanggal_selesai?: string | null
  unit_organisasi?: ReferenceItem | string
  bidang_fungsi?: ReferenceItem | string
  jenis_penugasan?: ReferenceItem | string
}

export interface Qualification {
  id: number
  nama_kualifikasi: string
  tahun?: number | null
  jenis_kualifikasi: string
  bidang_fungsi?: string | null
}

export interface Personnel {
  id: number
  jenis_personel: 'polri' | 'pns'
  jenis_personel_label: string
  nomor_identitas: string
  nama_lengkap: string
  pangkat: ReferenceItem
  tempat_lahir: string
  tanggal_lahir: string
  unit_organisasi: ReferenceItem
  status: 'aktif' | 'pensiun' | 'nonaktif'
  status_label: string
  jumlah_kualifikasi?: number
  ringkasan_relevan?: {
    jumlah_kualifikasi: number
    durasi_pengalaman_hari: number
    tahun_kualifikasi_terbaru: number | null
  }
  jabatan_utama_aktif?: Position | null
  penugasan_tambahan_aktif?: Position[]
  kualifikasi?: Qualification[]
  riwayat_jabatan?: Position[]
  created_at: string
  updated_at: string
}

export interface PaginationMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number | null
  to: number | null
}

export interface ReferenceOptions {
  unit_organisasi: ReferenceItem[]
  pangkat: ReferenceItem[]
  bidang_fungsi: ReferenceItem[]
  jenis_kualifikasi: ReferenceItem[]
  jenis_penugasan: ReferenceItem[]
}
